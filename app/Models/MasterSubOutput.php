<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterSubOutput extends Model
{
    protected $table = 'master_sub_output';

    protected $fillable = ['output_id', 'kode_sub_output', 'nama_sub_output'];

    public function output(): BelongsTo
    {
        return $this->belongsTo(MasterOutput::class, 'output_id');
    }

    public function komponens(): HasMany
    {
        return $this->hasMany(MasterKomponen::class, 'sub_output_id');
    }
}
