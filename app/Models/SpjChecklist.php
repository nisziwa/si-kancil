<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpjChecklist extends Model
{
    protected $fillable = ['request_id', 'nama_dokumen', 'status', 'is_required', 'catatan', 'file_path', 'urutan'];

    public const STATUS_LIST = ['Belum Ada', 'Belum Lengkap', 'Lengkap', 'Perlu Perbaikan'];

    public const STATUS_COLORS = [
        'Belum Ada' => 'gray', 'Belum Lengkap' => 'yellow',
        'Lengkap' => 'green', 'Perlu Perbaikan' => 'red',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ChecklistHistory::class)->orderByDesc('created_at');
    }

    public function suratTugasDetail(): HasOne
    {
        return $this->hasOne(SuratTugasDetail::class, 'checklist_id');
    }

    public function travelDetail(): HasOne
    {
        return $this->hasOne(TravelDetail::class, 'checklist_id');
    }

    public function realExpenseDetail(): HasOne
    {
        return $this->hasOne(RealExpenseDetail::class, 'checklist_id');
    }

    public function travelReportPelaksanas(): HasMany
    {
        return $this->hasMany(TravelReportPelaksana::class, 'checklist_id');
    }
}
