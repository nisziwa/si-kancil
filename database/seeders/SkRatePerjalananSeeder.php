<?php

namespace Database\Seeders;

use App\Models\SkRatePerjalanan;
use Illuminate\Database\Seeder;

class SkRatePerjalananSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['kecamatan' => 'Kecamatan Muara', 'besaran_biaya_transport' => 150000, 'keterangan' => 'Kecamatan biasa, transport lokal'],
            ['kecamatan' => 'Kecamatan Sukamaju', 'besaran_biaya_transport' => 175000, 'keterangan' => 'Kecamatan biasa'],
            ['kecamatan' => 'Kecamatan Hulu Air', 'besaran_biaya_transport' => 250000, 'keterangan' => 'Kecamatan daerah sulit (translok DS)'],
            ['kecamatan' => 'Kecamatan Gunung Merah', 'besaran_biaya_transport' => 220000, 'keterangan' => 'Kecamatan daerah sulit'],
        ];

        foreach ($rates as $rate) {
            SkRatePerjalanan::firstOrCreate(['kecamatan' => $rate['kecamatan']], $rate);
        }
    }
}
