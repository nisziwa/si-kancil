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
     * Aturan lapangan wajib (bukan transisi) untuk sebuah status tujuan.
     *
     * Satu-satunya tempat mendefinisikan apakah sebuah status membutuhkan
     * data tambahan. Dipakai bersama oleh dropdown, kanban, dan bulk agar
     * seluruh jalur menerapkan aturan yang sama.
     *
     * @return array<int,string> daftar aturan {field => pesan user-friendly}
     */
    protected function requiredFields(string $newStatus): array
    {
        $fields = [];

        if ($newStatus === 'Selesai') {
            $fields['tanggal_selesai_spj'] = 'Lengkapi tanggal selesai SPJ terlebih dahulu.';
        }

        return $fields;
    }

    /**
     * Validasi transisi & lapangan untuk satu FPA.
     *
     * @param  array<string,mixed>  $extra  nilai lapangan yang dikirim (mis. tanggal_selesai_spj, catatan)
     * @return array{ok: bool, errors: array<int,string>, new_status: string, changed: bool}
     */
    public function validate(FpaRequest $fpa, string $newStatus, array $extra = []): array
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
                $errors[] = 'Buat nomor FPA terlebih dahulu.';
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

        // Validasi lapangan wajib secara terpusat (konsisten untuk semua jalur).
        // Tanggal selesai SPJ boleh dikosongkan oleh jalur kanban/bulk yang
        // mengisi otomatis hari ini (menandai _auto_field_), namun dropdown
        // wajib mengisinya secara eksplisit.
        foreach ($this->requiredFields($newStatus) as $field => $pesan) {
            $terisi = trim((string) ($extra[$field] ?? '')) !== '';
            $autoSet = (bool) ($extra['_auto_field_'.$field] ?? false);
            if (! $terisi && ! $autoSet) {
                return [
                    'ok' => false,
                    'errors' => [$pesan],
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
     * Apakah sebuah status membutuhkan nilai lapangan tambahan yang belum dipenuhi.
     * Tanpa autofill (mis. validasi kanban/bulk yang tidak mengisi tanggal),
     * tanggal selesai tetap dianggap terpenuhi karena otomatis diisi hari ini.
     */
    public function missingFieldMessages(FpaRequest $fpa, string $newStatus, array $extra = []): array
    {
        if (! $this->transisiDiperbolehkan($fpa, $newStatus)) {
            return ["Transisi tidak diperbolehkan: {$fpa->status_spj} → {$newStatus}."];
        }

        $messages = [];

        foreach ($this->requiredFields($newStatus) as $field => $pesan) {
            $kosong = trim((string) ($extra[$field] ?? '')) === '';
            $autofill = ($newStatus === 'Selesai' && $field === 'tanggal_selesai_spj');
            if ($kosong && ! $autofill) {
                $messages[] = $pesan;
            }
        }

        return $messages;
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
