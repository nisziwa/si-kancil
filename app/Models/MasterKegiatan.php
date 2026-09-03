<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterKegiatan extends Model
{
    protected $table = 'master_kegiatan';

    protected $fillable = ['program_id', 'kode_kegiatan', 'nama_kegiatan'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(MasterProgram::class, 'program_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(MasterOutput::class, 'kegiatan_id');
    }
}
