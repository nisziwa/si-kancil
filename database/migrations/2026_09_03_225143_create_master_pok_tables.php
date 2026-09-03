<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_program', function (Blueprint $table) {
            $table->id();
            $table->string('kode_program');
            $table->string('nama_program');
            $table->timestamps();
        });

        Schema::create('master_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('master_program')->cascadeOnDelete();
            $table->string('kode_kegiatan');
            $table->string('nama_kegiatan');
            $table->timestamps();
        });

        Schema::create('master_output', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('master_kegiatan')->cascadeOnDelete();
            $table->string('kode_output');
            $table->string('nama_output');
            $table->timestamps();
        });

        Schema::create('master_sub_output', function (Blueprint $table) {
            $table->id();
            $table->foreignId('output_id')->constrained('master_output')->cascadeOnDelete();
            $table->string('kode_sub_output');
            $table->string('nama_sub_output');
            $table->timestamps();
        });

        Schema::create('master_komponen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_output_id')->constrained('master_sub_output')->cascadeOnDelete();
            $table->string('kode_komponen');
            $table->string('nama_komponen');
            $table->timestamps();
        });

        Schema::create('master_akun', function (Blueprint $table) {
            $table->id();
            $table->string('kode_akun');
            $table->string('nama_akun');
            $table->timestamps();
        });

        Schema::create('master_rincian_pok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('master_program')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->constrained('master_kegiatan')->cascadeOnDelete();
            $table->foreignId('output_id')->constrained('master_output')->cascadeOnDelete();
            $table->foreignId('sub_output_id')->constrained('master_sub_output')->cascadeOnDelete();
            $table->foreignId('komponen_id')->constrained('master_komponen')->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained('master_akun')->cascadeOnDelete();
            $table->text('rincian');
            $table->timestamps();

            $table->index('rincian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_rincian_pok');
        Schema::dropIfExists('master_akun');
        Schema::dropIfExists('master_komponen');
        Schema::dropIfExists('master_sub_output');
        Schema::dropIfExists('master_output');
        Schema::dropIfExists('master_kegiatan');
        Schema::dropIfExists('master_program');
    }
};
