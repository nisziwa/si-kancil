<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    protected $fillable = [
        'nomor_fpa', 'deskripsi_permintaan', 'jenis_pengeluaran_id',
        'periode', 'tanggal_mulai', 'tanggal_selesai', 'lokasi',
        'deadline_spj', 'status_spj', 'user_id', 'tanggal_kirim_ppk', 'tanggal_selesai_spj',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'deadline_spj' => 'date',
            'tanggal_kirim_ppk' => 'date',
            'tanggal_selesai_spj' => 'date',
        ];
    }

    public const STATUS_LIST = ['Persiapan', 'Dikirim ke PPK', 'Perbaikan', 'Selesai'];

    public const PERIOD_LIST = ['Bulanan', 'Triwulanan', 'Subround', 'Semester', 'Tahunan'];

    public const STATUS_COLORS = [
        'Persiapan' => 'gray',
        'Dikirim ke PPK' => 'indigo',
        'Perbaikan' => 'red',
        'Selesai' => 'green',
    ];

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class, 'jenis_pengeluaran_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(SpjChecklist::class)->orderBy('urutan');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class)->orderByDesc('created_at');
    }

    public function getChecklistProgressAttribute(): array
    {
        $total = $this->checklists()->count();
        $lengkap = $this->checklists()->where('status', 'Lengkap')->count();

        return [
            'total' => $total,
            'lengkap' => $lengkap,
            'persen' => $total > 0 ? round($lengkap / $total * 100) : 0,
        ];
    }

    /**
     * Progress SPJ digunakan untuk menunjukkan PRIORITAS SPJ (bukan checklist dokumen).
     * Berdasarkan deadline, sisa hari, dan keterlambatan.
     */
    public function getPriorityInfoAttribute(): array
    {
        $deadline = $this->deadline_spj;
        if (! $deadline) {
            return [
                'level' => 'normal',
                'label' => 'Belum ada deadline',
                'sisa_hari' => null,
                'terlambat' => false,
                'warning' => false,
            ];
        }

        $today = Carbon::today();
        $diff = $today->startOfDay()->diffInDays($deadline->startOfDay(), false);

        if ($deadline->lt($today)) {
            $keterlambatan = abs($diff);

            return [
                'level' => 'danger',
                'label' => "Keterlambatan {$keterlambatan} hari",
                'sisa_hari' => -$keterlambatan,
                'terlambat' => true,
                'warning' => true,
            ];
        }

        if ($diff <= 3) {
            return [
                'level' => 'warning',
                'label' => $diff === 0 ? 'Deadline hari ini' : "Sisa {$diff} hari — Prioritas tinggi",
                'sisa_hari' => $diff,
                'terlambat' => false,
                'warning' => true,
            ];
        }

        return [
            'level' => 'normal',
            'label' => "Sisa {$diff} hari — Prioritas normal",
            'sisa_hari' => $diff,
            'terlambat' => false,
            'warning' => false,
        ];
    }

    public function getHasNomorFpaAttribute(): bool
    {
        return filled($this->nomor_fpa);
    }

    /**
     * Cek apakah semua checklist wajib sudah berstatus Lengkap.
     */
    public function getMandatoryChecklistCompleteAttribute(): bool
    {
        if ($this->checklists()->where('is_required', true)->count() === 0) {
            return false;
        }

        return $this->checklists()
            ->where('is_required', true)
            ->where('status', '!=', 'Lengkap')
            ->count() === 0;
    }
}
