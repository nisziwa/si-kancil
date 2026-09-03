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
        Schema::create('travel_report_pelaksanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('spj_checklists')->onDelete('cascade');
            $table->foreignId('surat_tugas_pelaksana_id')->constrained('surat_tugas_pelaksanas')->onDelete('cascade');
            $table->string('status')->default('Belum Mengumpulkan');
            $table->timestamps();

            $table->unique(['checklist_id', 'surat_tugas_pelaksana_id'], 'travel_report_pelaksana_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_report_pelaksanas');
    }
};
