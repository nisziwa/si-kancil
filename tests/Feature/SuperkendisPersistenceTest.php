<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SkRatePerjalanan;
use App\Models\SpjChecklist;
use App\Models\Superkendis;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperkendisPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected FpaRequest $fpaRequest;

    protected SpjChecklist $stChecklist;

    protected SpjChecklist $integrasiChecklist;

    protected SuratTugasDetail $stDetail;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $expenseType = ExpenseType::create([
            'nama' => 'Perjalanan Dinas',
            'kode' => 'PERJADIN',
            'is_active' => true,
        ]);

        $this->fpaRequest = FpaRequest::create([
            'nomor_fpa' => 'FPA-SKD-002',
            'deskripsi_permintaan' => 'Perjalanan Dinas Persistence',
            'jenis_pengeluaran_id' => $expenseType->id,
            'periode' => 'Triwulanan',
            'user_id' => $this->user->id,
            'status_spj' => 'Persiapan',
        ]);

        $this->stChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'Surat Tugas',
            'status' => 'Lengkap',
            'is_required' => true,
        ]);

        $this->integrasiChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'Pengeluaran Riil + Surat Non Kendaraan Dinas',
            'status' => 'Belum Ada',
            'is_required' => true,
        ]);

        $this->stDetail = SuratTugasDetail::create([
            'checklist_id' => $this->stChecklist->id,
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => 'Koordinasi data',
        ]);

        SuratTugasPelaksana::create([
            'surat_tugas_detail_id' => $this->stDetail->id,
            'nama_pelaksana' => 'Budi Santoso',
            'nomor_surat' => 'B-1027.1/75040/KP.650/2026',
            'urutan' => 1,
        ]);
        SuratTugasPelaksana::create([
            'surat_tugas_detail_id' => $this->stDetail->id,
            'nama_pelaksana' => 'Siti Rahma',
            'nomor_surat' => 'B-1027.2/75040/KP.650/2026',
            'urutan' => 2,
        ]);

        SkRatePerjalanan::create([
            'kecamatan' => 'Kecamatan Muara',
            'ibukota_kecamatan' => 'Muara',
            'besaran_biaya_transport' => 150000,
            'keterangan' => 'test',
        ]);
    }

    private function generatePelaksana(SuratTugasPelaksana $pelaksana, string $jenis = 'Pendataan Lapangan', string $format = 'docx')
    {
        return $this->actingAs($this->user)->post(
            route('requests.superkendis.generate', ['requestId' => $this->fpaRequest->id, 'pelaksanaId' => $pelaksana->id]),
            [
                'kecamatan' => 'Kecamatan Muara',
                'tanggal_perjalanan' => '2026-08-26',
                'jenis_kegiatan' => $jenis,
                'nip' => '',
                'format' => $format,
            ]
        );
    }

    public function test_generate_membuat_record_superkendis_dan_file(): void
    {
        $pelaksana = SuratTugasPelaksana::first();

        $response = $this->generatePelaksana($pelaksana, 'Pelatihan');
        $response->assertOk();

        $record = Superkendis::where('surat_tugas_pelaksana_id', $pelaksana->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('Kecamatan Muara', $record->kecamatan);
        $this->assertSame('Pelatihan', $record->jenis_kegiatan);
        $this->assertSame('PCL', $record->jabatan);
        $this->assertNotNull($record->file_docx);
        Storage::disk('public')->assertExists($record->file_docx);

        // Checklist integrasi belum Lengkap (masih ada pelaksana lain yang belum digenerate).
        $this->assertSame('Belum Ada', $this->integrasiChecklist->fresh()->status);
    }

    public function test_jenis_kegiatan_menghasilkan_jabatan_yang_benar(): void
    {
        $pelaksana = SuratTugasPelaksana::first();

        $map = [
            'Pelatihan' => 'PCL',
            'Pendataan Lapangan' => 'PCL',
            'Pengawasan Lapangan' => 'PML',
            'Supervisi Lapangan' => 'Supervisor',
        ];

        foreach ($map as $jenis => $jabatan) {
            $this->generatePelaksana($pelaksana, $jenis);
            $this->assertSame($jabatan, Superkendis::where('surat_tugas_pelaksana_id', $pelaksana->id)->first()->jabatan);
        }
    }

    public function test_generate_ulang_tidak_membuat_duplikat(): void
    {
        $pelaksana = SuratTugasPelaksana::first();

        $this->generatePelaksana($pelaksana, 'Pendataan Lapangan');
        $this->generatePelaksana($pelaksana, 'Pengawasan Lapangan');

        $this->assertSame(1, Superkendis::where('surat_tugas_pelaksana_id', $pelaksana->id)->count());
        // Nilai terbaru yang tercatat (updateOrCreate).
        $this->assertSame('Pengawasan Lapangan', Superkendis::where('surat_tugas_pelaksana_id', $pelaksana->id)->first()->jenis_kegiatan);
    }

    public function test_semua_pelaksana_generate_checklist_integrasi_jadi_lengkap(): void
    {
        foreach (SuratTugasPelaksana::all() as $pelaksana) {
            $this->generatePelaksana($pelaksana, 'Pendataan Lapangan');
        }

        // Checklist integrasi menjadi Lengkap.
        $this->assertSame('Lengkap', $this->integrasiChecklist->fresh()->status);
        $this->assertDatabaseHas('checklist_histories', [
            'checklist_id' => $this->integrasiChecklist->id,
            'status_baru' => 'Lengkap',
        ]);

        // Surat Tugas tidak berubah status.
        $this->assertSame('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_checklist_integrasi_perlu_perbaikan_tidak_dioverwrite(): void
    {
        $this->integrasiChecklist->update(['status' => 'Perlu Perbaikan']);

        foreach (SuratTugasPelaksana::all() as $pelaksana) {
            $this->generatePelaksana($pelaksana, 'Pendataan Lapangan');
        }

        $this->assertSame('Perlu Perbaikan', $this->integrasiChecklist->fresh()->status);
    }

    public function test_halaman_index_terima_pelaksana_param_tunggal_dan_array(): void
    {
        $pelaksana = SuratTugasPelaksana::first();

        $this->actingAs($this->user)->get(route('requests.superkendis', $this->fpaRequest->id).'?pelaksana='.$pelaksana->id)->assertOk();
        $this->actingAs($this->user)->get(route('requests.superkendis', $this->fpaRequest->id).'?pelaksana[]='.$pelaksana->id)->assertOk();
    }
}
