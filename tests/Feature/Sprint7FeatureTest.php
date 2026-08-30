<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint7FeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ExpenseType $expenseType;
    protected FpaRequest $fpaRequest;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->expenseType = ExpenseType::create([
            'nama' => 'Meeting Kantor',
            'kode' => 'MEETING_KTR',
            'is_active' => true,
        ]);

        $this->fpaRequest = FpaRequest::create([
            'nomor_fpa' => 'FPA-DASH-001',
            'deskripsi_permintaan' => 'Rapat Evaluasi Triwulan',
            'jenis_pengeluaran_id' => $this->expenseType->id,
            'periode' => 'Agustus 2026',
            'user_id' => $this->user->id,
            'status_spj' => 'Pengumpulan SPJ',
            'tanggal_mulai' => '2026-08-25',
            'tanggal_selesai' => '2026-08-26',
            'lokasi' => 'Aula BPS',
            'deadline_spj' => '2026-08-31',
        ]);
    }

    public function test_dashboard_renders_with_stats_and_kanban(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Kanban Monitoring Posisi SPJ');
        $response->assertSee('FPA-DASH-001');
        $response->assertSee('Rapat Evaluasi Triwulan');
    }

    public function test_calendar_page_and_events_api(): void
    {
        // Page view
        $response = $this->actingAs($this->user)->get(route('calendar.index'));
        $response->assertOk();
        $response->assertSee('Kalender Jadwal Kegiatan SPJ');

        // Events JSON
        $eventsResponse = $this->actingAs($this->user)->get(route('calendar.events'));
        $eventsResponse->assertOk();
        $eventsResponse->assertJsonFragment([
            'id' => $this->fpaRequest->id,
            'title' => "FPA-DASH-001 - Rapat Evaluasi Triwulan",
        ]);
    }

    public function test_template_crud_workflow(): void
    {
        $file = UploadedFile::fake()->create('format_kak.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // 1. Create
        $response = $this->actingAs($this->user)->post(route('templates.store'), [
            'nama_template' => 'Format KAK Standar',
            'kategori' => 'KAK',
            'versi' => 'v1.0',
            'file' => $file,
            'status_aktif' => true,
        ]);

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('templates', [
            'nama_template' => 'Format KAK Standar',
            'kategori' => 'KAK',
            'versi' => 'v1.0',
            'status_aktif' => true,
        ]);

        $template = Template::where('nama_template', 'Format KAK Standar')->first();
        $this->assertNotNull($template);
        Storage::disk('public')->assertExists($template->file);

        // 2. Index
        $indexResponse = $this->actingAs($this->user)->get(route('templates.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Format KAK Standar');

        // 3. Update
        $updateResponse = $this->actingAs($this->user)->put(route('templates.update', $template->id), [
            'nama_template' => 'Format KAK Standar Revisi',
            'kategori' => 'KAK',
            'versi' => 'v2.0',
            'status_aktif' => true,
        ]);
        $updateResponse->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'nama_template' => 'Format KAK Standar Revisi',
            'versi' => 'v2.0',
        ]);

        // 4. Download
        $downloadResponse = $this->actingAs($this->user)->get(route('templates.download', $template->id));
        $downloadResponse->assertOk();

        // 5. Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('templates.destroy', $template->id));
        $deleteResponse->assertRedirect(route('templates.index'));
        $this->assertDatabaseMissing('templates', [
            'id' => $template->id,
        ]);
    }
}
