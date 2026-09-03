<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterAkun extends Model
{
    protected $table = 'master_akun';

    protected $fillable = ['kode_akun', 'nama_akun'];
}
