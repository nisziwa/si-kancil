<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealExpenseDetail extends Model
{
    protected $fillable = ['checklist_id', 'nomor_surat_tugas', 'tanggal_surat_tugas', 'nama_pelaksana', 'jabatan', 'tanggal_kegiatan', 'uraian_pengeluaran', 'jumlah_pengeluaran', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal_surat_tugas' => 'date', 'tanggal_kegiatan' => 'date', 'jumlah_pengeluaran' => 'decimal:2'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SpjChecklist::class);
    }
}
