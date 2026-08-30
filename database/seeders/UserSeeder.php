<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'sekprod@sikancil.local'],
            [
                'name' => 'Sekretaris Produksi',
                'email' => 'sekprod@sikancil.local',
                'password' => Hash::make('Sekprod7504!'),
                'email_verified_at' => now(),
            ]
        );
    }
}
