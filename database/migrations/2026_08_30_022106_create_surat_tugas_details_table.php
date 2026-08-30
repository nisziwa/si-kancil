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
        Schema::create('surat_tugas_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('spj_checklists')->onDelete('cascade');
            $table->string('nomor_surat_tugas');
            $table->date('tanggal_surat_tugas');
            $table->text('pelaksana');
            $table->text('isi_tugas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_tugas_details');
    }
};
