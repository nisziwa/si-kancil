<?php

namespace App\Services;

use App\Models\ChecklistHistory;
use App\Models\Request as FpaRequest;
use App\Models\RequestStatusHistory;
use App\Models\SpjChecklist;
use App\Models\TravelReportPelaksana;
use Illuminate\Support\Facades\Auth;

/**
 * Gate validasi tunggal untuk perpindahan status checklist dokumen
 * (kanban single, kanban bulk, dan dropdown).
 *
 * Menggabungkan:
 * - gate kelengkapan Surat Tugas terhadap dokumen dependen (SuratTugasService);
 * - validasi Dokumentasi terhadap Laporan Perjalanan;
 * - kelengkapan pengumpulan Laporan Perjalanan;
 * - kebutuhan konfirmasi SPJ ketika dokumen dipindah ke "Perlu Perbaikan"
 *   saat status SPJ masih "Dikirim ke PPK".
 */
class ChecklistStatusGate
{
    public const CODE_ST_SELF_INCOMPLETE = 'st_self_incomplete';
    public const CODE_ST_DEPENDENT = 'st_dependent';
    public const CODE_LAPORAN_NOT_COLLECTED = 'laporan_not_collected';
    public const CODE_DOKUMENTASI_BELUM_LENGKAP = 'dokumentasi_belum_lengkap';
    public const CODE_SPJ_NEEDS_PERBAIKAN = 'spj_needs_perbaikan';
    public const CODE_SPJ_SELESAI = 'spj_selesai';

    /**
     * Pesan popup: SPJ sudah Selesai, checklist dokumen tidak bisa dipindah lagi.
     */
    public const SPJ_SELESAI_BLOCK_MESSAGE = 'Status SPJ sudah Selesai, checklist dokumen tidak dapat dipindahkan.';

    /**
     * Pesan popup: Dokumentasi Belum Lengkap membatasi Laporan Perjalanan.
     */
    public const DOKUMENTASI_BLOCK_MESSAGE = 'Dokumentasi belum lengkap, Laporan Perjalanan maksimal berstatus "Belum Lengkap". Lengkapi Dokumentasi terlebih dahulu.';

    /**
     * Pesan popup konfirmasi SPJ ketika dokumen dipindah ke "Perlu Perbaikan"
     * saat status SPJ masih "Dikirim ke PPK".
     */
    public const SPJ_PERBAIKAN_CONFIRM_MESSAGE = 'Ada dokumen yang perlu diperbaiki. Status SPJ akan diubah menjadi Perbaikan.';

    /**
     * Alasan memblokir (atau memerlukan konfirmasi) untuk sebuah perpindahan status.
     *
     * @return array{code: string, checklist_id: int, message: string, not_collected?: int}|null
     */
    public static function blockedReason(SpjChecklist $checklist, string $newStatus, ?FpaRequest $fpa = null): ?array
    {
        if ($checklist->status === $newStatus) {
            return null;
        }

        $fpa = $fpa ?? $checklist->request;

        // SPJ sudah Selesai: checklist dokumen tidak boleh dipindahkan ke status apa pun.
        if ($fpa && $fpa->status_spj === 'Selesai') {
            return [
                'code' => self::CODE_SPJ_SELESAI,
                'checklist_id' => $checklist->id,
                'message' => self::SPJ_SELESAI_BLOCK_MESSAGE,
            ];
        }

        // Gate Surat Tugas untuk dokumen dependen (Laporan / Pengeluaran Riil).
        if (SuratTugasService::isDependentDocument($checklist->nama_dokumen)) {
            $stChecklist = SuratTugasService::forRequest($checklist);
            if (SuratTugasService::dependentMoveBlocked($stChecklist, $checklist->status, $newStatus)) {
                return [
                    'code' => self::CODE_ST_DEPENDENT,
                    'checklist_id' => $stChecklist->id ?? $checklist->id,
                    'message' => SuratTugasService::ST_DEPENDENT_BLOCK_MESSAGE,
                ];
            }
        }

        // Gate Dokumentasi: Laporan Perjalanan tidak boleh melampaui "Belum Lengkap"
        // selama Dokumentasi berstatus "Belum Lengkap".
        if (self::isLaporanPerjalanan($checklist->nama_dokumen)
            && in_array($newStatus, ['Lengkap', 'Perlu Perbaikan'], true)) {
            $dokumentasi = self::dokumentasiFor($checklist);
            if ($dokumentasi && $dokumentasi->status === 'Belum Lengkap') {
                return [
                    'code' => self::CODE_DOKUMENTASI_BELUM_LENGKAP,
                    'checklist_id' => $dokumentasi->id,
                    'message' => self::DOKUMENTASI_BLOCK_MESSAGE,
                ];
            }
        }

        // Laporan Perjalanan hanya boleh "Lengkap" bila seluruh pelaksana mengumpulkan.
        if ($newStatus === 'Lengkap' && self::isLaporanPerjalanan($checklist->nama_dokumen)) {
            $notCollected = self::notCollectedCount($checklist);
            if ($notCollected > 0) {
                return [
                    'code' => self::CODE_LAPORAN_NOT_COLLECTED,
                    'checklist_id' => $checklist->id,
                    'not_collected' => $notCollected,
                    'message' => 'Terdapat '.$notCollected.' pelaksana yang belum mengumpulkan laporan perjalanan.',
                ];
            }
        }

        // Surat Tugas hanya boleh "Lengkap" bila isiannya lengkap.
        if ($newStatus === 'Lengkap'
            && SuratTugasService::isSuratTugas($checklist)
            && ! SuratTugasService::isComplete($checklist)) {
            return [
                'code' => self::CODE_ST_SELF_INCOMPLETE,
                'checklist_id' => $checklist->id,
                'message' => SuratTugasService::ST_INCOMPLETE_MESSAGE,
            ];
        }

        // Dokumen dipindah ke "Perlu Perbaikan" saat SPJ masih "Dikirim ke PPK"
        // -> memerlukan konfirmasi (SPJ ikut diubah menjadi Perbaikan).
        if (self::spjNeedsPerbaikan($fpa, $checklist, $newStatus)) {
            return [
                'code' => self::CODE_SPJ_NEEDS_PERBAIKAN,
                'checklist_id' => $checklist->id,
                'message' => self::SPJ_PERBAIKAN_CONFIRM_MESSAGE,
            ];
        }

        return null;
    }

