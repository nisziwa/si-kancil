<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\DocumentTemplate;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\TravelReportPelaksana;
use App\Models\User;
use App\Services\ChecklistStatusGate;
use App\Services\SuratTugasService;
use Database\Seeders\DocumentTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FpaKanbanStatusValidationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected FpaRequest $fpa;

    protected SpjChecklist $stChecklist;

    protected SpjChecklist $laporanChecklist;

    protected SpjChecklist $dokumentasiChecklist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $expenseType = ExpenseType::create([
            'nama' => 'Perjalanan Dinas', 'kode' => 'PERJADIN', 'is_active' => true,
        ]);

        $this->fpa = FpaRequest::create([
            'nomor_fpa' => 'FPA-FLOW-001',
            'deskripsi_permintaan' => 'Uji flow status',
            'jenis_pengeluaran_id' => $expenseType->id,
            'periode' => 'Subround',
            'user_id' => $this->user->id,
            'status_spj' => 'Persiapan',
        ]);

        $this->stChecklist = SpjChecklist::create([
            'request_id' => $this->fpa->id,
            'nama_dokumen' => 'Surat Tugas', 'status' => 'Belum Lengkap', 'is_required' => true,
        ]);

        $this->laporanChecklist = SpjChecklist::create([
            'request_id' => $this->fpa->id,
            'nama_dokumen' => 'Laporan Perjalanan', 'status' => 'Belum Lengkap', 'is_required' => true,
        ]);

        $this->dokumentasiChecklist = SpjChecklist::create([
            'request_id' => $this->fpa->id,
            'nama_dokumen' => 'Dokumentasi', 'status' => 'Lengkap', 'is_required' => true,
        ]);
    }

    /* ---------- Template HONOR menyertakan Surat Tugas ---------- */

    public function test_honor_template_menyertakan_surat_tugas(): void
    {
        ExpenseType::create(['nama' => 'Honor', 'kode' => 'HONOR', 'is_active' => true]);
        $this->seed(DocumentTemplateSeeder::class);

        $honor = ExpenseType::where('kode', 'HONOR')->first();
        $names = DocumentTemplate::where('expense_type_id', $honor->id)
            ->orderBy('urutan')
            ->pluck('nama_dokumen')
            ->all();

        $this->assertEquals(['KAK', 'FPA', 'Kuitansi BOS', 'Surat Tugas'], $names);
    }

    /* ---------- Dokumentasi vs Laporan Perjalanan (kanban) ---------- */

    public function test_kanban_laporan_tidak_boleh_lengkap_saat_dokumentasi_belum_lengkap(): void
    {
        $this->setupSTLengkap();
        $this->dokumentasiChecklist->update(['status' => 'Belum Lengkap']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'require_confirmation' => true,
            'checklist_id' => $this->dokumentasiChecklist->id,
            'message' => ChecklistStatusGate::DOKUMENTASI_BLOCK_MESSAGE,
        ]);
        $this->assertNotEquals('Lengkap', $this->laporanChecklist->fresh()->status);
    }

    public function test_kanban_laporan_tidak_boleh_perbaikan_saat_dokumentasi_belum_lengkap(): void
    {
        $this->setupSTLengkap();
        $this->dokumentasiChecklist->update(['status' => 'Belum Lengkap']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Perlu Perbaikan']);

        $response->assertStatus(422);
        $response->assertJson(['message' => ChecklistStatusGate::DOKUMENTASI_BLOCK_MESSAGE]);
        $this->assertNotEquals('Perlu Perbaikan', $this->laporanChecklist->fresh()->status);
    }

    public function test_kanban_laporan_boleh_perbaikan_saat_dokumentasi_lengkap(): void
    {
        $this->setupSTLengkap();

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Perlu Perbaikan']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('Perlu Perbaikan', $this->laporanChecklist->fresh()->status);
    }

    public function test_kanban_laporan_boleh_lengkap_saat_dokumentasi_perbaikan(): void
    {
        $this->setupSTLengkap();
        $this->dokumentasiChecklist->update(['status' => 'Perlu Perbaikan']);
        $this->markAllCollected();

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('Lengkap', $this->laporanChecklist->fresh()->status);
    }

    /* ---------- Dokumentasi vs Laporan (dropdown) ---------- */

    public function test_dropdown_laporan_diblokir_saat_dokumentasi_belum_lengkap(): void
    {
        $this->setupSTLengkap();
        $this->dokumentasiChecklist->update(['status' => 'Belum Lengkap']);

        $response = $this->actingAs($this->user)
            ->from(route('checklists.edit', $this->laporanChecklist->id))
            ->put(route('checklists.update', $this->laporanChecklist->id), [
                'status' => 'Lengkap',
                'catatan' => null,
            ]);

        $response->assertRedirect(route('checklists.edit', $this->laporanChecklist->id));
        $response->assertSessionHas('status_block');
        $this->assertEquals('Belum Lengkap', $this->laporanChecklist->fresh()->status);
        $this->assertEquals('Lengkapi Dokumentasi', session('status_block')['link_text']);
        $this->assertEquals(route('checklists.edit', $this->dokumentasiChecklist->id), session('status_block')['link_href']);
    }

    /* ---------- Checklist ke Perbaikan saat SPJ "Dikirim ke PPK" (kanban) ---------- */

    public function test_kanban_dokumen_perbaikan_saat_spj_dikirim_ppk_minta_konfirmasi(): void
    {
        $this->setupSTLengkap();
        $this->fpa->update(['status_spj' => 'Dikirim ke PPK']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Perlu Perbaikan']);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'require_spj_confirm' => true,
            'message' => ChecklistStatusGate::SPJ_PERBAIKAN_CONFIRM_MESSAGE,
        ]);
        $this->assertEquals('Dikirim ke PPK', $this->fpa->fresh()->status_spj);
        $this->assertNotEquals('Perlu Perbaikan', $this->laporanChecklist->fresh()->status);
    }

    public function test_kanban_dokumen_perbaikan_dengan_konfirmasi_ubah_spj(): void
    {
        $this->setupSTLengkap();
        $this->fpa->update(['status_spj' => 'Dikirim ke PPK']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), [
                'status' => 'Perlu Perbaikan',
                'confirm_spj' => 1,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('Perlu Perbaikan', $this->laporanChecklist->fresh()->status);
        $this->assertEquals('Perbaikan', $this->fpa->fresh()->status_spj);
        $this->assertDatabaseHas('request_status_histories', [
            'request_id' => $this->fpa->id,
            'status_lama' => 'Dikirim ke PPK',
            'status_baru' => 'Perbaikan',
        ]);
    }

    /* ---------- Checklist ke Perbaikan saat SPJ "Dikirim ke PPK" (dropdown & bulk) ---------- */

    public function test_dropdown_dokumen_perbaikan_mengubah_spj_menjadi_perbaikan(): void
    {
        $this->setupSTLengkap();
        $this->fpa->update(['status_spj' => 'Dikirim ke PPK']);

        $response = $this->actingAs($this->user)
            ->put(route('checklists.update', $this->laporanChecklist->id), [
                'status' => 'Perlu Perbaikan',
                'catatan' => null,
            ]);

        $response->assertRedirect(route('requests.show', $this->fpa->id));
        $response->assertSessionHas('success');
        $this->assertEquals('Perlu Perbaikan', $this->laporanChecklist->fresh()->status);
        $this->assertEquals('Perbaikan', $this->fpa->fresh()->status_spj);
    }

    public function test_bulk_dokumen_perbaikan_mengubah_spj_menjadi_perbaikan(): void
    {
        $this->setupSTLengkap();
        $this->fpa->update(['status_spj' => 'Dikirim ke PPK']);

        $response = $this->actingAs($this->user)
            ->postJson(route('requests.checklists.bulk-status', $this->fpa->id), [
                'ids' => [$this->laporanChecklist->id],
                'status' => 'Perlu Perbaikan',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'spj_perbaikan' => true]);
        $this->assertEquals('Perlu Perbaikan', $this->laporanChecklist->fresh()->status);
        $this->assertEquals('Perbaikan', $this->fpa->fresh()->status_spj);
    }

    /* ---------- Konsistensi single vs bulk ---------- */

    public function test_bulk_validasi_sama_dengan_single_move(): void
    {
        // ST Belum Lengkap: Laporan diblokir dengan pesan yang sama di single & bulk.
        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);
        $response->assertStatus(422);
        $singleMessage = $response->json('message');

        $bulk = $this->actingAs($this->user)
            ->postJson(route('requests.checklists.bulk-status', $this->fpa->id), [
                'ids' => [$this->laporanChecklist->id],
                'status' => 'Lengkap',
            ]);
        $bulk->assertOk();
        $bulk->assertJson([
            'success' => false,
            'results' => ['failed' => [[
                'id' => $this->laporanChecklist->id,
                'nama' => 'Laporan Perjalanan',
                'error' => $singleMessage,
            ]]],
        ]);

        $this->assertEquals('Belum Lengkap', $this->laporanChecklist->fresh()->status);
        $this->assertEquals('Belum Lengkap', $this->stChecklist->fresh()->status);
    }

    /* ---------- Helper ---------- */

    protected function setupSTLengkap(): void
    {
        $detail = SuratTugasDetail::create([
            'checklist_id' => $this->stChecklist->id,
            'nomor_surat_tugas' => 'B-ST/FLOW/2026',
            'tanggal_surat_tugas' => '2026-09-01',
            'isi_tugas' => 'Pendataan',
        ]);

        SuratTugasPelaksana::create([
            'surat_tugas_detail_id' => $detail->id,
            'nama_pelaksana' => 'Budi Santoso',
            'nomor_surat' => 'B-ST.1/FLOW/2026',
            'urutan' => 1,
        ]);

        $this->stChecklist->update(['status' => 'Lengkap']);
    }

    protected function markAllCollected(): void
    {
        $detail = $this->stChecklist->suratTugasDetail;

        foreach ($detail->pelaksanas as $pelaksana) {
            TravelReportPelaksana::create([
                'checklist_id' => $this->laporanChecklist->id,
                'surat_tugas_pelaksana_id' => $pelaksana->id,
                'status' => TravelReportPelaksana::STATUS_SUDAH,
            ]);
        }
    }
}