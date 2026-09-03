<?php

namespace Database\Seeders;

use App\Models\SkRatePerjalanan;
use Illuminate\Database\Seeder;

class SkRatePerjalananSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['kecamatan' => 'TAPA', 'ibukota_kecamatan' => 'TALUMOPATU', 'besaran_biaya_transport' => 50000],
            ['kecamatan' => 'BULANGO UTARA', 'ibukota_kecamatan' => 'BOIDU', 'besaran_biaya_transport' => 95000],
            ['kecamatan' => 'BULANGO SELATAN', 'ibukota_kecamatan' => 'TINELO AYULA', 'besaran_biaya_transport' => 55000],
            ['kecamatan' => 'BULANGO TIMUR', 'ibukota_kecamatan' => 'BULOTALANGI BARAT', 'besaran_biaya_transport' => 45000],
            ['kecamatan' => 'BULANGO ULU', 'ibukota_kecamatan' => 'MONGILLO', 'besaran_biaya_transport' => 130000],
            ['kecamatan' => 'KABILA', 'ibukota_kecamatan' => 'OLUHUTA', 'besaran_biaya_transport' => 30000],
            ['kecamatan' => 'BOTU PINGGE', 'ibukota_kecamatan' => 'TANAH PUTIH', 'besaran_biaya_transport' => 70000],
            ['kecamatan' => 'TILONGKABILA', 'ibukota_kecamatan' => 'BONGOIME', 'besaran_biaya_transport' => 25000],
            ['kecamatan' => 'SUWAWA', 'ibukota_kecamatan' => 'BOLUDAWA', 'besaran_biaya_transport' => 20000],
            ['kecamatan' => 'SUWAWA SELATAN', 'ibukota_kecamatan' => 'MOLINTOGUPO', 'besaran_biaya_transport' => 70000],
            ['kecamatan' => 'SUWAWA TIMUR', 'ibukota_kecamatan' => 'DUMBAYABULAN', 'besaran_biaya_transport' => 100000],
            ['kecamatan' => 'SUWAWA TENGAH', 'ibukota_kecamatan' => 'DUANO', 'besaran_biaya_transport' => 30000],
            ['kecamatan' => 'PINOGU', 'ibukota_kecamatan' => 'PINOGU', 'besaran_biaya_transport' => 750000],
            ['kecamatan' => 'BONEPANTAI', 'ibukota_kecamatan' => 'BILUNGALA', 'besaran_biaya_transport' => 130000],
            ['kecamatan' => 'KABILA BONE', 'ibukota_kecamatan' => 'HUANGOBOTU', 'besaran_biaya_transport' => 110000],
            ['kecamatan' => 'BONE RAYA', 'ibukota_kecamatan' => 'TOMBULILATO', 'besaran_biaya_transport' => 150000],
            ['kecamatan' => 'BONE', 'ibukota_kecamatan' => 'TALUDAA', 'besaran_biaya_transport' => 160000],
            ['kecamatan' => 'BULAWA', 'ibukota_kecamatan' => 'KAIDUNDU', 'besaran_biaya_transport' => 140000],
        ];

        foreach ($rates as $rate) {
            SkRatePerjalanan::updateOrCreate(['kecamatan' => $rate['kecamatan']], $rate);
        }
    }
}
