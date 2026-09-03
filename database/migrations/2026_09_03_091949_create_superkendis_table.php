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
        Schema::create('superkendis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_tugas_pelaksana_id')->constrained('surat_tugas_pelaksanas')->onDelete('cascade');
            $table->string('nip')->nullable();
            $table->string('kecamatan')->nullable();
            $table->date('tanggal_perjalanan')->nullable();
            $table->string('jenis_kegiatan')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('file_docx')->nullable();
            $table->string('file_pdf')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('superkendis');
    }
};
