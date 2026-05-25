<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'photo',
        'status',
        'bio',
        'keahlian',
        'harga_per_jam'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'harga_per_jam' => 'decimal:2',
    ];

    // RELASI
    public function jadwal()
    {
        return $this->hasMany(Jadwal::class);
    }

    public function pesananSebagaiSiswa()
    {
        return $this->hasMany(Pesanan::class, 'siswa_id');
    }

    public function pesananSebagaiTutor()
    {
        return $this->hasMany(Pesanan::class, 'tutor_id');
    }

    public function materi()
    {
        return $this->hasMany(Materi::class);
    }

    public function reviewDiberikan()
    {
        return $this->hasMany(Review::class, 'siswa_id');
    }

    public function reviewDiterima()
    {
        return $this->hasMany(Review::class, 'tutor_id');
    }

    public function transaksiSebagaiSiswa()
    {
        return $this->hasMany(Transaksi::class, 'siswa_id');
    }

    public function transaksiSebagaiTutor()
    {
        return $this->hasMany(Transaksi::class, 'tutor_id');
    }
}
