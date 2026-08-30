<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use App\Models\RequestStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestStatusController extends Controller
{
    /**
     * Ubah status SPJ via form (POST).
     * Aturan:
     * - Dikirim ke PPK: wajib tanggal_kirim_ppk
     * - Perbaikan: wajib catatan, file_bukti opsional
     * - Selesai: wajib tanggal_selesai_spj + catatan, file_bukti opsional
     */
    public function update(Request $request, $id)
    {
        $fpaRequest = FpaRequest::findOrFail($id);
        $oldStatus = $fpaRequest->status_spj;

        $rules = [
            'status_baru' => 'required|in:Persiapan,Pelaksanaan,Pengumpulan SPJ,Dikirim ke PPK,Perbaikan,Selesai',
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ];

        $newStatus = $request->input('status_baru');

        if ($newStatus === 'Dikirim ke PPK') {
            $rules['tanggal_kirim_ppk'] = 'required|date';
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
     * Ubah status via AJAX (untuk Kanban FPA di Sprint 7).
     */
    public function updateAjax(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Persiapan,Pelaksanaan,Pengumpulan SPJ,Dikirim ke PPK,Perbaikan,Selesai',
        ]);

        $fpaRequest = FpaRequest::findOrFail($id);
        $oldStatus = $fpaRequest->status_spj;
        $newStatus = $request->status;

        if ($oldStatus !== $newStatus) {
            $fpaRequest->status_spj = $newStatus;
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

        return response()->json(['success' => true, 'message' => 'Status tidak berubah']);
    }
}
