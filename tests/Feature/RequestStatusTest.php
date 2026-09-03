<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected ExpenseType $expenseType;

    protected FpaRequest $fpaRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->expenseType = ExpenseType::create([
            'nama' => 'Honor Petugas',
            'kode' => 'HONOR',
            'is_active' => true,
        ]);

        $this->fpaRequest = FpaRequest::create([
            'nomor_fpa' => 'FPA-TEST-001',
            'deskripsi_permintaan' => 'Testing Permintaan',
            'jenis_pengeluaran_id' => $this->expenseType->id,
            'periode' => 'Bulanan',
            'user_id' => $this->user->id,
            'status_spj' => 'Persiapan',
        ]);
    }

    private function createCompleteChecklists(): void
    {
        SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'FPA',
            'status' => 'Lengkap',
            'is_required' => true,
        ]);
        SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'KAK',
            'status' => 'Lengkap',
            'is_required' => true,
        ]);
    }

    private function createIncompleteChecklist(): void
    {
        SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'KAK',
            'status' => 'Belum Lengkap',
            'is_required' => true,
        ]);
    }

    public function test_cannot_go_to_dikirim_ppk_without_lengkap_checklist(): void
    {
        $this->createIncompleteChecklist();

        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Dikirim ke PPK',
            'tanggal_kirim_ppk' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('status_baru');
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Persiapan',
        ]);
    }

    public function test_cannot_go_to_dikirim_ppk_without_nomor_fpa(): void
    {
        $this->createCompleteChecklists();
        $this->fpaRequest->update(['nomor_fpa' => null]);

        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Dikirim ke PPK',
            'tanggal_kirim_ppk' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('status_baru');
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Persiapan',
        ]);
    }

    public function test_illegal_transition_is_blocked(): void
    {
        $this->createCompleteChecklists();

        // Persiapan -> Selesai tidak diperbolehkan
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Selesai',
            'tanggal_selesai_spj' => now()->format('Y-m-d'),
            'catatan' => 'Selesai',
        ]);

        $response->assertSessionHasErrors('status_baru');
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Persiapan',
        ]);
    }

    public function test_persiapan_to_dikirim_ppk_works_when_requirements_met(): void
    {
        $this->createCompleteChecklists();

        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Dikirim ke PPK',
            'tanggal_kirim_ppk' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('requests.show', $this->fpaRequest->id));
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Dikirim ke PPK',
        ]);
        $this->assertDatabaseHas('request_status_histories', [
            'request_id' => $this->fpaRequest->id,
            'status_lama' => 'Persiapan',
            'status_baru' => 'Dikirim ke PPK',
        ]);
    }

    public function test_dikirim_ke_ppk_can_skip_perbaikan_and_go_to_selesai(): void
    {
        $this->fpaRequest->update(['status_spj' => 'Dikirim ke PPK']);

        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Selesai',
            'tanggal_selesai_spj' => now()->format('Y-m-d'),
            'catatan' => 'SPJ telah selesai',
        ]);

        $response->assertRedirect(route('requests.show', $this->fpaRequest->id));
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Selesai',
        ]);
    }

    public function test_dikirim_ke_ppk_to_perbaikan_then_selesai(): void
    {
        $this->fpaRequest->update(['status_spj' => 'Dikirim ke PPK']);

        // -> Perbaikan
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Perbaikan',
            'catatan' => 'Perlu perbaikan kuitansi',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Perbaikan',
        ]);

        // Perbaikan -> Selesai
        $response2 = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Selesai',
            'tanggal_selesai_spj' => now()->format('Y-m-d'),
            'catatan' => 'Selesai setelah perbaikan',
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Selesai',
        ]);
    }

    public function test_ajax_status_update_validates_flow(): void
    {
        $this->createCompleteChecklists();

        // Persiapan -> Dikirim ke PPK via ajax
        $response = $this->actingAs($this->user)->patchJson(route('requests.status.ajax', $this->fpaRequest->id), [
            'status' => 'Dikirim ke PPK',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'new_status' => 'Dikirim ke PPK',
        ]);
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Dikirim ke PPK',
        ]);
    }

    public function test_ajax_rejects_illegal_transition(): void
    {
        // Persiapan -> Selesai tidak valid via ajax
        $response = $this->actingAs($this->user)->patchJson(route('requests.status.ajax', $this->fpaRequest->id), [
            'status' => 'Selesai',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Persiapan',
        ]);
    }

    public function test_fpa_can_be_created_without_nomor_fpa(): void
    {
        $response = $this->actingAs($this->user)->post(route('requests.store'), [
            'deskripsi_permintaan' => 'FPA tanpa nomor',
            'jenis_pengeluaran_id' => $this->expenseType->id,
            'periode' => 'Triwulanan',
            'status' => 'Persiapan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'deskripsi_permintaan' => 'FPA tanpa nomor',
            'nomor_fpa' => null,
        ]);
    }

    public function test_check_nomor_fpa_returns_available_for_new_number(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('requests.check-nomor-fpa', ['nomor' => 'FPA-BARU-001']));

        $response->assertOk();
        $this->assertTrue($response->json('available'));
    }

    public function test_check_nomor_fpa_detects_duplicate(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('requests.check-nomor-fpa', ['nomor' => 'FPA-TEST-001']));

        $response->assertOk();
        $response->assertJson(['available' => false, 'message' => 'Nomor FPA sudah digunakan.']);
    }

    public function test_check_nomor_fpa_ignores_self_when_editing(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('requests.check-nomor-fpa', [
            'nomor' => 'FPA-TEST-001',
            'ignore_id' => $this->fpaRequest->id,
        ]));

        $response->assertOk();
        $this->assertTrue($response->json('available'));
    }

    /* ---------- BUG #2: Parity validasi antara dropdown, kanban (ajax), & bulk ---------- */

    public function test_dropdown_to_selesai_requires_tanggal_selesai(): void
    {
        $this->fpaRequest->update(['status_spj' => 'Dikirim ke PPK']);

        // Dropdown ke Selesai tanpa tanggal -> ditolak dengan pesan yang jelas.
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Selesai',
            'catatan' => 'SPJ selesai',
        ]);

        $response->assertSessionHasErrors('status_baru');
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Dikirim ke PPK',
        ]);
    }

    public function test_dropdown_to_selesai_with_tanggal_works(): void
    {
        $this->fpaRequest->update(['status_spj' => 'Dikirim ke PPK']);

        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Selesai',
            'tanggal_selesai_spj' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('requests.show', $this->fpaRequest->id));
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Selesai',
        ]);
    }

    public function test_ajax_to_selesai_autofills_tanggal_and_works(): void
    {
        $this->fpaRequest->update(['status_spj' => 'Dikirim ke PPK']);

        // Kanban (ajax) tidak mengisi tanggal; service otomatis mengisinya hari ini.
        $response = $this->actingAs($this->user)->patchJson(route('requests.status.ajax', $this->fpaRequest->id), [
            'status' => 'Selesai',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'new_status' => 'Selesai']);
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Selesai',
        ]);
        // Tanggal selesai terisi otomatis hari ini oleh service.
        $this->assertNotNull($this->fpaRequest->fresh()->tanggal_selesai_spj);
    }

    public function test_dropdown_to_perbaikan_without_catatan_is_allowed(): void
    {
        $this->fpaRequest->update(['status_spj' => 'Dikirim ke PPK']);

        // Catatan optional di semua jalur (parity dengan kanban/bulk).
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Perbaikan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Perbaikan',
        ]);
    }

    /* ---------- BUG #3: FPA hanya boleh diedit saat berstatus Persiapan ---------- */

    public function test_fpa_only_editable_when_persiapan(): void
    {
        // Status Persiapan: edit diperbolehkan.
        $resp1 = $this->actingAs($this->user)->put(route('requests.update', $this->fpaRequest->id), [
            'deskripsi_permintaan' => 'Deskripsi diubah',
            'jenis_pengeluaran_id' => $this->expenseType->id,
            'periode' => 'Bulanan',
        ]);
        $resp1->assertRedirect();
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'deskripsi_permintaan' => 'Deskripsi diubah',
        ]);

        // Keluar dari Persiapan.
        $this->fpaRequest->update(['status_spj' => 'Dikirim ke PPK']);

        // Edit berikutnya ditolak.
        $resp2 = $this->actingAs($this->user)->put(route('requests.update', $this->fpaRequest->id), [
            'deskripsi_permintaan' => 'Coba edit lagi',
            'jenis_pengeluaran_id' => $this->expenseType->id,
            'periode' => 'Bulanan',
        ]);
        $resp2->assertSessionHas('error');
        $this->assertDatabaseMissing('requests', [
            'id' => $this->fpaRequest->id,
            'deskripsi_permintaan' => 'Coba edit lagi',
        ]);
    }
}
