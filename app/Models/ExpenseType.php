<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ExpenseType extends Model
{
    protected $fillable = ['nama', 'kode', 'keterangan', 'is_active'];
    public function requests(): HasMany { return $this->hasMany(Request::class, 'jenis_pengeluaran_id'); }
    public function documentTemplates(): HasMany { return $this->hasMany(DocumentTemplate::class); }
}