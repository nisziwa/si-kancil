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
        Schema::create('travel_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('spj_checklists')->onDelete('cascade');
            $table->string('nomor_spd');
            $table->string('nama_pelaksana');
            $table->text('maksud_perjalanan');
            $table->string('tempat_berangkat');
            $table->string('tempat_tujuan');
            $table->date('tanggal_berangkat');
            $table->date('tanggal_kembali');
            $table->string('transportasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_details');
    }
};
