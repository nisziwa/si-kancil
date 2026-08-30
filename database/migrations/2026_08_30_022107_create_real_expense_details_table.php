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
        Schema::create('real_expense_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('spj_checklists')->onDelete('cascade');
            $table->string('nomor_surat_tugas');
            $table->date('tanggal_surat_tugas');
            $table->string('nama_pelaksana');
            $table->string('jabatan');
            $table->date('tanggal_kegiatan');
            $table->text('uraian_pengeluaran');
            $table->decimal('jumlah_pengeluaran', 15, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('real_expense_details');
    }
};
