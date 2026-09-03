<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use App\Models\RequestStatusHistory;
use App\Services\RequestStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RequestStatusController extends Controller
{
    protected $statusService;

    public function __construct(RequestStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Peta transisi status SPJ yang diperbolehkan.
     * Perbaikan bersifat opsional (tidak wajib dilewati).
     */
    public const TRANSITIONS = RequestStatusService::TRANSITIONS;

    /**
     * Ubah status SPJ via form (POST) dengan validasi alur.
     */
    public function update(Request $request, $id)
    {
        $fpaRequest = FpaRequest::findOrFail($id);
        $oldStatus = $fpaRequest->status_spj;

        $rules = [
            'status_baru' => 'required|in:' . implode(',', FpaRequest::STATUS_LIST),
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ];

        $newStatus = $request->input('status_baru');

        // Validasi transisi + nomor FPA + checklist (satu sumber).
        $result = $this->statusService->validate($fpaRequest, $newStatus);
        if (! $result['ok']) {
            throw ValidationException::withMessages([
                'status_baru' => implode(' ', $result['errors']),
            ]);
        }

        if ($newStatus === 'Perbaikan') {
            $rules['catatan'] = 'required|string';
        }

        if ($newStatus === 'Selesai') {
            $rules['tanggal_selesai_spj'] = 'required|date';
            $rules['catatan'] = 'required|string';
        }

        $validated = $request->validate($rules);

        // Terapkan perubahan status via service.
        $this->statusService->apply($fpaRequest, $newStatus, $validated);

        // Upload file bukti jika ada
        $filePath = null;
        if ($request->hasFile('file_bukti')) {
            $filePath = $request->file('file_bukti')->store('spj-files', 'public');
        }

        // Simpan history
        RequestStatusHistory::create([
            'request_id' => $fpaRequest->id,
            'status_lama' => $oldStatus,
            'status_baru' => $newStatus,
            'catatan' => $validated['catatan'] ?? null,
            'file_bukti' => $filePath,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('requests.show', $fpaRequest->id)
            ->with('success', "Status berhasil diubah dari '{$oldStatus}' ke '{$newStatus}'.");
    }

    /**
     * Ubah status via AJAX (untuk Kanban FPA).
     * Menerapkan validasi alur yang sama.
     */
    public function updateAjax(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', FpaRequest::STATUS_LIST),
        ]);

        $fpaRequest = FpaRequest::findOrFail($id);
        $oldStatus = $fpaRequest->status_spj;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return response()->json(['success' => true, 'message' => 'Status tidak berubah']);
        }

        // Validasi terpusat.
        $result = $this->statusService->validate($fpaRequest, $newStatus);
        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $result['errors']),
                'errors' => $result['errors'],
                'nomor_fpa' => $fpaRequest->nomor_fpa ?: 'Belum ada nomor FPA',
            ], 422);
        }

        $this->statusService->apply($fpaRequest, $newStatus);

        RequestStatusHistory::create([
            'request_id' => $fpaRequest->id,
            'status_lama' => $oldStatus,
            'status_baru' => $newStatus,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Status diubah ke {$newStatus}",
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }

    /**
     * Pindahkan banyak FPA sekaligus (bulk move Kanban FPA).
     * Validasi & hasil dikumpulkan per FPA; yang valid dipindah,
     * yang gagal tetap di status lama (tanpa rollback seluruh proses).
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:requests,id',
            'status' => 'required|in:' . implode(',', FpaRequest::STATUS_LIST),
        ]);

        $newStatus = $request->input('status');

        $results = ['success' => [], 'failed' => []];

        foreach ($request->input('ids') as $id) {
            $fpa = FpaRequest::find($id);
            if (! $fpa) {
                $results['failed'][] = ['nomor_fpa' => "ID {$id}", 'errors' => ['FPA tidak ditemukan.']];
                continue;
            }

            $label = $fpa->nomor_fpa ?: "FPA #{$fpa->id}";

            if ($fpa->status_spj === $newStatus) {
                $results['success'][] = ['nomor_fpa' => $label, 'status' => $newStatus, 'changed' => false];
                continue;
            }

            $result = $this->statusService->validate($fpa, $newStatus);
            if (! $result['ok']) {
                $results['failed'][] = ['nomor_fpa' => $label, 'errors' => $result['errors']];
                continue;
            }

            $oldStatus = $fpa->status_spj;
            $this->statusService->apply($fpa, $newStatus);

            RequestStatusHistory::create([
                'request_id' => $fpa->id,
                'status_lama' => $oldStatus,
                'status_baru' => $newStatus,
                'user_id' => Auth::id(),
            ]);

            $results['success'][] = ['nomor_fpa' => $label, 'status' => $newStatus, 'changed' => true];
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}
