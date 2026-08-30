<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['checklist_id', 'status_lama', 'status_baru', 'catatan', 'user_id'];

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

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SpjChecklist::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
