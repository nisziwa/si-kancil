<?php
namespace App\Models;
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
    public const STATUS_LIST = ['Persiapan', 'Pelaksanaan', 'Pengumpulan SPJ', 'Dikirim ke PPK', 'Perbaikan', 'Selesai'];
    public function expenseType(): BelongsTo { return $this->belongsTo(ExpenseType::class, 'jenis_pengeluaran_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function checklists(): HasMany { return $this->hasMany(SpjChecklist::class)->orderBy('urutan'); }
    public function statusHistories(): HasMany { return $this->hasMany(RequestStatusHistory::class)->orderByDesc('created_at'); }
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
}