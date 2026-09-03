<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProgram extends Model
{
    protected $table = 'master_program';

    protected $fillable = ['kode_program', 'nama_program'];

    public function kegiatans(): HasMany
    {
        return $this->hasMany(MasterKegiatan::class, 'program_id');
    }
}
