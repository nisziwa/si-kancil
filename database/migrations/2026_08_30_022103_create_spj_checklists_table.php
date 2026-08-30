<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('spj_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
            $table->string('nama_dokumen');
            $table->enum('status', [
                'Belum Ada',
                'Belum Lengkap',
                'Lengkap',
                'Perlu Perbaikan',
            ])->default('Belum Ada');
            $table->text('catatan')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spj_checklists');
    }
};
