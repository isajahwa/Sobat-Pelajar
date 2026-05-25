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
        Schema::create('laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelapor_id')->constrained('users')->onDelete('cascade');
            $table->string('judul');
            $table->text('isi');
            $table->enum('kategori', ['teknis', 'pembayaran', 'tutor', 'umum'])->default('umum');
            $table->enum('status', ['pending', 'proses', 'selesai', 'ditolak'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
