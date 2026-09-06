<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('travel_reports');

        Schema::create('travel_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fpa_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('surat_tugas_pelaksana_id')->constrained('surat_tugas_pelaksanas')->cascadeOnDelete();
            $table->string('jenis_laporan')
                ->default('LAPORAN_PENDATAAN');
            $table->string('judul_laporan');
            $table->date('tanggal_laporan')->nullable();
            $table->foreignId('pok_rincian_id')->nullable()->constrained('master_rincian_pok')->nullOnDelete();
            $table->string('file_docx')->nullable();
            $table->string('file_pdf')->nullable();
            $table->timestamps();

            $table->index(['fpa_id', 'surat_tugas_pelaksana_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_reports');

        Schema::create('travel_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('spj_checklists')->onDelete('cascade');
            $table->string('nama_pelaksana');
            $table->string('tujuan');
            $table->text('uraian_kegiatan');
            $table->date('tanggal_kegiatan');
            $table->string('dokumentasi')->nullable();
            $table->timestamps();
        });
    }
};
