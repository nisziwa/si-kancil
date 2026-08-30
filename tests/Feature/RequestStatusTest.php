<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
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
            'periode' => 'Agustus 2026',
            'user_id' => $this->user->id,
            'status_spj' => 'Persiapan',
        ]);
    }

    public function test_user_can_update_status_to_pelaksanaan(): void
    {
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Pelaksanaan',
            'catatan' => 'Mulai pelaksanaan kegiatan',
        ]);

        $response->assertRedirect(route('requests.show', $this->fpaRequest->id));
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Pelaksanaan',
        ]);
        $this->assertDatabaseHas('request_status_histories', [
            'request_id' => $this->fpaRequest->id,
            'status_lama' => 'Persiapan',
            'status_baru' => 'Pelaksanaan',
            'catatan' => 'Mulai pelaksanaan kegiatan',
        ]);
    }

    public function test_update_to_dikirim_ke_ppk_requires_tanggal_kirim(): void
    {
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Dikirim ke PPK',
        ]);

        $response->assertSessionHasErrors('tanggal_kirim_ppk');
    }

    public function test_update_to_perbaikan_requires_catatan(): void
    {
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Perbaikan',
        ]);

        $response->assertSessionHasErrors('catatan');
    }

    public function test_update_to_selesai_requires_tanggal_selesai_and_catatan(): void
    {
        $response = $this->actingAs($this->user)->post(route('requests.status.update', $this->fpaRequest->id), [
            'status_baru' => 'Selesai',
        ]);

        $response->assertSessionHasErrors(['tanggal_selesai_spj', 'catatan']);
    }

    public function test_ajax_status_update_works_and_logs_history(): void
    {
        $response = $this->actingAs($this->user)->patchJson(route('requests.status.ajax', $this->fpaRequest->id), [
            'status' => 'Pengumpulan SPJ',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'new_status' => 'Pengumpulan SPJ',
        ]);
        $this->assertDatabaseHas('requests', [
            'id' => $this->fpaRequest->id,
            'status_spj' => 'Pengumpulan SPJ',
        ]);
    }
}
