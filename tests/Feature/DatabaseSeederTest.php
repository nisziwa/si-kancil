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

        $this->assertDatabaseHas('templates', [
            'kategori' => 'KAK',
        ]);

        $this->assertDatabaseHas('requests', [
            'nomor_fpa' => 'FPA/2026/08/001',
        ]);
    }
}
