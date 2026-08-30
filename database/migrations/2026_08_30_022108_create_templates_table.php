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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama_template');
            $table->enum('kategori', [
                'KAK',
                'Surat Tugas',
                'Laporan Perjalanan',
                'Visum',
                'Superkendis',
                'Dokumen SPJ'
            ]);
            $table->string('versi')->nullable();
            $table->string('file')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
