<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'sekprod@sikancil.local',
        ]);

        $this->assertDatabaseHas('expense_types', [
            'kode' => 'PERJADIN',
        ]);

        $this->assertDatabaseCount('sk_rate_perjalanan', 18);

        $this->assertDatabaseHas('sk_rate_perjalanan', [
            'kecamatan' => 'TAPA',
            'ibukota_kecamatan' => 'TALUMOPATU',
            'besaran_biaya_transport' => 50000,
        ]);
    }
}
