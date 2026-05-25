<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id',
        'kelas_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'status'
    ];

    protected $casts = [
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
    ];

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}
