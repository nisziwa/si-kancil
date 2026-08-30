<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelDetail extends Model
{
    protected $fillable = ['checklist_id', 'nomor_spd', 'nama_pelaksana', 'maksud_perjalanan', 'tempat_berangkat', 'tempat_tujuan', 'tanggal_berangkat', 'tanggal_kembali', 'transportasi'];

    protected function casts(): array
    {
        return ['tanggal_berangkat' => 'date', 'tanggal_kembali' => 'date'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SpjChecklist::class);
    }
}
