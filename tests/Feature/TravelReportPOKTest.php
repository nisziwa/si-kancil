<?php

namespace Tests\Feature;

use App\Models\MasterAkun;
use App\Models\MasterKegiatan;
use App\Models\MasterKomponen;
use App\Models\MasterOutput;
use App\Models\MasterProgram;
use App\Models\MasterRincianPok;
use App\Models\MasterSubOutput;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\TravelReport;
use App\Models\TravelReportPelaksana;
use App\Models\User;
use App\Services\TravelReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TravelReportPOKTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected FpaRequest $fpa;

    protected SpjChecklist $laporanChecklist;

    protected SuratTugasPelaksana $pelaksana;

    protected MasterRincianPok $pok;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $expenseType = \App\Models\ExpenseType::create([
            'nama' => 'Perjalanan Dinas', 'kode' => 'PERJADIN', 'is_active' => true,
        ]);

        $this->fpa = FpaRequest::create([
            'nomor_fpa' => 'FPA-LAP-001',
            'deskripsi_permintaan' => 'Pendataan Lapangan',
            'jenis_pengeluaran_id' => $expenseType->id,
            'periode' => 'Subround',
            'user_id' => $this->user->id,
            'status_spj' => 'Persiapan',
        ]);

        $stChecklist = SpjChecklist::create([
            'request_id' => $this->fpa->id,
            'nama_dokumen' => 'Surat Tugas', 'status' => 'Lengkap', 'is_required' => true,
        ]);

        $detail = SuratTugasDetail::create([
            'checklist_id' => $stChecklist->id,
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-09-01',
            'isi_tugas' => 'Pendataan',
        ]);

        $this->pelaksana = SuratTugasPelaksana::create([
            'surat_tugas_detail_id' => $detail->id,
            'nama_pelaksana' => 'Budi Santoso',
            'nomor_surat' => 'B-1027.1/75040/KP.650/2026',
            'urutan' => 1,
        ]);

        $this->laporanChecklist = SpjChecklist::create([
            'request_id' => $this->fpa->id,
            'nama_dokumen' => 'Laporan Perjalanan', 'status' => 'Belum Lengkap', 'is_required' => true,
        ]);

        $this->pok = $this->seedPok();
    }

    protected function seedPok(): MasterRincianPok
    {
        $program = MasterProgram::create(['kode_program' => '054.01.GG', 'nama_program' => 'Program Informasi Statistik']);
        $kegiatan = MasterKegiatan::create(['program_id' => $program->id, 'kode_kegiatan' => '2897', 'nama_kegiatan' => 'Kegiatan Data']);
        $output = MasterOutput::create(['kegiatan_id' => $kegiatan->id, 'kode_output' => '2897.BMA', 'nama_output' => 'Output Data']);
        $sub = MasterSubOutput::create(['output_id' => $output->id, 'kode_sub_output' => '2897.BMA.005', 'nama_sub_output' => 'Sub Output Data']);
        $komponen = MasterKomponen::create(['sub_output_id' => $sub->id, 'kode_komponen' => '005', 'nama_komponen' => 'Komponen']);
        $akun = MasterAkun::create(['kode_akun' => '521213', 'nama_akun' => 'Belanja Honor']);

        return MasterRincianPok::create([
            'program_id' => $program->id,
            'kegiatan_id' => $kegiatan->id,
            'output_id' => $output->id,
            'sub_output_id' => $sub->id,
            'komponen_id' => $komponen->id,
            'akun_id' => $akun->id,
            'rincian' => 'honor petugas pendataan lapangan survei captive power',
        ]);
    }

    public function test_pok_relationships_resolve(): void
    {
        $pok = MasterRincianPok::with(['program', 'kegiatan', 'output', 'subOutput', 'komponen', 'akun'])->find($this->pok->id);

        $this->assertSame('054.01.GG', $pok->program->kode_program);
        $this->assertSame('2897', $pok->kegiatan->kode_kegiatan);
        $this->assertSame('2897.BMA', $pok->output->kode_output);
        $this->assertSame('2897.BMA.005', $pok->subOutput->kode_sub_output);
        $this->assertSame('005', $pok->komponen->kode_komponen);
        $this->assertSame('521213', $pok->akun->kode_akun);
    }

    public function test_pok_search_endpoint_returns_result(): void
    {
        $response = $this->actingAs($this->user)->getJson('/travel-reports/pok/search?q=captive')
            ->assertOk();

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('survei captive power', $response->json('data.0.rincian'));
    }

    public function test_kanban_popup_returns_not_collected_count_and_message(): void
    {
        // Belum ada pelaksana yang mengumpulkan -> 1 belum mengumpulkan.
        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'require_confirmation' => true,
            'not_collected' => 1,
            'message' => 'Terdapat 1 pelaksana yang belum mengumpulkan laporan perjalanan.',
        ]);
        $this->assertNotEquals('Lengkap', $this->laporanChecklist->fresh()->status);
    }

    public function test_popup_allows_lengkap_when_all_collected(): void
    {
        TravelReportPelaksana::create([
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => TravelReportPelaksana::STATUS_SUDAH,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);

        $response->assertOk();
        $this->assertEquals('Lengkap', $this->laporanChecklist->fresh()->status);
    }

    public function test_bulk_blocked_when_laporan_not_all_collected(): void
    {
        $other = SuratTugasPelaksana::create([
            'surat_tugas_detail_id' => $this->pelaksana->surat_tugas_detail_id,
            'nama_pelaksana' => 'Siti Aminah',
            'nomor_surat' => 'B-1027.2/75040/KP.650/2026',
            'urutan' => 2,
        ]);

        // Hanya satu yang mengumpulkan -> bulk Lengkap harus gagal.
        TravelReportPelaksana::create([
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => TravelReportPelaksana::STATUS_SUDAH,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('requests.checklists.bulk-status', $this->fpa->id), [
                'ids' => [$this->laporanChecklist->id],
                'status' => 'Lengkap',
            ]);

        $response->assertOk();
        $failed = $response->json('results.failed');
        $this->assertCount(1, $failed);
        $this->assertStringContainsString('belum mengumpulkan', $failed[0]['error']);
        $this->assertNotEquals('Lengkap', $this->laporanChecklist->fresh()->status);

        $other->delete();
    }

    public function test_bulk_pelaksana_status_updates_selected(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('travel-reports.bulk-pelaksana-status', $this->laporanChecklist->id), [
                'pelaksana_ids' => [$this->pelaksana->id],
                'status' => 'Sudah Mengumpulkan',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('travel_report_pelaksanas', [
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => 'Sudah Mengumpulkan',
        ]);
    }

    public function test_upload_laporan_sets_status_sudah(): void
    {
        $file = UploadedFile::fake()->create('laporan.docx', 50);

        $response = $this->actingAs($this->user)
            ->postJson(route('travel-reports.upload', [$this->laporanChecklist->id, $this->pelaksana->id]), [
                'file_laporan' => $file,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('travel_report_pelaksanas', [
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => 'Sudah Mengumpulkan',
        ]);

        $report = TravelReport::where('fpa_id', $this->fpa->id)
            ->where('surat_tugas_pelaksana_id', $this->pelaksana->id)->first();
        $this->assertNotNull($report);
        Storage::disk('public')->assertExists($report->file_docx);
    }

    public function test_change_back_to_belum_requires_confirmation_when_file_exists(): void
    {
        // Seed file pada record laporan.
        $this->seedReportWithFile();

        $response = $this->actingAs($this->user)
            ->patchJson(route('travel-reports.pelaksana-status', [$this->laporanChecklist->id, $this->pelaksana->id]), [
                'status' => 'Belum Mengumpulkan',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['require_confirm' => true]);
        $this->assertStringContainsString(
            'sudah memiliki laporan yang diunggah',
            $response->json('message')
        );
        // Status tidak berubah (tetap Sudah Mengumpulkan).
        $this->assertDatabaseHas('travel_report_pelaksanas', [
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => 'Sudah Mengumpulkan',
        ]);
    }

    public function test_change_back_to_belum_with_confirm_succeeds(): void
    {
        $this->seedReportWithFile();
        $this->assertDatabaseHas('travel_report_pelaksanas', [
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => 'Sudah Mengumpulkan',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson(route('travel-reports.pelaksana-status', [$this->laporanChecklist->id, $this->pelaksana->id]), [
                'status' => 'Belum Mengumpulkan',
                'confirm' => true,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('travel_report_pelaksanas', [
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => 'Belum Mengumpulkan',
        ]);
    }

    public function test_generate_report_endpoint_downloads_with_pok(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('travel-reports.generate', [$this->laporanChecklist->id, $this->pelaksana->id]), [
                'jenis_laporan' => TravelReport::JENIS_PENDATAAN,
                'judul_laporan' => 'SURVEI UBINAN PALAWIJA SUBROUND 3 TAHUN 2026',
                'tanggal_laporan' => '2026-09-04',
                'pok_rincian_id' => $this->pok->id,
            ]);

        $response->assertOk();

        $report = TravelReport::where('fpa_id', $this->fpa->id)
            ->where('surat_tugas_pelaksana_id', $this->pelaksana->id)->first();
        $this->assertNotNull($report);
        $this->assertSame($this->pok->id, $report->pok_rincian_id);
        $this->assertSame('SURVEI UBINAN PALAWIJA SUBROUND 3 TAHUN 2026', $report->judul_laporan);
        $this->assertNotNull($report->file_docx);
        Storage::disk('public')->assertExists($report->file_docx);
        // Generate menandai pelaksana telah mengumpulkan.
        $this->assertDatabaseHas('travel_report_pelaksanas', [
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => 'Sudah Mengumpulkan',
        ]);
    }

    public function test_generate_report_requires_pok_judul_and_tanggal(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('travel-reports.generate', [$this->laporanChecklist->id, $this->pelaksana->id]), [
                'jenis_laporan' => TravelReport::JENIS_PENDATAAN,
                'judul_laporan' => '',
                'tanggal_laporan' => '',
                'pok_rincian_id' => '',
            ]);

        $response->assertSessionHasErrors(['judul_laporan', 'tanggal_laporan', 'pok_rincian_id']);
    }

    public function test_template_takes_financing_from_pok(): void
    {
        $this->seedReportWithFile();
        $report = TravelReport::where('fpa_id', $this->fpa->id)
            ->where('surat_tugas_pelaksana_id', $this->pelaksana->id)->first();
        $report->update(['judul_laporan' => 'SURVEI']);

        $data = app(TravelReportService::class)->buildData($report, $this->pelaksana);

        $this->assertSame('LAPORAN PENDATAAN', $data['jenis_laporan']);
        $this->assertSame('SURVEI', $data['judul_laporan']);
        $this->assertSame('Budi Santoso', $data['nama']);

        $pembiayaan = collect($data['pembiayaan'])->pluck('value')->implode('|');
        $this->assertStringContainsString('054.01.GG', $pembiayaan);
        $this->assertStringContainsString('2897', $pembiayaan);
        $this->assertStringContainsString('2897.BMA.005', $pembiayaan);
        $this->assertStringContainsString('521213', $pembiayaan);
        $this->assertStringContainsString('survei captive power', $pembiayaan);
    }

    public function test_service_outputs_docx_for_pok_data(): void
    {
        $report = TravelReport::create([
            'fpa_id' => $this->fpa->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'jenis_laporan' => TravelReport::JENIS_PENGAWASAN,
            'judul_laporan' => 'PENGAWASAN LAPANGAN',
            'tanggal_laporan' => '2026-09-04',
            'pok_rincian_id' => $this->pok->id,
        ]);

        $data = app(TravelReportService::class)->buildData($report, $this->pelaksana);
        $path = storage_path('app/travel-test-'.uniqid().'.docx');
        app(TravelReportService::class)->write($data, 'docx', $path);

        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path));
        @unlink($path);
        $this->assertSame('LAPORAN PENGAWASAN DAN PEMERIKSAAN', $data['jenis_laporan']);
    }

    protected function seedReportWithFile(): void
    {
        $report = TravelReport::create([
            'fpa_id' => $this->fpa->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'jenis_laporan' => TravelReport::JENIS_PENDATAAN,
            'judul_laporan' => 'SURVEI',
            'tanggal_laporan' => '2026-09-04',
            'pok_rincian_id' => $this->pok->id,
        ]);

        Storage::disk('public')->put('spj-files/laporan-perjalanan/laporan.docx', 'dummy');
        $report->update(['file_docx' => 'spj-files/laporan-perjalanan/laporan.docx']);

        TravelReportPelaksana::create([
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $this->pelaksana->id,
            'status' => 'Sudah Mengumpulkan',
        ]);
    }
}
