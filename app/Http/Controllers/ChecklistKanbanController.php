<?php

namespace App\Http\Controllers;

use App\Models\SpjChecklist;
use App\Models\ChecklistHistory;
use App\Services\SuratTugasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChecklistKanbanController extends Controller
{
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Belum Ada,Belum Lengkap,Lengkap,Perlu Perbaikan',
        ]);

        $checklist = SpjChecklist::findOrFail($id);
        $oldStatus = $checklist->status;
        $newStatus = $request->status;

        // Validasi terpusat: Surat Tugas hanya boleh "Lengkap" bila memenuhi syarat.
        if ($newStatus === 'Lengkap'
            && $oldStatus !== 'Lengkap'
            && SuratTugasService::isSuratTugas($checklist)
            && ! SuratTugasService::isComplete($checklist)) {
            return response()->json([
                'success' => false,
                'revert' => true,
                'message' => SuratTugasService::completenessMessageForChecklist($checklist),
            ], 422);
        }

        if ($oldStatus !== $newStatus) {
            $checklist->status = $newStatus;
            $checklist->save();

            ChecklistHistory::create([
                'checklist_id' => $checklist->id,
                'status_lama' => $oldStatus,
                'status_baru' => $newStatus,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diubah',
                'history' => [
                    'status_baru' => $newStatus,
                    'user' => Auth::user()->name,
                    'time' => now()->format('d/m/Y H:i'),
                    'document' => $checklist->nama_dokumen
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status tidak berubah'
        ]);
    }
}
