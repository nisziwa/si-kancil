<?php

namespace App\Http\Controllers;

use App\Models\ChecklistHistory;
use App\Models\SpjChecklist;
use App\Models\TravelReportPelaksana;
use App\Services\ChecklistStatusGate;
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

        $reason = ChecklistStatusGate::blockedReason($checklist, $newStatus);

        // Perpindahan yang divalidasi & sudah dikonfirmasi (popup "Ubah Status SPJ")
        // diteruskan: checklist dipindah + status SPJ ikut menjadi Perbaikan.
        if ($reason && $reason['code'] !== ChecklistStatusGate::CODE_SPJ_NEEDS_PERBAIKAN) {
            return response()->json([
                'success' => false,
                'revert' => true,
                'require_st_confirmation' => in_array($reason['code'], [
                    ChecklistStatusGate::CODE_ST_SELF_INCOMPLETE,
                    ChecklistStatusGate::CODE_ST_DEPENDENT,
                ], true),
                'require_confirmation' => in_array($reason['code'], [
                    ChecklistStatusGate::CODE_LAPORAN_NOT_COLLECTED,
                    ChecklistStatusGate::CODE_DOKUMENTASI_BELUM_LENGKAP,
                ], true),
                'checklist_id' => $reason['checklist_id'],
                'link_text' => $reason['code'] === ChecklistStatusGate::CODE_DOKUMENTASI_BELUM_LENGKAP
                    ? 'Lengkapi Dokumentasi'
                    : 'Lengkapi Laporan',
                'not_collected' => $reason['not_collected'] ?? null,
                'message' => $reason['message'],
            ], 422);
        }

        // Dokumen dipindah ke "Perlu Perbaikan" saat SPJ "Dikirim ke PPK"
        // memerlukan konfirmasi popup terlebih dahulu.
        if ($reason && $reason['code'] === ChecklistStatusGate::CODE_SPJ_NEEDS_PERBAIKAN
            && ! $request->boolean('confirm_spj')) {
            return response()->json([
                'success' => false,
                'revert' => true,
                'require_spj_confirm' => true,
                'checklist_id' => $reason['checklist_id'],
                'message' => $reason['message'],
            ], 422);
        }

        if ($oldStatus !== $newStatus) {
            $fpa = $checklist->request;
            $spjNeedsPerbaikan = ChecklistStatusGate::spjNeedsPerbaikan($fpa, $checklist, $newStatus);

            ChecklistStatusGate::applyChecklistStatus($checklist, $newStatus);

            // Dokumen dipindah ke "Perlu Perbaikan": status SPJ ikut menjadi Perbaikan.
            if ($spjNeedsPerbaikan) {
                ChecklistStatusGate::applySpjPerbaikan($fpa);
            }

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

        $fpa = $checklists->first()?->request;

        $success = [];
        $failed = [];
        $spjPerbaikan = false;

        foreach ($checklists as $checklist) {
            $reason = ChecklistStatusGate::blockedReason($checklist, $status, $fpa);

            // Konfirmasi SPJ tidak bersifat memblokir di jalur bulk: dipindah
            // dan status SPJ ikut diubah menjadi Perbaikan.
            if ($reason && $reason['code'] !== ChecklistStatusGate::CODE_SPJ_NEEDS_PERBAIKAN) {
                $failed[] = ['id' => $checklist->id, 'nama' => $checklist->nama_dokumen, 'error' => $reason['message']];
                continue;
            }

            if ($checklist->status !== $status) {
                ChecklistStatusGate::applyChecklistStatus($checklist, $status);

                if ($reason && $reason['code'] === ChecklistStatusGate::CODE_SPJ_NEEDS_PERBAIKAN) {
                    $spjPerbaikan = true;
                }
            }

            $success[] = ['id' => $checklist->id, 'nama' => $checklist->nama_dokumen];
        }

        if ($fpa && $spjPerbaikan) {
            ChecklistStatusGate::applySpjPerbaikan($fpa);
        }

        return response()->json([
            'success' => $failed === [],
            'spj_perbaikan' => $spjPerbaikan,
            'results' => ['success' => $success, 'failed' => $failed],
        ]);
    }

    /**
     * Hitung jumlah pelaksana yang belum mengumpulkan untuk Laporan Perjalanan.
     */
    protected function notCollectedCount(SpjChecklist $checklist): int
    {
        return ChecklistStatusGate::notCollectedCount($checklist);
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

        // Dokumentasi Belum Lengkap -> Laporan Perjalanan tidak boleh menjadi Lengkap.
        if (ChecklistStatusGate::isLaporanPerjalanan($checklist->nama_dokumen)) {
            $dokumentasi = ChecklistStatusGate::dokumentasiFor($checklist);
            if ($dokumentasi && $dokumentasi->status === 'Belum Lengkap') {
                return response()->json(['success' => false, 'message' => ChecklistStatusGate::DOKUMENTASI_BLOCK_MESSAGE], 422);
            }
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

        if (! ChecklistStatusGate::allTravelReportCollected($checklist)) {
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
        return ChecklistStatusGate::allTravelReportCollected($checklist);
    }

    protected function stDetailFor(SpjChecklist $checklist)
    {
        $stChecklist = $this->stChecklistFor($checklist);

        return $stChecklist ? $stChecklist->suratTugasDetail : null;
    }

    /**
     * Checklist "Surat Tugas" pada request yang sama.
     */
    protected function stChecklistFor(SpjChecklist $checklist)
    {
        return SuratTugasService::forRequest($checklist);
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
