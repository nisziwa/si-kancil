<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratTugasPelaksana extends Model
{
    protected $table = 'surat_tugas_pelaksanas';

    protected $fillable = ['surat_tugas_detail_id', 'nama_pelaksana', 'nomor_surat', 'urutan'];

    public function suratTugasDetail(): BelongsTo
    {
        return $this->belongsTo(SuratTugasDetail::class, 'surat_tugas_detail_id');
    }
}
