<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = ['nama_template', 'kategori', 'versi', 'file', 'status_aktif'];

    protected function casts(): array
    {
        return ['status_aktif' => 'boolean'];
    }

    public const KATEGORI_LIST = ['KAK', 'Surat Tugas', 'Laporan Perjalanan', 'Visum', 'Superkendis', 'Dokumen SPJ'];
}
