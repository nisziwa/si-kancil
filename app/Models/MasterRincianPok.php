<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterRincianPok extends Model
{
    protected $table = 'master_rincian_pok';

    protected $fillable = [
        'program_id', 'kegiatan_id', 'output_id', 'sub_output_id',
        'komponen_id', 'akun_id', 'rincian',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(MasterProgram::class, 'program_id');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(MasterKegiatan::class, 'kegiatan_id');
    }

    public function output(): BelongsTo
    {
        return $this->belongsTo(MasterOutput::class, 'output_id');
    }

    public function subOutput(): BelongsTo
    {
        return $this->belongsTo(MasterSubOutput::class, 'sub_output_id');
    }

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(MasterKomponen::class, 'komponen_id');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(MasterAkun::class, 'akun_id');
    }
}
