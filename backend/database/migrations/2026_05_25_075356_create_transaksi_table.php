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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->decimal('jumlah', 10, 2);
            $table->enum('metode_pembayaran', ['transfer_bank', 'gopay', 'ovo', 'dana', 'cod']);
            $table->enum('status_pembayaran', ['pending', 'lunas', 'gagal', 'refund'])->default('pending');
            $table->date('tanggal_pembayaran')->nullable();
            $table->string('bukti_pembayaran')->nullable(); // path file
            $table->string('no_rekening')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
