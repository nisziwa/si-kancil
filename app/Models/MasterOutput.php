<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterOutput extends Model
{
    protected $table = 'master_output';

    protected $fillable = ['kegiatan_id', 'kode_output', 'nama_output'];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(MasterKegiatan::class, 'kegiatan_id');
    }

    public function subOutputs(): HasMany
    {
        return $this->hasMany(MasterSubOutput::class, 'output_id');
    }
}
