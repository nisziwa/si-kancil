<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\TravelReportPelaksana;
use App\Models\User;
use App\Services\SuratTugasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistStatusBlockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected FpaRequest $fpa;

    protected SpjChecklist $stChecklist;

    protected SpjChecklist $laporanChecklist;

    protected SpjChecklist $pengeluaranChecklist;

    protected SuratTugasDetail $stDetail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $expenseType = ExpenseType::create([
            'nama' => 'Perjalanan Dinas', 'kode' => 'PERJADIN', 'is_active' => true,
        ]);

        $this->fpa = FpaRequest::create([
            'nomor_fpa' => 'FPA-BLOCK-001',
            'deskripsi_permintaan' => 'Uji validasi status',
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

        $this->pengeluaranChecklist = SpjChecklist::create([
            'request_id' => $this->fpa->id,
            'nama_dokumen' => 'Pengeluaran Riil + Surat Non Kendaraan Dinas',
            'status' => 'Belum Lengkap',
            'is_required' => true,
        ]);
    }

    /* ---------- Kanban: Surat Tugas sendiri ---------- */

    public function test_kanban_surat_tugas_ke_lengkap_incomplete_meminta_konfirmasi_st(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->stChecklist->id), ['status' => 'Lengkap']);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'require_st_confirmation' => true,
            'checklist_id' => $this->stChecklist->id,
            'message' => SuratTugasService::ST_INCOMPLETE_MESSAGE,
        ]);
        $this->assertNotEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    /* ---------- Kanban: gate Surat Tugas untuk dokumen dependen ---------- */

    public function test_kanban_laporan_diblokir_saat_st_belum_ada(): void
    {
        $this->setStStatus('Belum Ada');

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'require_st_confirmation' => true,
            'checklist_id' => $this->stChecklist->id,
            'message' => SuratTugasService::ST_DEPENDENT_BLOCK_MESSAGE,
        ]);
        $this->assertNotEquals('Lengkap', $this->laporanChecklist->fresh()->status);
    }

    public function test_kanban_laporan_diblokir_ke_status_apa_pun_saat_st_belum_ada(): void
    {
        $this->setStStatus('Belum Ada');

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Perlu Perbaikan']);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'require_st_confirmation' => true]);
    }

    public function test_kanban_laporan_hanya_boleh_belum_lengkap_saat_st_belum_lengkap(): void
    {
        $this->setStStatus('Belum Lengkap');

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'require_st_confirmation' => true]);
        $this->assertNotEquals('Lengkap', $this->laporanChecklist->fresh()->status);
    }

    public function test_kanban_pengeluaran_riil_diblokir_saat_st_belum_ada(): void
    {
        $this->setStStatus('Belum Ada');

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->pengeluaranChecklist->id), ['status' => 'Lengkap']);

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'require_st_confirmation' => true]);
        $this->assertNotEquals('Lengkap', $this->pengeluaranChecklist->fresh()->status);
    }

    public function test_kanban_dependen_boleh_pindah_saat_st_lengkap(): void
    {
        $this->setStStatus('Lengkap');
        $this->seedCompleteStDetail();
        $this->markAllCollected();

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->laporanChecklist->id), ['status' => 'Lengkap']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('Lengkap', $this->laporanChecklist->fresh()->status);
    }

    public function test_kanban_dependen_boleh_pindah_saat_st_perlu_perbaikan(): void
    {
        $this->setStStatus('Perlu Perbaikan');

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->pengeluaranChecklist->id), ['status' => 'Lengkap']);

        $response->assertOk();
        $this->assertEquals('Lengkap', $this->pengeluaranChecklist->fresh()->status);
    }

    /* ---------- Dropdown Laporan Perjalanan: aturan pengumpulan ---------- */

    public function test_dropdown_laporan_lengkap_semua_belum_kumpul_tetap_belum_ada(): void
    {
        $this->setStStatus('Lengkap');
        $this->seedCompleteStDetail();

        $response = $this->actingAs($this->user)
            ->from(route('checklists.edit', $this->laporanChecklist->id))
            ->put(route('checklists.update', $this->laporanChecklist->id), [
                'status' => 'Lengkap',
                'catatan' => null,
            ]);

        $response->assertRedirect(route('checklists.edit', $this->laporanChecklist->id));
        $this->assertEquals('Belum Ada', $this->laporanChecklist->fresh()->status);
        $response->assertSessionHas('status_block', function ($block) {
            return isset($block['message'])
                && str_contains($block['message'], 'seluruh pelaksana belum mengumpulkan');
        });
    }

    public function test_dropdown_laporan_lengkap_sebagian_kumpul_otomatis_belum_lengkap(): void
    {
        $this->setStStatus('Lengkap');
        $detail = $this->seedCompleteStDetail();
        $this->addPelaksana($detail, 'Siti Aminah');
        $this->markOneCollected();

        $response = $this->actingAs($this->user)
            ->from(route('checklists.edit', $this->laporanChecklist->id))
            ->put(route('checklists.update', $this->laporanChecklist->id), [
                'status' => 'Lengkap',
                'catatan' => null,
            ]);

        $response->assertRedirect(route('checklists.edit', $this->laporanChecklist->id));
        $this->assertEquals('Belum Lengkap', $this->laporanChecklist->fresh()->status);
        $response->assertSessionHas('status_block', function ($block) {
            return isset($block['message'])
                && str_contains($block['message'], 'masih terdapat 1 pelaksana yang belum mengumpulkan');
        });
    }

    /* ---------- Dropdown: gate Surat Tugas untuk dokumen dependen ---------- */

    public function test_dropdown_laporan_diblokir_saat_st_belum_ada(): void
    {
        $this->setStStatus('Belum Ada');

        $response = $this->actingAs($this->user)
            ->from(route('checklists.edit', $this->laporanChecklist->id))
            ->put(route('checklists.update', $this->laporanChecklist->id), [
                'status' => 'Lengkap',
                'catatan' => null,
            ]);

        $response->assertRedirect(route('checklists.edit', $this->laporanChecklist->id));
        $this->assertEquals('Belum Lengkap', $this->laporanChecklist->fresh()->status);
        $response->assertSessionHas('status_block', function ($block) {
            return isset($block['message'])
                && str_contains($block['message'], 'Surat Tugas belum lengkap')
                && isset($block['link_href'])
                && str_contains($block['link_href'], route('checklists.edit', $this->stChecklist->id));
        });
    }

    public function test_dropdown_pengeluaran_riil_diblokir_saat_st_belum_lengkap(): void
    {
        $this->setStStatus('Belum Lengkap');

        $response = $this->actingAs($this->user)
            ->from(route('checklists.edit', $this->pengeluaranChecklist->id))
            ->put(route('checklists.update', $this->pengeluaranChecklist->id), [
                'status' => 'Lengkap',
                'catatan' => null,
            ]);

        $response->assertRedirect(route('checklists.edit', $this->pengeluaranChecklist->id));
        $this->assertEquals('Belum Lengkap', $this->pengeluaranChecklist->fresh()->status);
        $response->assertSessionHas('status_block');
    }

    public function test_dropdown_dependen_ikut_flow_normal_saat_st_lengkap(): void
    {
        $this->setStStatus('Lengkap');
        $this->seedCompleteStDetail();

        $response = $this->actingAs($this->user)
            ->from(route('checklists.edit', $this->pengeluaranChecklist->id))
            ->put(route('checklists.update', $this->pengeluaranChecklist->id), [
                'status' => 'Lengkap',
                'catatan' => null,
            ]);

        $response->assertRedirect(route('requests.show', $this->fpa->id));
        $this->assertEquals('Lengkap', $this->pengeluaranChecklist->fresh()->status);
        $response->assertSessionHas('success');
    }

    /* ---------- Helper ---------- */

    protected function setStStatus(string $status): void
    {
        $this->stChecklist->update(['status' => $status]);
    }

    protected function seedCompleteStDetail(): SuratTugasDetail
    {
        $detail = SuratTugasDetail::create([
            'checklist_id' => $this->stChecklist->id,
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-09-01',
            'isi_tugas' => 'Pendataan',
        ]);

        $this->addPelaksana($detail, 'Budi Santoso');

        return $detail;
    }

    protected function addPelaksana(SuratTugasDetail $detail, string $nama): void
    {
        $count = SuratTugasPelaksana::where('surat_tugas_detail_id', $detail->id)->count();

        SuratTugasPelaksana::create([
            'surat_tugas_detail_id' => $detail->id,
            'nama_pelaksana' => $nama,
            'nomor_surat' => 'B-1027.'.($count + 1).'/75040/KP.650/2026',
            'urutan' => $count + 1,
        ]);
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

    protected function markOneCollected(): void
    {
        $detail = $this->stChecklist->suratTugasDetail;

        TravelReportPelaksana::create([
            'checklist_id' => $this->laporanChecklist->id,
            'surat_tugas_pelaksana_id' => $detail->pelaksanas->first()->id,
            'status' => TravelReportPelaksana::STATUS_SUDAH,
        ]);
    }
}