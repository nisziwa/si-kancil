<?php

namespace App\Http\Controllers;

use App\Models\ChecklistHistory;
use App\Models\SkRatePerjalanan;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\TravelDetail;
use App\Models\TravelReportPelaksana;
use App\Services\SuratTugasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SpjChecklistController extends Controller
{
    public function edit($id)
    {
        $checklist = SpjChecklist::with([
            'request',
            'suratTugasDetail.pelaksanas.superkendis',
            'travelDetail',
            'realExpenseDetail',
            'travelReportPelaksanas',
        ])->findOrFail($id);

        // Sumber data pelaksana berasal dari checklist "Surat Tugas" pada request yang sama.
        $stChecklist = SpjChecklist::where('request_id', $checklist->request_id)
            ->where('nama_dokumen', 'like', '%Surat Tugas%')
            ->with('suratTugasDetail.pelaksanas.superkendis')
            ->first();

        $stDetail = $stChecklist ? $stChecklist->suratTugasDetail : null;
        $stPelaksanas = $stDetail ? $stDetail->pelaksanas : collect();

        // Travel reports per pelaksana (keyed by surat_tugas_pelaksana_id) untuk
        // fitur upload & generate laporan pada detail checklist.
        $travelReports = \App\Models\TravelReport::where('fpa_id', $checklist->request_id)
            ->whereIn('surat_tugas_pelaksana_id', $stPelaksanas->pluck('id'))
            ->with('pokRincian')
            ->get()
            ->keyBy('surat_tugas_pelaksana_id');

        // Data pendukung form Generate Superkendis yang di-share di halaman
        // Kelola Dokumen (khusus checklist "Pengeluaran Riil + Surat Non Kendaraan Dinas").
        $superkendisDone = $stChecklist && $stChecklist->status === 'Lengkap';
        $selectedPelaksanaIds = SuperkendisController::parseSelectedPelaksanaIds(request('pelaksana'));
        $kecamatans = SkRatePerjalanan::orderBy('kecamatan')->get();

        return view('checklists.edit', compact(
            'checklist',
            'stDetail',
            'stPelaksanas',
            'stChecklist',
            'travelReports',
            'superkendisDone',
            'selectedPelaksanaIds',
            'kecamatans'
        ));
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
            $rules['isi_tugas'] = 'nullable|string';
            $rules['pelaksana_nama'] = 'nullable|array';
            $rules['pelaksana_nama.*'] = 'nullable|string';
        }

        if (str_contains($docName, 'Laporan Perjalanan')) {
            $rules['report_status'] = 'nullable|array';
            $rules['report_status.status'] = 'nullable|array';
            $rules['report_status.status.*'] = 'nullable|in:'.implode(',', TravelReportPelaksana::STATUS_LIST);
        }

        $validated = $request->validate($rules);

        $oldStatus = $checklist->status;
        $requestedStatus = $validated['status'];
        $isSuratTugas = SuratTugasService::isSuratTugas($checklist);
        $stChecklist = SuratTugasService::forRequest($checklist);

        // Gate Surat Tugas untuk dokumen dependen: selama ST belum lengkap,
        // Laporan Perjalanan / Pengeluaran Riil tidak boleh dipindah statusnya.
        $dependentBlocked = SuratTugasService::isDependentDocument($docName)
            && SuratTugasService::dependentMoveBlocked($stChecklist, $oldStatus, $requestedStatus);

        // Validasi terpusat: Surat Tugas hanya boleh "Lengkap" bila memenuhi syarat.
        // Berlaku sebelum data disimpan sehingga perubahan dibatalkan bila tidak lengkap.
        if ($isSuratTugas && $requestedStatus === 'Lengkap') {
            $missing = SuratTugasService::missingRequirementsFromFields(
                $request->input('nomor_surat_tugas'),
                $request->input('tanggal_surat_tugas'),
                $request->input('isi_tugas'),
                $request->input('pelaksana_nama', [])
            );

            if ($missing !== []) {
                return back()
                    ->withInput()
                    ->with('error', SuratTugasService::completenessMessage($missing));
            }
        }

        $newStatus = $requestedStatus;

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

        // Simpan data Surat Tugas.
        if ($isSuratTugas) {
            $stDetail = SuratTugasDetail::updateOrCreate(
                ['checklist_id' => $checklist->id],
                [
                    'nomor_surat_tugas' => $request->input('nomor_surat_tugas') ?? '',
                    'tanggal_surat_tugas' => $request->input('tanggal_surat_tugas'),
                    'isi_tugas' => $request->input('isi_tugas') ?? '',
                ]
            );

            $this->syncPelaksana($stDetail, $request->input('nomor_surat_tugas') ?? '', $request->input('pelaksana_nama', []));
        }

        // Simpan Travel Detail jika relevan (dari pelaksana Surat Tugas, bukan form manual).
        if (str_contains($docName, 'SPD') || str_contains($docName, 'SPPD')) {
            $this->syncTravelDetailFromSuratTugas($checklist);
        }

        // Simpan status pengumpulan Laporan Perjalanan per pelaksana (bulk checkbox).
        if (str_contains($docName, 'Laporan Perjalanan')) {
            $this->syncTravelReportPelaksana($checklist, $request->input('report_status', []));
        }

        // Surat Tugas belum lengkap: status dokumen dependen dikembalikan ke
        // status semula, tetap di halaman, dan tampilkan popup konfirmasi.
        if ($dependentBlocked) {
            $checklist->status = $oldStatus;
            $checklist->save();

            return back()->with('status_block', [
                'title' => 'Status Tidak Dapat Diperbarui',
                'message' => SuratTugasService::ST_DEPENDENT_BLOCK_MESSAGE,
                'link_text' => 'Lengkapi Surat Tugas',
                'link_href' => route('checklists.edit', $stChecklist->id),
            ]);
        }

        // Laporan Perjalanan hanya boleh "Lengkap" bila seluruh pelaksana
        // sudah mengumpulkan. Bila tidak: status ditentukan dari jumlah yang
        // belum mengumpulkan dan pengguna tetap berada di halaman ini.
        if (str_contains($docName, 'Laporan Perjalanan')
            && $requestedStatus === 'Lengkap'
            && ! $this->allTravelReportCollected($checklist)) {
            $total = $this->pelaksanaCount($checklist);
            $notCollected = $this->notCollectedCount($checklist);
            $effectiveStatus = $notCollected >= $total ? 'Belum Ada' : 'Belum Lengkap';

            $checklist->status = $effectiveStatus;
            $checklist->save();

            if ($oldStatus !== $effectiveStatus) {
                ChecklistHistory::create([
                    'checklist_id' => $checklist->id,
                    'status_lama' => $oldStatus,
                    'status_baru' => $effectiveStatus,
                    'catatan' => $validated['catatan'] ?? null,
                    'user_id' => Auth::id(),
                ]);
            }

            $message = $effectiveStatus === 'Belum Ada'
                ? 'Status tidak dapat diubah karena seluruh pelaksana belum mengumpulkan laporan perjalanan.'
                : 'Status diubah menjadi Belum Lengkap karena masih terdapat '.$notCollected.' pelaksana yang belum mengumpulkan laporan perjalanan.';

            return back()->with('status_block', [
                'title' => 'Status Tidak Dapat Diperbarui',
                'message' => $message,
            ]);
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

    /**
     * Simpan daftar pelaksana Surat Tugas dan bangun nomor sub otomatis.
     *
     * Nomor utama: B-1027/75040/KP.650/2026
     * Diubah menjadi: B-1027.1/..., B-1027.2/..., B-1027.3/...
     */
    protected function syncPelaksana(SuratTugasDetail $stDetail, string $nomorUtama, array $namaPelaksana)
    {
        // Hapus semua pelaksana lama
        SuratTugasPelaksana::where('surat_tugas_detail_id', $stDetail->id)->delete();

        $namaFiltered = array_values(array_filter(array_map('trim', $namaPelaksana), fn ($n) => $n !== ''));

        foreach ($namaFiltered as $index => $nama) {
            $nomorSub = $this->buildSuratSubNumber($nomorUtama, $index + 1);
            SuratTugasPelaksana::create([
                'surat_tugas_detail_id' => $stDetail->id,
                'nama_pelaksana' => $nama,
                'nomor_surat' => $nomorSub,
                'urutan' => $index + 1,
            ]);
        }
    }

    protected function buildSuratSubNumber(string $nomorUtama, int $sub): string
    {
        if ($nomorUtama === '') {
            return '';
        }

        // Format: XXXX<base>.N<remainder>
        // Cari titik sebelum tanda "/" (nomor utama), contoh B-1027/75040/KP.650/2026
        if (preg_match('/^(.*?)(\.\d+)?(\/.*)$/u', $nomorUtama, $m)) {
            return $m[1].'.'.$sub.$m[3];
        }

        return $nomorUtama.'.'.$sub;
    }

    /**
     * Checklist "Surat Tugas" pada request yang sama (dengan detail & pelaksana).
     */
    protected function stChecklistFor(SpjChecklist $checklist)
    {
        return SuratTugasService::forRequest($checklist);
    }

    /**
     * Sumber pelaksana berasal dari checklist "Surat Tugas" pada request yang sama.
     */
    protected function stDetailFor(SpjChecklist $checklist)
    {
        $stChecklist = $this->stChecklistFor($checklist);

        return $stChecklist ? $stChecklist->suratTugasDetail : null;
    }

    /**
     * Bangun TravelDetail dari sumber Surat Tugas (pelaksana pertama) bila data
     * tersedia. Tidak membuat baris kosong.
     */
    protected function syncTravelDetailFromSuratTugas(SpjChecklist $checklist): void
    {
        $st = $this->stDetailFor($checklist);
        if (! $st || $st->pelaksanas->isEmpty()) {
            return;
        }

        $pelaksana = $st->pelaksanas->first();
        $nomorUtama = (string) $st->nomor_surat_tugas;

        if (trim($nomorUtama) === '' || trim((string) $pelaksana->nama_pelaksana) === '') {
            return;
        }

        TravelDetail::updateOrCreate(
            ['checklist_id' => $checklist->id],
            [
                'nomor_spd' => $pelaksana->nomor_surat ?: $nomorUtama,
                'nama_pelaksana' => $pelaksana->nama_pelaksana,
                'maksud_perjalanan' => $st->isi_tugas ?? '',
                'tempat_berangkat' => '',
                'tempat_tujuan' => '',
                'tanggal_berangkat' => $st->tanggal_surat_tugas,
                'tanggal_kembali' => $st->tanggal_surat_tugas,
                'transportasi' => '',
            ]
        );
    }

    /**
     * Simpan status pengumpulan Laporan Perjalanan per pelaksana (bulk checkbox).
     * Hanya pelaksana yang dipilih yang diperbarui; yang lain tidak disentuh.
     */
    protected function syncTravelReportPelaksana(SpjChecklist $checklist, array $statuses): void
    {
        $st = $this->stDetailFor($checklist);
        if (! $st || $st->pelaksanas->isEmpty()) {
            return;
        }

        foreach ($st->pelaksanas as $pelaksana) {
            $selected = $statuses['selected'][$pelaksana->id] ?? null;
            if ($selected) {
                $status = $statuses['status'][$pelaksana->id] ?? TravelReportPelaksana::STATUS_SUDAH;

                TravelReportPelaksana::updateOrCreate(
                    [
                        'checklist_id' => $checklist->id,
                        'surat_tugas_pelaksana_id' => $pelaksana->id,
                    ],
                    ['status' => $status]
                );
            }
        }
    }

    /**
     * Seluruh pelaksana Surat Tugas sudah mengumpulkan laporan perjalanan.
     */
    protected function allTravelReportCollected(SpjChecklist $checklist): bool
    {
        $st = $this->stDetailFor($checklist);
        if (! $st || $st->pelaksanas->isEmpty()) {
            return true;
        }

        $pelaksanaIds = $st->pelaksanas->pluck('id');
        $sudah = TravelReportPelaksana::where('checklist_id', $checklist->id)
            ->whereIn('surat_tugas_pelaksana_id', $pelaksanaIds)
            ->where('status', TravelReportPelaksana::STATUS_SUDAH)
            ->count();

        return $sudah >= $pelaksanaIds->count();
    }

    /**
     * Jumlah pelaksana yang belum mengumpulkan laporan perjalanan.
     */
    protected function notCollectedCount(SpjChecklist $checklist): int
    {
        return $this->pelaksanaCount($checklist) - $this->collectedCount($checklist);
    }

    /**
     * Jumlah pelaksana pada Surat Tugas yang sama.
     */
    protected function pelaksanaCount(SpjChecklist $checklist): int
    {
        $st = $this->stDetailFor($checklist);

        return $st ? $st->pelaksanas->count() : 0;
    }

    /**
     * Jumlah pelaksana yang sudah mengumpulkan laporan perjalanan.
     */
    protected function collectedCount(SpjChecklist $checklist): int
    {
        $st = $this->stDetailFor($checklist);
        if (! $st || $st->pelaksanas->isEmpty()) {
            return 0;
        }

        return TravelReportPelaksana::where('checklist_id', $checklist->id)
            ->whereIn('surat_tugas_pelaksana_id', $st->pelaksanas->pluck('id'))
            ->where('status', TravelReportPelaksana::STATUS_SUDAH)
            ->count();
    }
}
