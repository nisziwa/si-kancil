<?php
namespace Database\Seeders;
use App\Models\ExpenseType;
use Illuminate\Database\Seeder;
class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nama' => 'Honor', 'kode' => 'HONOR', 'keterangan' => 'Honor petugas pendataan'],
            ['nama' => 'Translok', 'kode' => 'TRANSLOK', 'keterangan' => 'Transport lokal'],
            ['nama' => 'Translok Daerah Sulit', 'kode' => 'TRANSLOK_DS', 'keterangan' => 'Transport lokal daerah sulit'],
            ['nama' => 'Perjalanan Dinas', 'kode' => 'PERJADIN', 'keterangan' => 'Perjalanan dinas'],
            ['nama' => 'Meeting Kantor', 'kode' => 'MEETING_KTR', 'keterangan' => 'Meeting dalam kantor'],
            ['nama' => 'Meeting Luar Kantor', 'kode' => 'MEETING_LK', 'keterangan' => 'Meeting luar kantor'],
        ];
        foreach ($types as $type) {
            ExpenseType::firstOrCreate(['kode' => $type['kode']], $type);
        }
    }
}