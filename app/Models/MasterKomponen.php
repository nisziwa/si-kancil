<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterKomponen extends Model
{
    protected $table = 'master_komponen';

    protected $fillable = ['sub_output_id', 'kode_komponen', 'nama_komponen'];

    public function subOutput(): BelongsTo
    {
        return $this->belongsTo(MasterSubOutput::class, 'sub_output_id');
    }
}
