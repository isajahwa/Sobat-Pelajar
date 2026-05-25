<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'pesanan_id',
        'siswa_id',
        'tutor_id',
        'rating',
        'komentar'
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

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
