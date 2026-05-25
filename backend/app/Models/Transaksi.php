<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'pesanan_id',
        'siswa_id',
        'tutor_id',
        'jumlah',
        'metode_pembayaran',
        'status_pembayaran',
        'tanggal_pembayaran',
        'bukti_pembayaran',
        'no_rekening'
    ];

    protected $casts = ['jumlah' => 'decimal:2', 'tanggal_pembayaran' => 'date'];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class);
    }
    public function siswa()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
