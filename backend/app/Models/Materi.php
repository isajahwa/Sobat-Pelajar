<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id',
        'kelas_id',
        'judul',
        'deskripsi',
        'file_path',
        'tipe_file',
        'views'
    ];

    protected $casts = [
        'views' => 'integer',
    ];

    public function tutor()
    {
        return $this->belongsTo(User::class);
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}
