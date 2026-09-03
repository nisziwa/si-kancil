<?php

namespace App\Http\Controllers;

use App\Models\ChecklistHistory;
use App\Models\SpjChecklist;
use App\Models\TravelReportPelaksana;
use App\Services\SuratTugasService;
use App\Support\Tanggal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChecklistKanbanController extends Controller
{
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Belum Ada,Belum Lengkap,Lengkap,Perlu Perbaikan',
        ]);

        $checklist = SpjChecklist::with('suratTugasDetail.pelaksanas')->findOrFail($id);
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

        // Laporan Perjalanan hanya boleh "Lengkap" bila seluruh pelaksana mengumpulkan.
        if ($newStatus === 'Lengkap'
            && $oldStatus !== 'Lengkap'
            && str_contains($checklist->nama_dokumen, 'Laporan Perjalanan')
            && ! $this->allTravelReportCollected($checklist)) {
            $notCollected = $this->notCollectedCount($checklist);

            return response()->json([
                'success' => false,
                'revert' => true,
                'require_confirmation' => true,
                'checklist_id' => $checklist->id,
                'not_collected' => $notCollected,
                'message' => 'Terdapat '.$notCollected.' pelaksana yang belum mengumpulkan laporan perjalanan.',
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
                    'time' => Tanggal::formatDateTime(now()),
                    'document' => $checklist->nama_dokumen,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status tidak berubah',
        ]);
    }

    /**
     * Bulk ubah status beberapa checklist sekaligus (kanban detail FPA).
     * Setiap item divalidasi sama seperti perubahan individual.
     */
    public function bulkStatus(Request $request, $requestId)
    {
        $status = $request->input('status');
        $validStatus = ['Belum Ada', 'Belum Lengkap', 'Lengkap', 'Perlu Perbaikan'];
        if (! in_array($status, $validStatus, true)) {
            return response()->json([
                'success' => false,
                'results' => ['success' => [], 'failed' => [['id' => null, 'nama' => null, 'error' => 'Pilih status target yang valid.']]],
            ], 422);
        }

        $ids = collect($request->input('ids', []))->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return response()->json([
                'success' => false,
                'results' => ['success' => [], 'failed' => [['id' => null, 'nama' => null, 'error' => 'Pilih minimal satu checklist.']]],
            ], 422);
        }

        $checklists = SpjChecklist::with('suratTugasDetail.pelaksanas')
            ->where('request_id', $requestId)
            ->whereIn('id', $ids)
            ->get();

        $success = [];
        $failed = [];

        foreach ($checklists as $checklist) {
            $error = $this->checkStatusChange($checklist, $status);
            if ($error) {
                $failed[] = ['id' => $checklist->id, 'nama' => $checklist->nama_dokumen, 'error' => $error];
                continue;
            }

            $oldStatus = $checklist->status;
            if ($oldStatus !== $status) {
                $checklist->status = $status;
                $checklist->save();

                ChecklistHistory::create([
                    'checklist_id' => $checklist->id,
                    'status_lama' => $oldStatus,
                    'status_baru' => $status,
                    'user_id' => Auth::id(),
                ]);
            }

            $success[] = ['id' => $checklist->id, 'nama' => $checklist->nama_dokumen];
        }

        return response()->json([
            'success' => $failed === [],
            'results' => ['success' => $success, 'failed' => $failed],
        ]);
    }

    /**
     * Validasi per-checklist sebelum status diubah (dipakai single & bulk).
     * Mengembalikan pesan error (null bila valid).
     */
    protected function checkStatusChange(SpjChecklist $checklist, string $newStatus): ?string
    {
        if ($newStatus !== 'Lengkap' || $checklist->status === 'Lengkap') {
            return null;
        }

        if (SuratTugasService::isSuratTugas($checklist) && ! SuratTugasService::isComplete($checklist)) {
            return SuratTugasService::completenessMessageForChecklist($checklist);
        }

        if (str_contains($checklist->nama_dokumen, 'Laporan Perjalanan') && ! $this->allTravelReportCollected($checklist)) {
            return 'Masih ada pelaksana yang belum mengumpulkan laporan perjalanan.';
        }

        return null;
    }

    /**
     * Hitung jumlah pelaksana yang belum mengumpulkan untuk Laporan Perjalanan.
     */
    protected function notCollectedCount(SpjChecklist $checklist): int
    {
        $st = $this->stDetailFor($checklist);
        if (! $st || $st->pelaksanas->isEmpty()) {
            return 0;
        }

        $pelaksanaIds = $st->pelaksanas->pluck('id');
        $sudah = TravelReportPelaksana::where('checklist_id', $checklist->id)
            ->whereIn('surat_tugas_pelaksana_id', $pelaksanaIds)
            ->where('status', TravelReportPelaksana::STATUS_SUDAH)
            ->count();

        return $pelaksanaIds->count() - $sudah;
    }

    /**
     * Detail pelaksana Surat Tugas untuk popup konfirmasi Laporan Perjalanan.
     */
    public function laporanPelaksana($id)
    {
        $checklist = SpjChecklist::with(['travelReportPelaksanas'])->findOrFail($id);

        $reportStatuses = $checklist->travelReportPelaksanas->keyBy('surat_tugas_pelaksana_id');

        $st = $this->stDetailFor($checklist);

        $pelaksanas = $st && $st->pelaksanas
            ? $st->pelaksanas->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama_pelaksana,
                'nomor_surat' => $p->nomor_surat,
                'status' => optional($reportStatuses->get($p->id))->status ?? TravelReportPelaksana::STATUS_BELUM,
            ])
            : [];

        return response()->json([
            'success' => true,
            'checklist_id' => $checklist->id,
            'status_list' => TravelReportPelaksana::STATUS_LIST,
            'pelaksanas' => $pelaksanas,
        ]);
    }

    /**
     * Simpan status pengumpulan Laporan Perjalanan (bulk) dan terapkan status Lengkap
     * hanya bila seluruh pelaksana sudah mengumpulkan.
     */
    public function storeLaporanPelaksana(Request $request, $id)
    {
        $request->validate([
            'checklist_id' => 'required|integer|exists:spj_checklists,id',
            'report_status' => 'nullable|array',
            'report_status.selected' => 'nullable|array',
            'report_status.selected.*' => 'nullable|integer',
            'report_status.status.*' => 'nullable|in:'.implode(',', TravelReportPelaksana::STATUS_LIST),
        ]);

        $checklist = SpjChecklist::findOrFail($id);
        if ((int) $checklist->id !== (int) $request->input('checklist_id')) {
            return response()->json(['success' => false, 'message' => 'Checklist tidak cocok.'], 422);
        }

        $st = $this->stDetailFor($checklist);

        if ($st && $st->pelaksanas->count() > 0) {
            $statuses = $request->input('report_status', []);
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

        if (! $this->allTravelReportCollected($checklist)) {
            return response()->json([
                'success' => false,
                'message' => 'Seluruh pelaksana harus berstatus Sudah Mengumpulkan sebelum checklist menjadi Lengkap.',
            ], 422);
        }

        $this->applyLengkap($checklist);

        return response()->json([
            'success' => true,
            'message' => 'Laporan Perjalanan lengkap.',
            'history' => [
                'status_baru' => 'Lengkap',
                'user' => Auth::user()->name,
                'time' => Tanggal::formatDateTime(now()),
                'document' => $checklist->nama_dokumen,
            ],
        ]);
    }

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

    protected function stDetailFor(SpjChecklist $checklist)
    {
        $stChecklist = SpjChecklist::where('request_id', $checklist->request_id)
            ->where('nama_dokumen', 'like', '%Surat Tugas%')
            ->with('suratTugasDetail.pelaksanas')
            ->first();

        return $stChecklist ? $stChecklist->suratTugasDetail : null;
    }

    protected function applyLengkap(SpjChecklist $checklist): void
    {
        $oldStatus = $checklist->status;
        if ($oldStatus === 'Lengkap') {
            return;
        }

        $checklist->status = 'Lengkap';
        $checklist->save();

        ChecklistHistory::create([
            'checklist_id' => $checklist->id,
            'status_lama' => $oldStatus,
            'status_baru' => 'Lengkap',
            'user_id' => Auth::id(),
        ]);
    }
}
