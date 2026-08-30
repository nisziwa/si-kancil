<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TravelReport extends Model
{
    protected $fillable = ['checklist_id', 'nama_pelaksana', 'tujuan', 'uraian_kegiatan', 'tanggal_kegiatan', 'dokumentasi'];
    protected function casts(): array { return ['tanggal_kegiatan' => 'date']; }
    public function checklist(): BelongsTo { return $this->belongsTo(SpjChecklist::class); }
}