<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_kelas',
        'deskripsi',
        'tingkat',
        'harga_per_sesi',
        'gambar'
    ];

    protected $casts = [
        'harga_per_sesi' => 'decimal:2',
    ];

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class);
    }

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class);
    }

    public function materi()
    {
        return $this->hasMany(Materi::class);
    }
}
