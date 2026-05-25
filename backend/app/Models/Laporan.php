<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    protected $fillable = ['pelapor_id', 'judul', 'isi', 'kategori', 'status'];

    public function pelapor()
    {
        return $this->belongsTo(User::class, 'pelapor_id');
    }
}
