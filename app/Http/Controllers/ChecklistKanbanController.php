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
            return response()->json([
                'success' => false,
                'revert' => true,
                'require_confirmation' => true,
                'checklist_id' => $checklist->id,
                'message' => 'Konfirmasi Laporan Perjalanan diperlukan sebelum checklist menjadi Lengkap.',
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
     * Detail pelaksana Surat Tugas untuk popup konfirmasi Laporan Perjalanan.
     */
    public function laporanPelaksana($id)
    {
        $checklist = SpjChecklist::with([
            'suratTugasDetail.pelaksanas',
            'travelReportPelaksanas',
        ])->findOrFail($id);

        $reportStatuses = $checklist->travelReportPelaksanas->keyBy('surat_tugas_pelaksana_id');

        $pelaksanas = $checklist->suratTugasDetail && $checklist->suratTugasDetail->pelaksanas
            ? $checklist->suratTugasDetail->pelaksanas->map(fn ($p) => [
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

        $checklist = SpjChecklist::with('suratTugasDetail.pelaksanas')->findOrFail($id);
        if ((int) $checklist->id !== (int) $request->input('checklist_id')) {
            return response()->json(['success' => false, 'message' => 'Checklist tidak cocok.'], 422);
        }

        if ($checklist->suratTugasDetail && $checklist->suratTugasDetail->pelaksanas->count() > 0) {
            $statuses = $request->input('report_status', []);
            foreach ($checklist->suratTugasDetail->pelaksanas as $pelaksana) {
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
        if (! $checklist->suratTugasDetail || $checklist->suratTugasDetail->pelaksanas->isEmpty()) {
            return true;
        }

        $pelaksanaIds = $checklist->suratTugasDetail->pelaksanas->pluck('id');
        $sudah = TravelReportPelaksana::where('checklist_id', $checklist->id)
            ->whereIn('surat_tugas_pelaksana_id', $pelaksanaIds)
            ->where('status', TravelReportPelaksana::STATUS_SUDAH)
            ->count();

        return $sudah >= $pelaksanaIds->count();
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
