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
        Schema::create('surat_tugas_pelaksanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_tugas_detail_id')->constrained('surat_tugas_details')->onDelete('cascade');
            $table->string('nama_pelaksana');
            $table->string('nomor_surat')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_tugas_pelaksanas');
    }
};
