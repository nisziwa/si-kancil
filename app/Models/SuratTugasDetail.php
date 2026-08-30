<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratTugasDetail extends Model
{
    protected $fillable = ['checklist_id', 'nomor_surat_tugas', 'tanggal_surat_tugas', 'pelaksana', 'isi_tugas'];

    protected function casts(): array
    {
        return ['tanggal_surat_tugas' => 'date'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SpjChecklist::class);
    }

    public function pelaksanas(): HasMany
    {
        return $this->hasMany(SuratTugasPelaksana::class, 'surat_tugas_detail_id')->orderBy('urutan');
    }
}
