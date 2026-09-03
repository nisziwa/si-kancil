<?php

namespace Tests\Feature;

use App\Models\SkRatePerjalanan;
use App\Models\SkRatePerjalananHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkRatePerjalananTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_page_lists_sk_rates(): void
    {
        SkRatePerjalanan::create([
            'kecamatan' => 'TAPA',
            'ibukota_kecamatan' => 'TALUMOPATU',
            'besaran_biaya_transport' => 50000,
        ]);

        $response = $this->actingAs($this->user)->get(route('sk-rates.index'));
        $response->assertOk();
        $response->assertSee('TAPA');
        $response->assertSee('TALUMOPATU');
    }

    public function test_index_search_filters_by_kecamatan(): void
    {
        SkRatePerjalanan::create([
            'kecamatan' => 'TAPA',
            'ibukota_kecamatan' => 'TALUMOPATU',
            'besaran_biaya_transport' => 50000,
        ]);
        SkRatePerjalanan::create([
            'kecamatan' => 'BONE',
            'ibukota_kecamatan' => 'TALUDAA',
            'besaran_biaya_transport' => 160000,
        ]);

        $response = $this->actingAs($this->user)->get(route('sk-rates.index', ['search' => 'BONE']));
        $response->assertOk();
        $response->assertSee('BONE');
        $this->assertStringNotContainsString('TAPA', $response->getContent());
    }

    public function test_store_creates_rate_and_history(): void
    {
        $response = $this->actingAs($this->user)->post(route('sk-rates.store'), [
            'kecamatan' => 'BONE',
            'ibukota_kecamatan' => 'TALUDAA',
            'besaran_biaya_transport' => 160000,
            'keterangan' => 'SK baru',
        ]);

        $response->assertRedirect(route('sk-rates.index'));

        $this->assertDatabaseHas('sk_rate_perjalanan', [
            'kecamatan' => 'BONE',
            'besaran_biaya_transport' => 160000,
        ]);

        $rate = SkRatePerjalanan::where('kecamatan', 'BONE')->first();
        $this->assertDatabaseHas('sk_rate_perjalanan_histories', [
            'sk_rate_perjalanan_id' => $rate->id,
            'aksi' => 'create',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_update_changes_value_and_records_before_after(): void
    {
        $rate = SkRatePerjalanan::create([
            'kecamatan' => 'BONE',
            'ibukota_kecamatan' => 'TALUDAA',
            'besaran_biaya_transport' => 100000,
        ]);

        $response = $this->actingAs($this->user)->put(route('sk-rates.update', $rate->id), [
            'kecamatan' => 'BONE',
            'ibukota_kecamatan' => 'TALUDAA',
            'besaran_biaya_transport' => 175000,
            'keterangan' => 'Perubahan rate',
        ]);

        $response->assertRedirect(route('sk-rates.index'));
        $this->assertDatabaseHas('sk_rate_perjalanan', [
            'id' => $rate->id,
            'besaran_biaya_transport' => 175000,
        ]);

        $history = SkRatePerjalananHistory::where('sk_rate_perjalanan_id', $rate->id)
            ->where('aksi', 'update')
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('100000', $history->data_sebelum);
        $this->assertStringContainsString('175000', $history->data_sesudah);
        $this->assertEquals($this->user->id, $history->user_id);
    }

    public function test_destroy_deletes_rate_and_records_history(): void
    {
        $rate = SkRatePerjalanan::create([
            'kecamatan' => 'BONE',
            'ibukota_kecamatan' => 'TALUDAA',
            'besaran_biaya_transport' => 160000,
        ]);

        $response = $this->actingAs($this->user)->delete(route('sk-rates.destroy', $rate->id));
        $response->assertRedirect(route('sk-rates.index'));

        $this->assertDatabaseMissing('sk_rate_perjalanan', [
            'id' => $rate->id,
        ]);

        $history = SkRatePerjalananHistory::where('aksi', 'delete')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('BONE', $history->data_sebelum);
    }

    public function test_edit_page_shows_history(): void
    {
        $rate = SkRatePerjalanan::create([
            'kecamatan' => 'BONE',
            'ibukota_kecamatan' => 'TALUDAA',
            'besaran_biaya_transport' => 160000,
        ]);

        $this->actingAs($this->user)->post(route('sk-rates.store'), [
            'kecamatan' => 'BONE',
            'ibukota_kecamatan' => 'TALUDAA',
            'besaran_biaya_transport' => 165000,
        ]);

        $response = $this->actingAs($this->user)->get(route('sk-rates.edit', $rate->id));
        $response->assertOk();
        $response->assertSee('Riwayat Perubahan SK Rate');
    }
}
