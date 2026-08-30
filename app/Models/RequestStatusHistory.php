<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['request_id', 'status_lama', 'status_baru', 'catatan', 'file_bukti', 'user_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
