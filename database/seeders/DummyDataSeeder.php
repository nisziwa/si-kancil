<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Models\ExpenseType;
use App\Models\RealExpenseDetail;
use App\Models\Request as FpaRequest;
use App\Models\RequestStatusHistory;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\Template;
use App\Models\TravelDetail;
use App\Models\TravelReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        $honorType = ExpenseType::where('kode', 'HONOR')->first();
        $perjadinType = ExpenseType::where('kode', 'PERJADIN')->first();
        $translokType = ExpenseType::where('kode', 'TRANSLOK')->first();
        $meetingKtrType = ExpenseType::where('kode', 'MEETING_KTR')->first();
        $meetingLkType = ExpenseType::where('kode', 'MEETING_LK')->first();

        // 1. Template Repository Dummies
        $dummyTemplates = [
            ['nama_template' => 'Format Standar KAK Honor Petugas', 'kategori' => 'KAK', 'versi' => 'v2026.1'],
            ['nama_template' => 'Format Surat Tugas Perjalanan Dinas Luar Kota', 'kategori' => 'Surat Tugas', 'versi' => 'v2026.1'],
            ['nama_template' => 'Format Form Laporan Perjalanan Dinas Singkat', 'kategori' => 'Laporan Perjalanan', 'versi' => 'v2026.1'],
            ['nama_template' => 'Format Lembar Visum Perjalanan Dinas', 'kategori' => 'Visum', 'versi' => 'v2026.1'],
            ['nama_template' => 'Format Surat Pernyataan Non Kendaraan Dinas', 'kategori' => 'Superkendis', 'versi' => 'v2026.1'],
            ['nama_template' => 'Format Cover & Berkas SPJ Lengkap', 'kategori' => 'Dokumen SPJ', 'versi' => 'v2026.1'],
        ];

        foreach ($dummyTemplates as $tpl) {
            Template::firstOrCreate(
                ['nama_template' => $tpl['nama_template']],
                [
                    'kategori' => $tpl['kategori'],
                    'versi' => $tpl['versi'],
                    'status_aktif' => true,
                ]
            );
        }

        // 2. FPA Sample Data across different statuses
        $sampleRequests = [
            [
                'nomor_fpa' => 'FPA/2026/08/001',
                'deskripsi_permintaan' => 'Honor Petugas Survei Industri Triwulan III',
                'expense_type' => $honorType,
                'periode' => 'Agustus 2026',
                'tanggal_mulai' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'lokasi' => 'Kabupaten Sukamaju',
                'deadline_spj' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'status_spj' => 'Persiapan',
            ],
            [
                'nomor_fpa' => 'FPA/2026/08/002',
                'deskripsi_permintaan' => 'Perjalanan Dinas Koordinasi Evaluasi SAKTI ke Kanwil',
                'expense_type' => $perjadinType,
                'periode' => 'Agustus 2026',
                'tanggal_mulai' => Carbon::now()->subDays(15)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(12)->format('Y-m-d'),
                'lokasi' => 'Ibukota Provinsi',
                'deadline_spj' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'status_spj' => 'Dikirim ke PPK',
                'tanggal_kirim_ppk' => Carbon::now()->subDays(2)->format('Y-m-d'),
            ],
            [
                'nomor_fpa' => 'FPA/2026/08/003',
                'deskripsi_permintaan' => 'Rapat Koordinasi Penyusunan Publikasi Daerah Dalam Angka',
                'expense_type' => $meetingKtrType,
                'periode' => 'Agustus 2026',
                'tanggal_mulai' => Carbon::now()->subDays(20)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(19)->format('Y-m-d'),
                'lokasi' => 'Ruang Rapat Utama BPS',
                'deadline_spj' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'status_spj' => 'Selesai',
                'tanggal_selesai_spj' => Carbon::now()->subDays(3)->format('Y-m-d'),
            ],
            [
                'nomor_fpa' => 'FPA/2026/08/004',
                'deskripsi_permintaan' => 'Transport Lokal Pengawasan Lapangan Sensus Pertanian',
                'expense_type' => $translokType,
                'periode' => 'Agustus 2026',
                'tanggal_mulai' => Carbon::now()->subDays(7)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->subDays(4)->format('Y-m-d'),
                'lokasi' => 'Kecamatan Muara',
                'deadline_spj' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'status_spj' => 'Perbaikan',
            ],
            [
                'nomor_fpa' => 'FPA/2026/08/005',
                'deskripsi_permintaan' => 'Focus Group Discussion Hasil Analisis Data Statistik Sektoral',
                'expense_type' => $meetingLkType,
                'periode' => 'Agustus 2026',
                'tanggal_mulai' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->addDays(4)->format('Y-m-d'),
                'lokasi' => 'Hotel Grand Mercure',
                'deadline_spj' => Carbon::now()->addDays(15)->format('Y-m-d'),
                'status_spj' => 'Persiapan',
            ],
            [
                'nomor_fpa' => 'FPA/2026/08/006',
                'deskripsi_permintaan' => 'Pendataan Lapangan Survei Harga Konsumen Mingguan',
                'expense_type' => $translokType,
                'periode' => 'Agustus 2026',
                'tanggal_mulai' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'tanggal_selesai' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'lokasi' => 'Pasar Sentral',
                'deadline_spj' => Carbon::now()->addDays(8)->format('Y-m-d'),
                'status_spj' => 'Persiapan',
            ],
        ];

        foreach ($sampleRequests as $data) {
            $fpa = FpaRequest::firstOrCreate(
                ['nomor_fpa' => $data['nomor_fpa']],
                [
                    'deskripsi_permintaan' => $data['deskripsi_permintaan'],
                    'jenis_pengeluaran_id' => $data['expense_type']->id,
                    'periode' => $data['periode'],
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'tanggal_selesai' => $data['tanggal_selesai'],
                    'lokasi' => $data['lokasi'],
                    'deadline_spj' => $data['deadline_spj'],
                    'status_spj' => $data['status_spj'],
                    'tanggal_kirim_ppk' => $data['tanggal_kirim_ppk'] ?? null,
                    'tanggal_selesai_spj' => $data['tanggal_selesai_spj'] ?? null,
                    'user_id' => $user->id,
                ]
            );

            // Log status history
            RequestStatusHistory::firstOrCreate(
                [
                    'request_id' => $fpa->id,
                    'status_baru' => $data['status_spj'],
                ],
                [
                    'status_lama' => 'Persiapan',
                    'catatan' => 'Pencatatan status awal FPA',
                    'user_id' => $user->id,
                ]
            );

            // Generate checklists jika belum ada
            if ($fpa->checklists()->count() === 0) {
                $templates = DocumentTemplate::where('expense_type_id', $fpa->jenis_pengeluaran_id)
                    ->orderBy('urutan')
                    ->get();

                foreach ($templates as $idx => $t) {
                    $status = 'Belum Ada';
                    if ($fpa->status_spj === 'Selesai') {
                        $status = 'Lengkap';
                    } elseif ($fpa->status_spj === 'Dikirim ke PPK') {
                        $status = 'Lengkap';
                    } elseif ($fpa->status_spj === 'Perbaikan') {
                        $status = $idx === 0 ? 'Perlu Perbaikan' : 'Lengkap';
                    }

                    $chk = SpjChecklist::create([
                        'request_id' => $fpa->id,
                        'nama_dokumen' => $t->nama_dokumen,
                        'status' => $status,
                        'urutan' => $t->urutan,
                        'catatan' => $status === 'Perlu Perbaikan' ? 'Kuitansi perlu tanda tangan asli' : null,
                    ]);

                    // Seed detail jika Surat Tugas
                    if (str_contains($t->nama_dokumen, 'Surat Tugas')) {
                        $stDetail = SuratTugasDetail::create([
                            'checklist_id' => $chk->id,
                            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
                            'tanggal_surat_tugas' => $fpa->tanggal_mulai,
                            'pelaksana' => 'Budi Santoso, Siti Rahma',
                            'isi_tugas' => 'Pelaksanaan tugas penugasan lapangan kegiatan '.$fpa->deskripsi_permintaan,
                        ]);

                        $pelaksanas = [['Budi Santoso', 1], ['Siti Rahma', 2]];
                        foreach ($pelaksanas as $p) {
                            SuratTugasPelaksana::create([
                                'surat_tugas_detail_id' => $stDetail->id,
                                'nama_pelaksana' => $p[0],
                                'nomor_surat' => 'B-1027.'.$p[1].'/75040/KP.650/2026',
                                'urutan' => $p[1],
                            ]);
                        }
                    }

                    // Seed detail jika SPD
                    if (str_contains($t->nama_dokumen, 'SPD')) {
                        TravelDetail::create([
                            'checklist_id' => $chk->id,
                            'nomor_spd' => 'SPD/0'.$fpa->id.'/2026',
                            'nama_pelaksana' => 'Budi Santoso',
                            'maksud_perjalanan' => $fpa->deskripsi_permintaan,
                            'tempat_berangkat' => 'Kantor BPS',
                            'tempat_tujuan' => $fpa->lokasi ?: 'Lokasi Kegiatan',
                            'tanggal_berangkat' => $fpa->tanggal_mulai,
                            'tanggal_kembali' => $fpa->tanggal_selesai,
                            'transportasi' => 'Kendaraan Dinas / Darat',
                        ]);
                    }

                    // Seed detail jika Pengeluaran Riil
                    if (str_contains($t->nama_dokumen, 'Pengeluaran Riil')) {
                        RealExpenseDetail::create([
                            'checklist_id' => $chk->id,
                            'nomor_surat_tugas' => 'ST/0'.$fpa->id.'/VIII/2026',
                            'tanggal_surat_tugas' => $fpa->tanggal_mulai,
                            'nama_pelaksana' => 'Budi Santoso',
                            'jabatan' => 'Statistisi Ahli Pertama',
                            'tanggal_kegiatan' => $fpa->tanggal_mulai,
                            'uraian_pengeluaran' => 'Biaya BBM & Transportasi Lapangan',
                            'jumlah_pengeluaran' => 250000,
                            'keterangan' => 'Sesuai pengeluaran riil di lapangan',
                        ]);
                    }

                    // Seed detail jika Laporan Perjalanan
                    if (str_contains($t->nama_dokumen, 'Laporan Perjalanan')) {
                        TravelReport::create([
                            'checklist_id' => $chk->id,
                            'nama_pelaksana' => 'Budi Santoso',
                            'tujuan' => $fpa->lokasi ?: 'Lokasi Kegiatan',
                            'tanggal_kegiatan' => $fpa->tanggal_selesai,
                            'uraian_kegiatan' => 'Telah selesai melaksanakan tugas '.$fpa->deskripsi_permintaan.' dengan lancar dan tertib.',
                        ]);
                    }
                }
            }
        }
    }
}
