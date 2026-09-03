<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelReport extends Model
{
    public const JENIS_PENDATAAN = 'LAPORAN_PENDATAAN';

    public const JENIS_PENGAWASAN = 'LAPORAN_PENGAWASAN_DAN_PEMERIKSAAN';

    public const JENIS_LIST = [
        self::JENIS_PENDATAAN,
        self::JENIS_PENGAWASAN,
    ];

    public const JENIS_LABELS = [
        self::JENIS_PENDATAAN => 'LAPORAN PENDATAAN',
        self::JENIS_PENGAWASAN => 'LAPORAN PENGAWASAN DAN PEMERIKSAAN',
    ];

    protected $fillable = [
        'fpa_id', 'surat_tugas_pelaksana_id', 'jenis_laporan',
        'judul_laporan', 'tanggal_laporan', 'pok_rincian_id',
        'file_docx', 'file_pdf',
    ];

    protected function casts(): array
    {
        return ['tanggal_laporan' => 'date'];
    }

    public function fpa(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'fpa_id');
    }

    public function suratTugasPelaksana(): BelongsTo
    {
        return $this->belongsTo(SuratTugasPelaksana::class, 'surat_tugas_pelaksana_id');
    }

    public function pokRincian(): BelongsTo
    {
        return $this->belongsTo(MasterRincianPok::class, 'pok_rincian_id');
    }
}
