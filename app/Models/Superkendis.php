<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Superkendis extends Model
{
    protected $fillable = [
        'surat_tugas_pelaksana_id',
        'nip',
        'kecamatan',
        'tanggal_perjalanan',
        'jenis_kegiatan',
        'jabatan',
        'file_docx',
        'file_pdf',
    ];

    protected function casts(): array
    {
        return ['tanggal_perjalanan' => 'date'];
    }

    public function suratTugasPelaksana(): BelongsTo
    {
        return $this->belongsTo(SuratTugasPelaksana::class, 'surat_tugas_pelaksana_id');
    }
}
