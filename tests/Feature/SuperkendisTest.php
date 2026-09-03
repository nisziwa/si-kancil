<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperkendisController;
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
            'ibukota_kecamatan' => 'Muara',
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

    public function test_index_autochecks_pelaksana_with_stored_superkendis_and_prefills(): void
    {
        $pelaksana1 = SuratTugasPelaksana::where('urutan', 1)->first();
        $pelaksana2 = SuratTugasPelaksana::where('urutan', 2)->first();

        Superkendis::create([
            'surat_tugas_pelaksana_id' => $pelaksana1->id,
            'kecamatan' => 'Kecamatan Muara',
            'tanggal_perjalanan' => '2026-08-26',
            'jenis_kegiatan' => 'Pendataan Lapangan',
            'nip' => '198001012010011001',
            'jabatan' => 'Pelaksana',
        ]);

        $response = $this->actingAs($this->user)->get(route('requests.superkendis', $this->fpaRequest->id));
        $response->assertOk();

        $html = $response->getContent();

        // Pelaksana 1 (punya Superkendis) otomatis tercentang.
        $this->assertMatchesRegularExpression(
            '/name="pelaksana\['.$pelaksana1->id.'\]\[selected\]" value="1"\s+class="pelaksana-check[^"]*"\s+checked/i',
            $html
        );
        // Nilainya terisi dari record Superkendis.
        $this->assertStringContainsString('value="198001012010011001"', $html);
        $this->assertStringContainsString('value="2026-08-26"', $html);

        // Pelaksana 2 (belum punya Superkendis) tidak tercentang.
        $this->assertDoesNotMatchRegularExpression(
            '/name="pelaksana\['.$pelaksana2->id.'\]\[selected\]" value="1"\s+class="pelaksana-check[^"]*"\s+checked/i',
            $html
        );
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
        $pelaksana1 = SuratTugasPelaksana::orderBy('urutan')->first();
        $pelaksana2 = SuratTugasPelaksana::orderBy('urutan')->skip(1)->first();

        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk', $this->fpaRequest->id),
            [
                'format' => 'docx',
                'method' => 'merged',
                'pelaksana' => [
                    $pelaksana1->id => [
                        'selected' => 1,
                        'kecamatan' => 'Kecamatan Muara',
                        'tanggal_perjalanan' => '2026-08-26',
                        'nip' => '',
                    ],
                    $pelaksana2->id => [
                        'selected' => 1,
                        'kecamatan' => 'Kecamatan Muara',
                        'tanggal_perjalanan' => '2026-08-26',
                        'nip' => '',
                    ],
                ],
            ]
        );

        $response->assertOk();
        $this->assertStringContainsString('Superkendis_Gabungan.docx', $response->headers->get('content-disposition'));
    }

    public function test_bulk_separate_generates_zip(): void
    {
        $pelaksana1 = SuratTugasPelaksana::orderBy('urutan')->first();
        $pelaksana2 = SuratTugasPelaksana::orderBy('urutan')->skip(1)->first();

        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk', $this->fpaRequest->id),
            [
                'format' => 'docx',
                'method' => 'separate',
                'pelaksana' => [
                    $pelaksana1->id => [
                        'selected' => 1,
                        'kecamatan' => 'Kecamatan Muara',
                        'tanggal_perjalanan' => '2026-08-26',
                        'nip' => '',
                    ],
                    $pelaksana2->id => [
                        'selected' => 1,
                        'kecamatan' => 'Kecamatan Muara',
                        'tanggal_perjalanan' => '2026-08-26',
                        'nip' => '',
                    ],
                ],
            ]
        );

        $response->assertOk();
        $this->assertStringContainsString('Superkendis_Pisah.zip', $response->headers->get('content-disposition'));
    }

    public function test_bulk_merged_page_break_between_pelaksana(): void
    {
        // BUG #1: antar Superkendis pelaksana harus berpindah halaman (nextPage).
        // appendBody() mengubah <w:type w:val="continuous"/> menjadi nextPage
        // agar pelaksana berikutnya tidak menempel di halaman yang sama.
        $controller = new SuperkendisController;
        $method = new \ReflectionMethod($controller, 'appendBody');
        $method->setAccessible(true);

        $base = '<w:body><w:p>Pelaksana 1</w:p><w:sectPr><w:type w:val="nextPage"/></w:sectPr></w:body>';
        $src = '<w:body><w:p>Pelaksana 2</w:p><w:sectPr><w:type w:val="continuous"/></w:sectPr></w:body>';

        $merged = $method->invokeArgs($controller, [$base, $src]);

        $this->assertStringContainsString('w:type="page"', $merged, 'Harus ada page break eksplisit.');
        // Struktur isi pelaksana 2 tetap dipertahankan.
        $this->assertStringContainsString('Pelaksana 2', $merged);

        // Page break harus muncul SEBELUM konten pelaksana berikutnya, agar
        // judul/isi pelaksana 2 dimulai pada halaman baru (bukan menempel di bawah
        // pelaksana 1).
        $posBreak = strpos($merged, 'w:type="page"');
        $posP2 = strpos($merged, 'Pelaksana 2');
        $this->assertNotFalse($posBreak);
        $this->assertNotFalse($posP2);
        $this->assertLessThan($posP2, $posBreak, 'Page break harus mendahului konten pelaksana berikutnya.');

        // Fallback saat sumber tidak memiliki sectPr: gunakan page break eksplisit.
        $srcNoSect = '<w:body><w:p>Pelaksana 3</w:p></w:body>';
        $merged2 = $method->invokeArgs($controller, [$base, $srcNoSect]);
        $this->assertStringContainsString('w:type="page"', $merged2);
        $this->assertStringContainsString('Pelaksana 3', $merged2);
    }

    public function test_single_pelaksana_downloads_direct_docx(): void
    {
        $pelaksana1 = SuratTugasPelaksana::orderBy('urutan')->first();

        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk', $this->fpaRequest->id),
            [
                'format' => 'docx',
                'method' => 'separate',
                'pelaksana' => [
                    $pelaksana1->id => [
                        'selected' => 1,
                        'kecamatan' => 'Kecamatan Muara',
                        'tanggal_perjalanan' => '2026-08-26',
                        'nip' => '',
                    ],
                ],
            ]
        );

        $response->assertOk();
        $filename = 'Superkendis_'.str_replace(' ', '_', $pelaksana1->nama_pelaksana).'.docx';
        $this->assertStringContainsString($filename, $response->headers->get('content-disposition'));
        $this->assertStringNotContainsString('.zip', $response->headers->get('content-disposition'));
    }

    public function test_bulk_export_requires_tujuan_and_tanggal_per_pelaksana(): void
    {
        $pelaksana1 = SuratTugasPelaksana::orderBy('urutan')->first();

        // Tanpa kecamatan & tanggal untuk pelaksana terpilih, export ditolak
        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk', $this->fpaRequest->id),
            [
                'format' => 'docx',
                'method' => 'merged',
                'pelaksana' => [
                    $pelaksana1->id => [
                        'selected' => 1,
                        'kecamatan' => '',
                        'tanggal_perjalanan' => '',
                        'nip' => '',
                    ],
                ],
            ]
        );

        $response->assertStatus(422);
    }

    public function test_bulk_requires_at_least_one_pelaksana(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('requests.superkendis.bulk', $this->fpaRequest->id),
            [
                'format' => 'docx',
                'method' => 'separate',
                'pelaksana' => [],
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
