<?php

namespace App\Services;

use App\Models\SpjChecklist;

/**
 * Validasi sentral kelengkapan Surat Tugas.
 *
 * Status checklist "Surat Tugas" hanya boleh menjadi "Lengkap" jika
 * Nomor Surat Tugas, Tanggal Surat Tugas, Isi Tugas, dan minimal 1
 * Daftar Pelaksana tersedia.
 *
 * Dipakai bersama oleh dropdown status (SpjChecklistController) dan
 * kanban drag-and-drop (ChecklistKanbanController) agar logic tidak
 * terduplikasi.
 */
class SuratTugasService
{
    /**
     * Pesan popup Konfirmasi Surat Tugas (kanban, drag Surat Tugas sendiri ke Lengkap).
     */
    public const ST_INCOMPLETE_MESSAGE = 'Surat Tugas belum lengkap. Lengkapi Nomor Surat Tugas, Tanggal Surat Tugas, Isi Tugas, dan minimal 1 Pelaksana.';

    /**
     * Pesan popup Konfirmasi {dokumen dependen} ketika Surat Tugas belum lengkap
     * (kanban & dropdown untuk Laporan Perjalanan / Pengeluaran Riil / Superkendis).
     */
    public const ST_DEPENDENT_BLOCK_MESSAGE = 'Surat Tugas belum lengkap. Lengkapi Nomor Surat Tugas, Tanggal Surat Tugas, Isi Tugas, dan minimal 1 Pelaksana terlebih dahulu.';

    /**
     * Cek apakah sebuah checklist merupakan dokumen Surat Tugas.
     */
    public static function isSuratTugas(SpjChecklist $checklist): bool
    {
        return str_contains((string) $checklist->nama_dokumen, 'Surat Tugas');
    }

    /**
     * Cek kelengkapan berbasis data persist di DB (dipakai jalur kanban).
     */
    public static function isComplete(SpjChecklist $checklist): bool
    {
        return self::missingRequirementsForChecklist($checklist) === [];
    }

    /**
     * Ambil requirement hilang dari sebuah checklist (jalur kanban).
     *
     * @return array<string>
     */
    public static function missingRequirementsForChecklist(SpjChecklist $checklist): array
    {
        $detail = $checklist->suratTugasDetail;

        $pelaksana = $detail
            ? $detail->pelaksanas->pluck('nama_pelaksana')->all()
            : [];

        return self::missingRequirementsFromFields(
            $detail->nomor_surat_tugas ?? '',
            $detail->tanggal_surat_tugas ?? null,
            $detail->isi_tugas ?? '',
            $pelaksana
        );
    }

    /**
     * Requirement yang belum terpenuhi berdasarkan field input form
     * (dipakai jalur dropdown sebelum data disimpan).
     *
     * @return array<string> e.g. ['Nomor Surat Tugas', 'Tanggal Surat Tugas', ...]
     */
    public static function missingRequirementsFromFields($nomor, $tanggal, $isi, array $namaPelaksana): array
    {
        $missing = [];

        if (trim((string) $nomor) === '') {
            $missing[] = 'Nomor Surat Tugas';
        }

        if (trim((string) $tanggal) === '') {
            $missing[] = 'Tanggal Surat Tugas';
        }

        if (trim((string) $isi) === '') {
            $missing[] = 'Isi Tugas';
        }

        $pelaksana = array_values(array_filter(array_map('trim', $namaPelaksana), fn ($n) => $n !== ''));
        if (count($pelaksana) < 1) {
            $missing[] = 'minimal 1 Pelaksana';
        }

        return $missing;
    }

    /**
     * Bangun pesan kelengkapan dari daftar requirement yang hilang.
     *
     * @param  array<string>  $missing
     */
    public static function completenessMessage(array $missing): string
    {
        if ($missing === []) {
            return '';
        }

        return 'Surat Tugas belum lengkap. Lengkapi '.implode(', ', $missing).'.';
    }

    /**
     * Pesan kelengkapan lengkap untuk sebuah checklist (jalur kanban).
     */
    public static function completenessMessageForChecklist(SpjChecklist $checklist): string
    {
        return self::completenessMessage(self::missingRequirementsForChecklist($checklist));
    }

    /**
     * Checklist "Surat Tugas" pada request yang sama (dengan detail & pelaksana).
     */
    public static function forRequest(SpjChecklist $checklist): ?SpjChecklist
    {
        return SpjChecklist::where('request_id', $checklist->request_id)
            ->where('nama_dokumen', 'like', '%Surat Tugas%')
            ->with('suratTugasDetail.pelaksanas')
            ->first();
    }

    /**
     * Dokumen yang bergantung pada kelengkapan Surat Tugas.
     */
    public static function isDependentDocument(string $namaDokumen): bool
    {
        return str_contains($namaDokumen, 'Laporan Perjalanan')
            || str_contains($namaDokumen, 'Pengeluaran Riil');
    }

    /**
     * Perpindahan status dokumen dependen harus diblokir atau tidak.
     *
     * - ST "Belum Ada": blokir semua perpindahan.
     * - ST "Belum Lengkap": dokumen dependen hanya boleh berstatus "Belum Lengkap".
     * - ST "Lengkap"/"Perlu Perbaikan": validasi normal.
     */
    public static function dependentMoveBlocked(?SpjChecklist $stChecklist, ?string $oldStatus, ?string $newStatus): bool
    {
        if (! $stChecklist) {
            return false;
        }

        return match ($stChecklist->status) {
            'Belum Ada' => $oldStatus !== $newStatus,
            'Belum Lengkap' => $oldStatus !== $newStatus && $newStatus !== 'Belum Lengkap',
            default => false,
        };
    }
}
