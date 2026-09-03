<?php

namespace Database\Seeders;

use App\Models\MasterAkun;
use App\Models\MasterKegiatan;
use App\Models\MasterKomponen;
use App\Models\MasterOutput;
use App\Models\MasterProgram;
use App\Models\MasterRincianPok;
use App\Models\MasterSubOutput;
use Illuminate\Database\Seeder;

class PokSeeder extends Seeder
{
    public function run(): void
    {
        $program = MasterProgram::updateOrCreate(
            ['kode_program' => '054.01.GG'],
            ['nama_program' => 'Program Penyediaan dan Pelayanan Informasi Statistik (GG)']
        );

        $kegiatan = MasterKegiatan::updateOrCreate(
            ['kode_kegiatan' => '2897'],
            [
                'program_id' => $program->id,
                'nama_kegiatan' => 'Pengelolaan Data Statistik Sektoral',
            ]
        );

        $output = MasterOutput::updateOrCreate(
            ['kode_output' => '2897.BMA'],
            [
                'kegiatan_id' => $kegiatan->id,
                'nama_output' => 'Layanan Data dan Informasi Statistik Sektoral',
            ]
        );

        $subOutput = MasterSubOutput::updateOrCreate(
            ['kode_sub_output' => '2897.BMA.005'],
            [
                'output_id' => $output->id,
                'nama_sub_output' => 'Layanan Data dan Informasi Statistik Sektoral Bidang Produksi',
            ]
        );

        $komponen = MasterKomponen::updateOrCreate(
            ['kode_komponen' => '005'],
            [
                'sub_output_id' => $subOutput->id,
                'nama_komponen' => 'Komponen Kegiatan Pendataan',
            ]
        );

        $akun = MasterAkun::updateOrCreate(
            ['kode_akun' => '521213'],
            ['nama_akun' => 'Belanja Honor Operasional Satuan Kerja']
        );

        MasterRincianPok::updateOrCreate(
            ['rincian' => 'honor petugas pendataan lapangan survei captive power'],
            [
                'program_id' => $program->id,
                'kegiatan_id' => $kegiatan->id,
                'output_id' => $output->id,
                'sub_output_id' => $subOutput->id,
                'komponen_id' => $komponen->id,
                'akun_id' => $akun->id,
            ]
        );
    }
}
