<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkRatePerjalanan extends Model
{
    protected $table = 'sk_rate_perjalanan';

    protected $fillable = ['kecamatan', 'ibukota_kecamatan', 'besaran_biaya_transport', 'keterangan'];

    protected function casts(): array
    {
        return ['besaran_biaya_transport' => 'decimal:2'];
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SkRatePerjalananHistory::class, 'sk_rate_perjalanan_id');
    }
}
