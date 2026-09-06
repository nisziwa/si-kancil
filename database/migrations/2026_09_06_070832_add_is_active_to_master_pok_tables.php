<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'master_program',
        'master_kegiatan',
        'master_output',
        'master_sub_output',
        'master_komponen',
        'master_akun',
        'master_rincian_pok',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};