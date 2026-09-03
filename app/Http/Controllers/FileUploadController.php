<?php

namespace App\Http\Controllers;

use App\Models\SpjChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    /**
     * Upload file untuk checklist SPJ (Max 10MB: PDF, JPG, PNG, DOCX).
     */
    public function upload(Request $request, $checklistId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ]);

        $checklist = SpjChecklist::findOrFail($checklistId);

        // Hapus file lama jika ada
        if ($checklist->file_path && Storage::disk('public')->exists($checklist->file_path)) {
            Storage::disk('public')->delete($checklist->file_path);
        }

        $path = $request->file('file')->store('spj-files', 'public');
        $checklist->file_path = $path;
        $checklist->save();

        return back()->with('success', 'File dokumen berhasil diupload.');
    }

    /**
     * Download / preview file checklist.
     */
    public function download($checklistId)
    {
        $checklist = SpjChecklist::findOrFail($checklistId);

        if (! $checklist->file_path || ! Storage::disk('public')->exists($checklist->file_path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return Storage::disk('public')->download($checklist->file_path);
    }
}
