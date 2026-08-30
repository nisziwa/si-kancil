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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_fpa')->unique();
            $table->text('deskripsi_permintaan');
            $table->foreignId('jenis_pengeluaran_id')->constrained('expense_types')->onDelete('restrict');
            $table->string('periode')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->date('deadline_spj')->nullable();
            $table->enum('status_spj', [
                'Persiapan',
                'Pelaksanaan',
                'Pengumpulan SPJ',
                'Dikirim ke PPK',
                'Perbaikan',
                'Selesai'
            ])->default('Persiapan');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal_kirim_ppk')->nullable();
            $table->date('tanggal_selesai_spj')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
