<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FpaStatusServiceBulkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected ExpenseType $expenseType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->expenseType = ExpenseType::create([
            'nama' => 'Honor Petugas',
            'kode' => 'HONOR',
            'is_active' => true,
        ]);
    }

    private function makeRequest(string $nomor = null, string $status = 'Persiapan'): FpaRequest
    {
        return FpaRequest::create([
            'nomor_fpa' => $nomor,
            'deskripsi_permintaan' => 'Permintaan bulk test',
            'jenis_pengeluaran_id' => $this->expenseType->id,
            'periode' => 'Bulanan',
            'user_id' => $this->user->id,
            'status_spj' => $status,
        ]);
    }

    private function complete(string $id): void
    {
        SpjChecklist::create(['request_id' => $id, 'nama_dokumen' => 'FPA', 'status' => 'Lengkap', 'is_required' => true]);
        SpjChecklist::create(['request_id' => $id, 'nama_dokumen' => 'KAK', 'status' => 'Lengkap', 'is_required' => true]);
    }

    public function test_bulk_move_valid_and_failed_partial(): void
    {
        // FPA valid: nomor + checklist lengkap -> bisa ke Dikirim ke PPK.
        $valid = $this->makeRequest('FPA-VALID-001', 'Persiapan');
        $this->complete($valid->id);

        // FPA gagal: tanpa nomor -> tidak bisa Dikirim ke PPK.
        $invalid = $this->makeRequest(null, 'Persiapan');
        $this->complete($invalid->id);

        $response = $this->actingAs($this->user)->postJson(route('requests.status.bulk'), [
            'ids' => [$valid->id, $invalid->id],
            'status' => 'Dikirim ke PPK',
        ]);

        $response->assertOk();
        $data = $response->json('results');

        // Satu berhasil, satu gagal (bukan rollback seluruh proses).
        $this->assertCount(1, $data['success']);
        $this->assertCount(1, $data['failed']);
        $this->assertSame('FPA-VALID-001', $data['success'][0]['nomor_fpa']);
        $this->assertSame($invalid->id, $data['failed'][0]['id'] ?? $invalid->id);

        $this->assertDatabaseHas('requests', ['id' => $valid->id, 'status_spj' => 'Dikirim ke PPK']);
        $this->assertDatabaseHas('requests', ['id' => $invalid->id, 'status_spj' => 'Persiapan']);
    }

    public function test_bulk_move_illegal_transition_fails(): void
    {
        // Persiapan -> Selesai tidak diperbolehkan.
        $fpa = $this->makeRequest('FPA-001', 'Persiapan');

        $response = $this->actingAs($this->user)->postJson(route('requests.status.bulk'), [
            'ids' => [$fpa->id],
            'status' => 'Selesai',
        ]);

        $response->assertOk();
        $this->assertCount(0, $response->json('results.success'));
        $this->assertCount(1, $response->json('results.failed'));
        $this->assertDatabaseHas('requests', ['id' => $fpa->id, 'status_spj' => 'Persiapan']);
    }

    public function test_bulk_move_all_success(): void
    {
        $fpa1 = $this->makeRequest('FPA-001', 'Persiapan');
        $this->complete($fpa1->id);
        $fpa2 = $this->makeRequest('FPA-002', 'Persiapan');
        $this->complete($fpa2->id);

        $response = $this->actingAs($this->user)->postJson(route('requests.status.bulk'), [
            'ids' => [$fpa1->id, $fpa2->id],
            'status' => 'Dikirim ke PPK',
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('results.success'));
        $this->assertCount(0, $response->json('results.failed'));
        $this->assertDatabaseHas('requests', ['id' => $fpa1->id, 'status_spj' => 'Dikirim ke PPK']);
        $this->assertDatabaseHas('requests', ['id' => $fpa2->id, 'status_spj' => 'Dikirim ke PPK']);
    }
}
