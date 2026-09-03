<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratTugasValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected FpaRequest $fpaRequest;

    protected SpjChecklist $stChecklist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $expenseType = ExpenseType::create([
            'nama' => 'Perjalanan Dinas',
            'kode' => 'PERJADIN',
            'is_active' => true,
        ]);

        $this->fpaRequest = FpaRequest::create([
            'nomor_fpa' => 'FPA-ST-001',
            'deskripsi_permintaan' => 'Test Surat Tugas Validation',
            'jenis_pengeluaran_id' => $expenseType->id,
            'periode' => 'Triwulanan',
            'user_id' => $this->user->id,
            'status_spj' => 'Persiapan',
        ]);

        $this->stChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'Surat Tugas',
            'status' => 'Belum Lengkap',
            'is_required' => true,
        ]);
    }

    protected function updateStatus(array $payload)
    {
        return $this->actingAs($this->user)
            ->from(route('requests.show', $this->fpaRequest->id))
            ->put(route('checklists.update', $this->stChecklist->id), $payload);
    }

    public function test_surat_tugas_without_nomor_cannot_be_lengkap(): void
    {
        $this->updateStatus([
            'nomor_surat_tugas' => '',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => 'Uraian tugas',
            'pelaksana_nama' => ['Budi Santoso'],
            'status' => 'Lengkap',
        ])->assertSessionHas('error');

        $this->assertNotEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_surat_tugas_without_tanggal_cannot_be_lengkap(): void
    {
        $this->updateStatus([
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '',
            'isi_tugas' => 'Uraian tugas',
            'pelaksana_nama' => ['Budi Santoso'],
            'status' => 'Lengkap',
        ])->assertSessionHas('error');

        $this->assertNotEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_surat_tugas_without_uraian_cannot_be_lengkap(): void
    {
        $this->updateStatus([
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => '',
            'pelaksana_nama' => ['Budi Santoso'],
            'status' => 'Lengkap',
        ])->assertSessionHas('error');

        $this->assertNotEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_surat_tugas_without_pelaksana_cannot_be_lengkap(): void
    {
        $this->updateStatus([
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => 'Uraian tugas',
            'pelaksana_nama' => [],
            'status' => 'Lengkap',
        ])->assertSessionHas('error');

        $this->assertNotEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_dropdown_can_set_lengkap_when_complete(): void
    {
        $this->updateStatus([
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => 'Uraian tugas',
            'pelaksana_nama' => ['Budi Santoso'],
            'status' => 'Lengkap',
        ])->assertSessionHasNoErrors();

        $this->assertEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_kanban_rejects_lengkap_when_incomplete(): void
    {
        // Tidak ada detail sama sekali.
        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->stChecklist->id), ['status' => 'Lengkap']);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertNotEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_kanban_allows_lengkap_when_complete(): void
    {
        $this->seedCompleteDetail('B-1027/75040/KP.650/2026', '2026-08-25', 'Uraian tugas', ['Budi Santoso']);

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklists.status', $this->stChecklist->id), ['status' => 'Lengkap']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('Lengkap', $this->stChecklist->fresh()->status);
    }

    public function test_generate_superkendis_button_hidden_when_surat_tugas_not_lengkap(): void
    {
        $this->seedCompleteDetail('B-1027/75040/KP.650/2026', '2026-08-25', 'Uraian tugas', ['Budi Santoso']);

        $view = $this->actingAs($this->user)
            ->get(route('requests.show', $this->fpaRequest->id))
            ->assertOk();

        // Belum Lengkap -> tombol Generate Superkendis tidak muncul.
        $this->assertStringNotContainsString('Generate Superkendis', $view->getContent());
    }

    public function test_generate_superkendis_button_shown_when_surat_tugas_lengkap(): void
    {
        $this->seedCompleteDetail('B-1027/75040/KP.650/2026', '2026-08-25', 'Uraian tugas', ['Budi Santoso']);
        $this->stChecklist->update(['status' => 'Lengkap']);

        $view = $this->actingAs($this->user)
            ->get(route('requests.show', $this->fpaRequest->id))
            ->assertOk();

        $this->assertStringContainsString('Generate Superkendis', $view->getContent());
    }

    public function test_bulk_pelaksana_input_creates_multiple_pelaksana(): void
    {
        $response = $this->updateStatus([
            'nomor_surat_tugas' => 'B-1041/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => 'Uraian tugas',
            'pelaksana_nama' => ['Hamdia', 'Holil', 'Onal'],
            'status' => 'Lengkap',
        ]);

        $response->assertSessionHasNoErrors();

        $detail = SuratTugasDetail::where('checklist_id', $this->stChecklist->id)->first();
        $pelaksanas = SuratTugasPelaksana::where('surat_tugas_detail_id', $detail->id)->orderBy('urutan')->get();
        $this->assertCount(3, $pelaksanas);
        $this->assertEquals(['Hamdia', 'Holil', 'Onal'], $pelaksanas->pluck('nama_pelaksana')->all());
    }

    public function test_surat_sub_number_auto_generated(): void
    {
        $response = $this->updateStatus([
            'nomor_surat_tugas' => 'B-1041/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => 'Uraian tugas',
            'pelaksana_nama' => ['Hamdia', 'Holil', 'Onal'],
            'status' => 'Lengkap',
        ]);

        $response->assertSessionHasNoErrors();

        $detail = SuratTugasDetail::where('checklist_id', $this->stChecklist->id)->first();
        $nomors = SuratTugasPelaksana::where('surat_tugas_detail_id', $detail->id)
            ->orderBy('urutan')
            ->pluck('nomor_surat')
            ->all();

        $this->assertEquals([
            'B-1041.1/75040/KP.650/2026',
            'B-1041.2/75040/KP.650/2026',
            'B-1041.3/75040/KP.650/2026',
        ], $nomors);
    }

    protected function seedCompleteDetail(?string $nomor, ?string $tanggal, ?string $isi, array $pelaksanas): void
    {
        $detail = SuratTugasDetail::create([
            'checklist_id' => $this->stChecklist->id,
            'nomor_surat_tugas' => $nomor,
            'tanggal_surat_tugas' => $tanggal,
            'isi_tugas' => $isi,
        ]);

        foreach ($pelaksanas as $i => $nama) {
            SuratTugasPelaksana::create([
                'surat_tugas_detail_id' => $detail->id,
                'nama_pelaksana' => $nama,
                'nomor_surat' => 'B-1027.' . ($i + 1) . '/75040/KP.650/2026',
                'urutan' => $i + 1,
            ]);
        }
    }
}
