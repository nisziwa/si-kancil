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
        Schema::create('sk_rate_perjalanan', function (Blueprint $table) {
            $table->id();
            $table->string('kecamatan');
            $table->string('ibukota_kecamatan');
            $table->decimal('besaran_biaya_transport', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sk_rate_perjalanan');
    }
};
