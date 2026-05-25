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
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();

            // Foreign key to tutor (user with role 'tutor')
            $table->foreignId('tutor_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Bank information
            $table->enum('bank', [
                'bca',
                'mandiri',
                'bni',
                'bri',
                'danamon',
                'permata',
                'dana',
                'gopay',
                'ovo',
                'shopeepay'
            ])->comment('Kode bank/e-wallet');

            $table->string('nomor_rekening', 50)->comment('Nomor rekening/e-wallet');
            $table->string('nama_pemilik', 255)->nullable()->comment('Nama pemilik rekening');

            // Amount information
            $table->decimal('nominal', 12, 2)->comment('Jumlah penarikan');
            $table->decimal('admin_fee', 12, 2)->default(2500)->comment('Biaya admin');
            $table->decimal('total_diterima', 12, 2)->nullable()
                ->comment('Nominal yang diterima (nominal - admin_fee)');

            // Status & processing
            $table->enum('status', [
                'pending',    // Menunggu verifikasi admin
                'proses',     // Sedang diproses transfer
                'success',    // Berhasil ditransfer
                'failed',     // Gagal transfer
                'rejected'    // Ditolak admin
            ])->default('pending');

            $table->timestamp('processed_at')->nullable()
                ->comment('Waktu ketika status berubah dari pending');

            // Additional info
            $table->text('notes')->nullable()->comment('Catatan dari tutor');
            $table->text('rejected_reason')->nullable()
                ->comment('Alasan penolakan jika status rejected');
            $table->string('proof_transfer')->nullable()
                ->comment('Path file bukti transfer (jika success)');

            $table->timestamps();

            // Indexes for performance
            $table->index(['tutor_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
