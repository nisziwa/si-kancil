<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use App\Models\RequestStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RequestStatusController extends Controller
{
    /**
     * Peta transisi status SPJ yang diperbolehkan.
     * Perbaikan bersifat opsional (tidak wajib dilewati).
     */
    public const TRANSITIONS = [
        'Persiapan' => ['Dikirim ke PPK'],
        'Dikirim ke PPK' => ['Selesai', 'Perbaikan'],
        'Perbaikan' => ['Dikirim ke PPK', 'Selesai'],
        'Selesai' => [],
    ];

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

        // Cek transisi diperbolehkan
        $allowed = self::TRANSITIONS[$oldStatus] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status_baru' => "Transisi status tidak diperbolehkan: {$oldStatus} → {$newStatus}.",
            ]);
        }

        // Validasi menuju Dikirim ke PPK
        if ($newStatus === 'Dikirim ke PPK') {
            $rules['tanggal_kirim_ppk'] = 'required|date';

            if (!$fpaRequest->has_nomor_fpa) {
                throw ValidationException::withMessages([
                    'status_baru' => 'SPJ belum dapat dikirim ke PPK. Nomor FPA wajib diisi terlebih dahulu.',
                ]);
            }

            if (!$fpaRequest->mandatory_checklist_complete) {
                throw ValidationException::withMessages([
                    'status_baru' => 'SPJ belum dapat dikirim ke PPK. Silakan lengkapi checklist dokumen terlebih dahulu.',
                ]);
            }
        }

        if ($newStatus === 'Perbaikan') {
            $rules['catatan'] = 'required|string';
        }

        if ($newStatus === 'Selesai') {
            $rules['tanggal_selesai_spj'] = 'required|date';
            $rules['catatan'] = 'required|string';
        }

        $validated = $request->validate($rules);

        // Update status FPA
        $fpaRequest->status_spj = $newStatus;

        if ($newStatus === 'Dikirim ke PPK') {
            $fpaRequest->tanggal_kirim_ppk = $validated['tanggal_kirim_ppk'];
        }

        if ($newStatus === 'Selesai') {
            $fpaRequest->tanggal_selesai_spj = $validated['tanggal_selesai_spj'];
        }

        $fpaRequest->save();

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

        $allowed = self::TRANSITIONS[$oldStatus] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => "Transisi tidak diperbolehkan: {$oldStatus} → {$newStatus}.",
            ], 422);
        }

        if ($newStatus === 'Dikirim ke PPK') {
            if (!$fpaRequest->has_nomor_fpa) {
                return response()->json([
                    'success' => false,
                    'message' => 'SPJ belum dapat dikirim ke PPK. Nomor FPA wajib diisi terlebih dahulu.',
                ], 422);
            }
            if (!$fpaRequest->mandatory_checklist_complete) {
                return response()->json([
                    'success' => false,
                    'message' => 'SPJ belum dapat dikirim ke PPK. Silakan lengkapi checklist dokumen terlebih dahulu.',
                ], 422);
            }
        }

        $fpaRequest->status_spj = $newStatus;

        if ($newStatus === 'Dikirim ke PPK') {
            $fpaRequest->tanggal_kirim_ppk = now()->format('Y-m-d');
        }

        if ($newStatus === 'Selesai') {
            $fpaRequest->tanggal_selesai_spj = now()->format('Y-m-d');
        }

        $fpaRequest->save();

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
}