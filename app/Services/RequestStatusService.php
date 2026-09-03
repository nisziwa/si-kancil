<?php

namespace App\Services;

use App\Models\Request as FpaRequest;

/**
 * Validasi terpusat transisi status FPA / SPJ.
 *
 * Dipakai bersama oleh:
 * - form dropdown (RequestStatusController@update)
 * - drag kanban (RequestStatusController@updateAjax)
 * - bulk kanban (RequestStatusController@bulk)
 */
class RequestStatusService
{
    /**
     * Peta transisi status SPJ yang diperbolehkan.
     * Perbaikan bersifat opsional (tidak wajib dilewati).
     */
    public const TRANSITIONS = [
        'Persiapan' => ['Dikirim ke PPK'],
        'Dikirim ke PPK' => ['Selesai', 'Perbaikan'],
        'Perbaikan' => ['Dikirim ke PPK', 'Selesai'],
        'Selesai' => [],
    ];

    /**
     * Apakah sebuah transisi diperbolehkan dari status saat ini.
     */
    public function transisiDiperbolehkan(FpaRequest $fpa, string $newStatus): bool
    {
        if ($fpa->status_spj === $newStatus) {
            return true;
        }

        $allowed = self::TRANSITIONS[$fpa->status_spj] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Validasi transisi untuk satu FPA.
     *
     * @return array{ok: bool, errors: array<int,string>, new_status: string, changed: bool}
     */
    public function validate(FpaRequest $fpa, string $newStatus): array
    {
        $changed = $fpa->status_spj !== $newStatus;

        if (! $this->transisiDiperbolehkan($fpa, $newStatus)) {
            return [
                'ok' => false,
                'errors' => ["Transisi tidak diperbolehkan: {$fpa->status_spj} → {$newStatus}."],
                'new_status' => $newStatus,
                'changed' => $changed,
            ];
        }

        if ($newStatus === 'Dikirim ke PPK') {
            $errors = [];

            if (! $fpa->has_nomor_fpa) {
                $errors[] = 'Nomor FPA belum tersedia.';
            }

            if (! $fpa->mandatory_checklist_complete) {
                $errors[] = 'Checklist dokumen belum lengkap.';
            }

            if ($errors !== []) {
                $pesan = 'SPJ belum dapat dikirim ke PPK.';
                return [
                    'ok' => false,
                    'errors' => array_map(fn ($e) => "$pesan $e", $errors),
                    'new_status' => $newStatus,
                    'changed' => $changed,
                ];
            }
        }

        return [
            'ok' => true,
            'errors' => [],
            'new_status' => $newStatus,
            'changed' => $changed,
        ];
    }

    /**
     * Terapkan perubahan status (termasuk tanggal terkait).
     */
    public function apply(FpaRequest $fpa, string $newStatus, array $extra = []): FpaRequest
    {
        $fpa->status_spj = $newStatus;

        if ($newStatus === 'Dikirim ke PPK') {
            $fpa->tanggal_kirim_ppk = $extra['tanggal_kirim_ppk'] ?? now()->format('Y-m-d');
        }

        if ($newStatus === 'Selesai') {
            $fpa->tanggal_selesai_spj = $extra['tanggal_selesai_spj'] ?? now()->format('Y-m-d');
        }

        $fpa->save();

        return $fpa;
    }
}
