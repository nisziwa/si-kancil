<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkRatePerjalananHistory extends Model
{
    protected $table = 'sk_rate_perjalanan_histories';

    protected $fillable = [
        'sk_rate_perjalanan_id',
        'data_sebelum',
        'data_sesudah',
        'aksi',
        'user_id',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function skRate(): BelongsTo
    {
        return $this->belongsTo(SkRatePerjalanan::class, 'sk_rate_perjalanan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
