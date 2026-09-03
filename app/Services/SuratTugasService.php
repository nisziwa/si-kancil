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
     * @param array<string> $missing
     */
    public static function completenessMessage(array $missing): string
    {
        if ($missing === []) {
            return '';
        }

        return 'Surat Tugas belum lengkap. Lengkapi ' . implode(', ', $missing) . '.';
    }

    /**
     * Pesan kelengkapan lengkap untuk sebuah checklist (jalur kanban).
     */
    public static function completenessMessageForChecklist(SpjChecklist $checklist): string
    {
        return self::completenessMessage(self::missingRequirementsForChecklist($checklist));
    }
}
