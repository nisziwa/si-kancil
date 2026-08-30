<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkRatePerjalanan extends Model
{
    protected $table = 'sk_rate_perjalanan';

    protected $fillable = ['kecamatan', 'besaran_biaya_transport', 'keterangan'];

    protected function casts(): array
    {
        return ['besaran_biaya_transport' => 'decimal:2'];
    }
}
