<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SkRatePerjalanan;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperkendisTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected FpaRequest $fpaRequest;

    protected SpjChecklist $stChecklist;

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
            'nomor_fpa' => 'FPA-SKD-001',
            'deskripsi_permintaan' => 'Perjalanan Dinas Superkendis Test',
            'jenis_pengeluaran_id' => $expenseType->id,
            'periode' => 'Triwulanan',
            'user_id' => $this->user->id,
            'status_spj' => 'Dikirim ke PPK',
        ]);

        $this->stChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'Surat Tugas',
            'status' => 'Lengkap',
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
            'besaran_biaya_transport' => 150000,
            'keterangan' => 'test',
        ]);
    }

    public function test_superkendis_index_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('requests.superkendis', $this->fpaRequest->id));
        $response->assertOk();
        $response->assertSee('Generate Superkendis');
        $response->assertSee('Budi Santoso');
    }

    public function test_generate_superkendis_docx(): void
    {
        $pelaksana = SuratTugasPelaksana::first();

        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.generate', ['requestId' => $this->fpaRequest->id, 'pelaksanaId' => $pelaksana->id]),
            [
                'kecamatan' => 'Kecamatan Muara',
                'tanggal_perjalanan' => '2026-08-26',
                'nip' => '198001012010011001',
                'format' => 'docx',
            ]
        );

        $response->assertOk();
        $this->assertStringContainsString('Superkendis_Budi_Santoso.docx', $response->headers->get('content-disposition'));
    }

    public function test_generate_superkendis_pdf(): void
    {
        $pelaksana = SuratTugasPelaksana::first();

        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.generate', ['requestId' => $this->fpaRequest->id, 'pelaksanaId' => $pelaksana->id]),
            [
                'kecamatan' => 'Kecamatan Muara',
                'tanggal_perjalanan' => '2026-08-26',
                'format' => 'pdf',
            ]
        );

        $response->assertOk();
        $this->assertStringContainsString('Superkendis_Budi_Santoso.pdf', $response->headers->get('content-disposition'));
    }

    public function test_bulk_merged_generates_gabungan_file(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk-merged', $this->fpaRequest->id),
            [
                'kecamatan' => 'Kecamatan Muara',
                'tanggal_perjalanan' => '2026-08-26',
                'format' => 'docx',
            ]
        );

        $response->assertOk();
        $this->assertStringContainsString('Superkendis_Gabungan.docx', $response->headers->get('content-disposition'));
    }

    public function test_bulk_separate_generates_zip(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk-separate', $this->fpaRequest->id),
            [
                'kecamatan' => 'Kecamatan Muara',
                'tanggal_perjalanan' => '2026-08-26',
                'format' => 'docx',
            ]
        );

        $response->assertOk();
        $this->assertStringContainsString('Superkendis_Pisah.zip', $response->headers->get('content-disposition'));
    }

    public function test_bulk_export_requires_tujuan_and_tanggal(): void
    {
        // Tanpa tanggal & kecamatan, export ditolak
        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk-merged', $this->fpaRequest->id),
            [
                'kecamatan' => '',
                'tanggal_perjalanan' => '',
                'format' => 'docx',
            ]
        );

        $response->assertStatus(422);
    }

    public function test_nip_normalization(): void
    {
        $pelaksana = SuratTugasPelaksana::first();

        // NIP kosong -> tetap bisa export, terisi "-"
        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.generate', ['requestId' => $this->fpaRequest->id, 'pelaksanaId' => $pelaksana->id]),
            [
                'kecamatan' => 'Kecamatan Muara',
                'tanggal_perjalanan' => '2026-08-26',
                'nip' => '',
                'format' => 'docx',
            ]
        );

        $response->assertOk();
        $this->assertStringContainsString('Superkendis_Budi_Santoso.docx', $response->headers->get('content-disposition'));
    }
}
