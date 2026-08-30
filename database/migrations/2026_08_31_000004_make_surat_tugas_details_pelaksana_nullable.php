<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_tugas_details', function (Blueprint $table) {
            $table->text('pelaksana')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('surat_tugas_details', function (Blueprint $table) {
            $table->text('pelaksana')->nullable(false)->change();
        });
    }
};
