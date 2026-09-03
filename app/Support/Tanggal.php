<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * Formatter tanggal Indonesia terpusat.
 *
 * Standar tampilan: "dd MMMM yyyy" (mis. "02 September 2026").
 * Database tetap menyimpan format "Y-m-d"; helper ini hanya dipakai
 * untuk MENAMPILKAN tanggal agar konsisten di seluruh tampilan (Blade)
 * dan dokumen yang digenerate (DOCX/PDF). Jangan dipakai untuk menyimpan.
 */
class Tanggal
{
    public const BULAN_INDONESIA = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Format tanggal tampilan Indonesia "dd MMMM yyyy".
     *
     * @param  CarbonInterface|DateTimeInterface|string|null  $value
     */
    public static function format($value, string $fallback = '-'): string
    {
        $ts = self::toTimestamp($value);
        if ($ts === null) {
            return $fallback;
        }

        return date('d', $ts).' '.self::BULAN_INDONESIA[(int) date('n', $ts)].' '.date('Y', $ts);
    }

    /**
     * Format tanggal + jam Indonesia "dd MMMM yyyy HH:mm".
     * (Jam tanpa detik agar ringkas namun tetap non-ambigu.)
     *
     * @param  CarbonInterface|DateTimeInterface|string|null  $value
     */
    public static function formatDateTime($value, string $fallback = '-'): string
    {
        $date = self::format($value, $fallback);
        if ($date === $fallback) {
            return $fallback;
        }

        $ts = self::toTimestamp($value);

        return $date.' '.date('H:i', $ts);
    }

    /**
     * Konversi nilai menjadi unix timestamp, atau null bila tidak dapat diparse.
     *
     * @param  CarbonInterface|DateTimeInterface|string|null  $value
     */
    protected static function toTimestamp($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface || $value instanceof CarbonInterface) {
            return $value->getTimestamp();
        }

        $ts = strtotime((string) $value);
        if ($ts === false) {
            return null;
        }

        return $ts;
    }
}
