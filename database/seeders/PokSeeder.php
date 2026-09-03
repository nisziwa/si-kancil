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
use Illuminate\Support\Facades\DB;

class PokSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $program = MasterProgram::updateOrCreate(
                ['kode_program' => '054.01.GG'],
                ['nama_program' => 'Penyediaan dan Pelayanan Informasi Statistik']
            );

            $akun = MasterAkun::updateOrCreate(
                ['kode_akun' => '521213'],
                ['nama_akun' => 'Belanja Honor Output Kegiatan']
            );

            $branches = [
                [
                    'kegiatan' => ['kode' => '8130', 'nama' => 'Penyediaan dan Pengembangan Statistik Sumber Daya Hayati'],
                    'output' => ['kode' => '8130.BMA', 'nama' => 'Publikasi/Laporan Statistik Sumber Daya Hayati'],
                    'sub_output' => ['kode' => '007', 'nama' => 'Publikasi/Laporan Statistik Tanaman Pangan'],
                    'rincian' => [
                        'Honor petugas pendataan lapangan survei KSA',
                        'Honor petugas pendataan lapangan survei SKP',
                        'Honor petugas pendataan lapangan survei Ubinan',
                    ],
                ],
                [
                    'kegiatan' => ['kode' => '8131', 'nama' => 'Penyediaan dan Pengembangan Statistik Sumber Daya Mineral dan Konstruksi'],
                    'output' => ['kode' => '8131.BMA', 'nama' => 'Publikasi/Laporan Statistik Sumber Daya Mineral dan Konstruksi'],
                    'sub_output' => ['kode' => '005', 'nama' => 'Publikasi/Laporan Statistik Sumber Daya Mineral dan Konstruksi'],
                    'rincian' => [
                        'Honor petugas pendataan lapangan survei SKGB',
                        'Honor petugas pendataan lapangan survei konstruksi',
                        'Honor petugas pendataan lapangan survei pertambangan',
                    ],
                ],
                [
                    'kegiatan' => ['kode' => '2904', 'nama' => 'Penyediaan dan Pengembangan Statistik Industri'],
                    'output' => ['kode' => '2904.BMA', 'nama' => 'Publikasi/Laporan Statistik Industri'],
                    'sub_output' => ['kode' => '006', 'nama' => 'Publikasi/Laporan Statistik Industri'],
                    'rincian' => [
                        'Honor petugas pendataan lapangan survei Captive Power',
                        'Honor petugas pendataan lapangan survei industri besar sedang',
                        'Honor petugas pendataan lapangan survei industri mikro kecil',
                    ],
                ],
            ];

            foreach ($branches as $branch) {
                $kegiatan = MasterKegiatan::updateOrCreate(
                    ['kode_kegiatan' => $branch['kegiatan']['kode']],
                    [
                        'program_id' => $program->id,
                        'nama_kegiatan' => $branch['kegiatan']['nama'],
                    ]
                );

                $output = MasterOutput::updateOrCreate(
                    ['kode_output' => $branch['output']['kode']],
                    [
                        'kegiatan_id' => $kegiatan->id,
                        'nama_output' => $branch['output']['nama'],
                    ]
                );

                $subOutput = MasterSubOutput::updateOrCreate(
                    ['kode_sub_output' => $branch['sub_output']['kode']],
                    [
                        'output_id' => $output->id,
                        'nama_sub_output' => $branch['sub_output']['nama'],
                    ]
                );

                $komponen = MasterKomponen::updateOrCreate(
                    ['kode_komponen' => '005'],
                    [
                        'sub_output_id' => $subOutput->id,
                        'nama_komponen' => 'Dukungan Penyelenggaraan Tugas dan Fungsi Unit',
                    ]
                );

                foreach ($branch['rincian'] as $rincian) {
                    MasterRincianPok::updateOrCreate(
                        ['rincian' => $rincian],
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
        });
    }
}
