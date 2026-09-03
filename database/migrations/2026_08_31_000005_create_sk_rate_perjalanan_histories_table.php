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
        Schema::create('sk_rate_perjalanan_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sk_rate_perjalanan_id')->nullable();
            $table->foreign('sk_rate_perjalanan_id')->references('id')->on('sk_rate_perjalanan')->nullOnDelete();
            $table->text('data_sebelum')->nullable();
            $table->text('data_sesudah')->nullable();
            $table->text('aksi')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sk_rate_perjalanan_histories');
    }
};
