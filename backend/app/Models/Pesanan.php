<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'tutor_id',
        'kelas_id',
        'tanggal',
        'jam',
        'status',
        'catatan_siswa',
        'catatan_tutor'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam' => 'datetime:H:i',
    ];

    public function siswa()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function transaksi()
    {
        return $this->hasOne(Transaksi::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