    /**
     * Apakah memindahkan checklist ke "Perlu Perbaikan" mengharuskan status SPJ
     * ikut diubah menjadi "Perbaikan" (saat SPJ berstatus "Dikirim ke PPK").
     */
    public static function spjNeedsPerbaikan(?FpaRequest $fpa, SpjChecklist $checklist, string $newStatus): bool
    {
        return $newStatus === 'Perlu Perbaikan'
            && $checklist->status !== 'Perlu Perbaikan'
            && $fpa
            && $fpa->status_spj === 'Dikirim ke PPK';
    }

    /**
     * Checklist "Dokumentasi" pada request yang sama.
     */
    public static function dokumentasiFor(SpjChecklist $checklist): ?SpjChecklist
    {
        return SpjChecklist::where('request_id', $checklist->request_id)
            ->where('nama_dokumen', 'Dokumentasi')
            ->first();
    }

    public static function isLaporanPerjalanan(string $namaDokumen): bool
    {
        return str_contains($namaDokumen, 'Laporan Perjalanan');
    }

    /**
     * Jumlah pelaksana Surat Tugas yang belum mengumpulkan Laporan Perjalanan.
     */
    public static function notCollectedCount(SpjChecklist $checklist): int
    {
        $st = self::stDetailFor($checklist);
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
     * Seluruh pelaksana Surat Tugas sudah mengumpulkan Laporan Perjalanan.
     */
    public static function allTravelReportCollected(SpjChecklist $checklist): bool
    {
        $st = self::stDetailFor($checklist);
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
     * SuratTugasDetail dari checklist "Surat Tugas" pada request yang sama.
     */
    public static function stDetailFor(SpjChecklist $checklist)
    {
        $stChecklist = SuratTugasService::forRequest($checklist);

        return $stChecklist ? $stChecklist->suratTugasDetail : null;
    }

    /**
     * Terapkan status checklist + catat riwayat (dipakai jalur kanban & dropdown).
     */
    public static function applyChecklistStatus(SpjChecklist $checklist, string $newStatus, ?string $catatan = null, ?int $userId = null): void
    {
        $oldStatus = $checklist->status;
        if ($oldStatus === $newStatus) {
            return;
        }

        $checklist->status = $newStatus;
        $checklist->save();

        ChecklistHistory::create([
            'checklist_id' => $checklist->id,
            'status_lama' => $oldStatus,
            'status_baru' => $newStatus,
            'catatan' => $catatan,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Ubah status SPJ menjadi "Perbaikan" + catat riwayat bila sebelumnya "Dikirim ke PPK".
     *
     * @return bool apakah status SPJ benar-benar diubah
     */
    public static function applySpjPerbaikan(FpaRequest $fpa, ?string $catatan = null, ?int $userId = null): bool
    {
        if ($fpa->status_spj === 'Perbaikan') {
            return false;
        }

        $oldStatus = $fpa->status_spj;
        $fpa->status_spj = 'Perbaikan';
        $fpa->save();

        RequestStatusHistory::create([
            'request_id' => $fpa->id,
            'status_lama' => $oldStatus,
            'status_baru' => 'Perbaikan',
            'catatan' => $catatan,
            'user_id' => $userId ?? Auth::id(),
        ]);

        return true;
    }
}