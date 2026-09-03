<?php

namespace App\Support;

class Terbilang
{
    protected static array $satuan = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
    ];

    protected static array $belasan = [
        'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
        'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas',
    ];

    public static function convert($number): string
    {
        $number = (int) round((float) $number);

        if ($number < 0) {
            return 'minus ' . self::convert(abs($number));
        }

        if ($number < 12) {
            return ($number < 10)
                ? self::$satuan[$number]
                : self::$belasan[$number - 10];
        }

        if ($number < 20) {
            return self::convert($number - 10) . ' belas';
        }

        if ($number < 100) {
            $puluhan = intdiv($number, 10);
            $sisa = $number % 10;
            return ($puluhan === 1 ? 'sepuluh' : self::$satuan[$puluhan] . ' puluh')
                . ($sisa > 0 ? ' ' . self::$satuan[$sisa] : '');
        }

        if ($number < 200) {
            return 'seratus' . ($number > 100 ? ' ' . self::convert($number - 100) : '');
        }

        if ($number < 1000) {
            $ratusan = intdiv($number, 100);
            $sisa = $number % 100;
            return self::$satuan[$ratusan] . ' ratus'
                . ($sisa > 0 ? ' ' . self::convert($sisa) : '');
        }

        if ($number < 1000000) {
            return self::convertThousands($number, 1000, 'ribu');
        }

        if ($number < 1000000000) {
            return self::convertThousands($number, 1000000, 'juta');
        }

        if ($number < 1000000000000) {
            return self::convertThousands($number, 1000000000, 'miliar');
        }

        return self::convertThousands($number, 1000000000000, 'triliun');
    }

    protected static function convertThousands(int $number, int $base, string $label): string
    {
        $satuan = intdiv($number, $base);

        $prefix = $satuan === 1 && $base === 1000 ? 'seribu' : self::convert($satuan) . ' ' . $label;
        $sisa = $number % $base;

        return $prefix . ($sisa > 0 ? ' ' . self::convert($sisa) : '');
    }
}
