<?php
namespace Database\Seeders;
use App\Models\DocumentTemplate;
use App\Models\ExpenseType;
use Illuminate\Database\Seeder;
class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'HONOR' => ['FPA', 'KAK', 'Kuitansi BOS'],
            'TRANSLOK' => ['FPA', 'KAK', 'Surat Tugas', 'Laporan Perjalanan', 'Dokumentasi', 'Visum', 'Pengeluaran Riil + Surat Non Kendaraan Dinas'],
            'TRANSLOK_DS' => ['FPA', 'KAK', 'Surat Tugas', 'Laporan Perjalanan', 'Dokumentasi', 'Visum', 'Pengeluaran Riil + Surat Non Kendaraan Dinas'],
            'PERJADIN' => ['FPA', 'KAK', 'Surat Tugas', 'SPD/SPPD', 'Laporan Perjalanan', 'Dokumentasi', 'Visum', 'Pengeluaran Riil + Surat Non Kendaraan Dinas'],
            'MEETING_KTR' => ['FPA', 'KAK', 'Undangan Kegiatan', 'Jadwal Kegiatan', 'Surat Tugas', 'Notulensi', 'Daftar Hadir', 'Nota Konsumsi', 'Dokumentasi', 'Daftar Penerima Perlengkapan', 'Surat Non Kendaraan Dinas + Pengeluaran Riil'],
            'MEETING_LK' => ['FPA', 'KAK', 'Undangan Kegiatan', 'Jadwal Kegiatan', 'Surat Tugas', 'Notulensi', 'Daftar Hadir', 'Nota Konsumsi', 'Dokumentasi', 'Daftar Penerima Perlengkapan', 'Surat Non Kendaraan Dinas + Pengeluaran Riil'],
        ];
        DocumentTemplate::truncate();
        foreach ($templates as $kode => $docs) {
            $expenseType = ExpenseType::where('kode', $kode)->first();
            if (!$expenseType) continue;
            foreach ($docs as $urutan => $nama) {
                DocumentTemplate::create([
                    'expense_type_id' => $expenseType->id,
                    'nama_dokumen'    => $nama,
                    'is_required'     => true,
                    'urutan'          => $urutan + 1,
                ]);
            }
        }
    }
}