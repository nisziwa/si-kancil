<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelReportPelaksana extends Model
{
    /**
     * Status pengumpulan laporan per pelaksana.
     */
    public const STATUS_SUDAH = 'Sudah Mengumpulkan';

    public const STATUS_BELUM = 'Belum Mengumpulkan';

    public const STATUS_LIST = [self::STATUS_BELUM, self::STATUS_SUDAH];

    protected $fillable = ['checklist_id', 'surat_tugas_pelaksana_id', 'status'];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SpjChecklist::class);
    }

    public function suratTugasPelaksana(): BelongsTo
    {
        return $this->belongsTo(SuratTugasPelaksana::class, 'surat_tugas_pelaksana_id');
    }
}
