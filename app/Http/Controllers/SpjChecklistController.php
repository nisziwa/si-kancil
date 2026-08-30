<?php

namespace App\Http\Controllers;

use App\Models\ChecklistHistory;
use App\Models\RealExpenseDetail;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\TravelDetail;
use App\Models\TravelReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SpjChecklistController extends Controller
{
    public function edit($id)
    {
        $checklist = SpjChecklist::with([
            'request',
            'suratTugasDetail',
            'travelDetail',
            'realExpenseDetail',
            'travelReport',
        ])->findOrFail($id);

        return view('checklists.edit', compact('checklist'));
    }

    public function update(Request $request, $id)
    {
        $checklist = SpjChecklist::findOrFail($id);

        $rules = [
            'status' => 'required|in:Belum Ada,Belum Lengkap,Lengkap,Perlu Perbaikan',
            'catatan' => 'nullable|string',
            'file_dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ];

        // Rules berdasarkan tipe dokumen
        $docName = $checklist->nama_dokumen;

        if (str_contains($docName, 'Surat Tugas')) {
            $rules['nomor_surat_tugas'] = 'nullable|string';
            $rules['tanggal_surat_tugas'] = 'nullable|date';
            $rules['pelaksana'] = 'nullable|string';
            $rules['isi_tugas'] = 'nullable|string';
        }

        if (str_contains($docName, 'SPD') || str_contains($docName, 'SPPD')) {
            $rules['nomor_spd'] = 'nullable|string';
            $rules['travel_nama_pelaksana'] = 'nullable|string';
            $rules['maksud_perjalanan'] = 'nullable|string';
            $rules['tempat_berangkat'] = 'nullable|string';
            $rules['tempat_tujuan'] = 'nullable|string';
            $rules['tanggal_berangkat'] = 'nullable|date';
            $rules['tanggal_kembali'] = 'nullable|date|after_or_equal:tanggal_berangkat';
            $rules['transportasi'] = 'nullable|string';
        }

        if (str_contains($docName, 'Pengeluaran Riil')) {
            $rules['real_nomor_surat_tugas'] = 'nullable|string';
            $rules['real_tanggal_surat_tugas'] = 'nullable|date';
            $rules['real_nama_pelaksana'] = 'nullable|string';
            $rules['real_jabatan'] = 'nullable|string';
            $rules['real_tanggal_kegiatan'] = 'nullable|date';
            $rules['uraian_pengeluaran'] = 'nullable|string';
            $rules['jumlah_pengeluaran'] = 'nullable|numeric|min:0';
            $rules['real_keterangan'] = 'nullable|string';
        }

        if (str_contains($docName, 'Laporan Perjalanan')) {
            $rules['report_nama_pelaksana'] = 'nullable|string';
            $rules['report_tujuan'] = 'nullable|string';
            $rules['report_uraian_kegiatan'] = 'nullable|string';
            $rules['report_tanggal_kegiatan'] = 'nullable|date';
            $rules['report_dokumentasi'] = 'nullable|file|mimes:pdf,jpg,jpeg,png,docx|max:10240';
        }

        $validated = $request->validate($rules);

        $oldStatus = $checklist->status;
        $newStatus = $validated['status'];

        $checklist->status = $newStatus;
        $checklist->catatan = $validated['catatan'] ?? null;

        // Handle file upload dokumen utama
        if ($request->hasFile('file_dokumen')) {
            if ($checklist->file_path && Storage::disk('public')->exists($checklist->file_path)) {
                Storage::disk('public')->delete($checklist->file_path);
            }
            $checklist->file_path = $request->file('file_dokumen')->store('spj-files', 'public');
        }

        $checklist->save();

        // Simpan Surat Tugas Detail jika relevan
        if (str_contains($docName, 'Surat Tugas')) {
            SuratTugasDetail::updateOrCreate(
                ['checklist_id' => $checklist->id],
                [
                    'nomor_surat_tugas' => $request->input('nomor_surat_tugas') ?? '',
                    'tanggal_surat_tugas' => $request->input('tanggal_surat_tugas'),
                    'pelaksana' => $request->input('pelaksana') ?? '',
                    'isi_tugas' => $request->input('isi_tugas') ?? '',
                ]
            );
        }

        // Simpan Travel Detail jika relevan
        if (str_contains($docName, 'SPD') || str_contains($docName, 'SPPD')) {
            TravelDetail::updateOrCreate(
                ['checklist_id' => $checklist->id],
                [
                    'nomor_spd' => $request->input('nomor_spd') ?? '',
                    'nama_pelaksana' => $request->input('travel_nama_pelaksana') ?? '',
                    'maksud_perjalanan' => $request->input('maksud_perjalanan') ?? '',
                    'tempat_berangkat' => $request->input('tempat_berangkat') ?? '',
                    'tempat_tujuan' => $request->input('tempat_tujuan') ?? '',
                    'tanggal_berangkat' => $request->input('tanggal_berangkat'),
                    'tanggal_kembali' => $request->input('tanggal_kembali'),
                    'transportasi' => $request->input('transportasi') ?? '',
                ]
            );
        }

        // Simpan Real Expense Detail jika relevan
        if (str_contains($docName, 'Pengeluaran Riil')) {
            RealExpenseDetail::updateOrCreate(
                ['checklist_id' => $checklist->id],
                [
                    'nomor_surat_tugas' => $request->input('real_nomor_surat_tugas') ?? '',
                    'tanggal_surat_tugas' => $request->input('real_tanggal_surat_tugas'),
                    'nama_pelaksana' => $request->input('real_nama_pelaksana') ?? '',
                    'jabatan' => $request->input('real_jabatan') ?? '',
                    'tanggal_kegiatan' => $request->input('real_tanggal_kegiatan'),
                    'uraian_pengeluaran' => $request->input('uraian_pengeluaran') ?? '',
                    'jumlah_pengeluaran' => $request->input('jumlah_pengeluaran') ?? 0,
                    'keterangan' => $request->input('real_keterangan'),
                ]
            );
        }

        // Simpan Travel Report jika relevan
        if (str_contains($docName, 'Laporan Perjalanan')) {
            $reportData = [
                'nama_pelaksana' => $request->input('report_nama_pelaksana') ?? '',
                'tujuan' => $request->input('report_tujuan') ?? '',
                'uraian_kegiatan' => $request->input('report_uraian_kegiatan') ?? '',
                'tanggal_kegiatan' => $request->input('report_tanggal_kegiatan'),
            ];

            if ($request->hasFile('report_dokumentasi')) {
                $reportData['dokumentasi'] = $request->file('report_dokumentasi')->store('spj-files', 'public');
            }

            TravelReport::updateOrCreate(
                ['checklist_id' => $checklist->id],
                $reportData
            );
        }

        // Log history jika ada perubahan status atau catatan
        if ($oldStatus !== $newStatus || $request->filled('catatan')) {
            ChecklistHistory::create([
                'checklist_id' => $checklist->id,
                'status_lama' => $oldStatus,
                'status_baru' => $newStatus,
                'catatan' => $validated['catatan'] ?? null,
                'user_id' => Auth::id(),
            ]);
        }

        return redirect()->route('requests.show', $checklist->request_id)
            ->with('success', 'Detail dokumen dan checklist berhasil diperbarui.');
    }
}
