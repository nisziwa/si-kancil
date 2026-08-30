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
}
