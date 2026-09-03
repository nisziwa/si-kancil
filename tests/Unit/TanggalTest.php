<?php

namespace Tests\Unit;

use App\Support\Tanggal;
use Carbon\Carbon;
use Tests\TestCase;

class TanggalTest extends TestCase
{
    public function test_format_menghasilkan_format_indonesia_dengan_nama_bulan(): void
    {
        $this->assertSame('02 September 2026', Tanggal::format('2026-09-02'));
        $this->assertSame('25 Juli 2026', Tanggal::format('2026-07-25'));
    }

    public function test_format_menerima_carbon_dan_menambah_nol_di_depan(): void
    {
        $this->assertSame('04 September 2026', Tanggal::format(Carbon::parse('2026-09-04')));
    }

    public function test_format_mengembalikan_fallback_saat_nilai_kosong_atau_tidak_valid(): void
    {
        $this->assertSame('-', Tanggal::format(null));
        $this->assertSame('-', Tanggal::format(''));
        $this->assertSame('-', Tanggal::format('bukan-tanggal'));
        $this->assertSame('', Tanggal::format(null, ''));
    }

    public function test_format_date_time_menyertakan_jam(): void
    {
        $this->assertSame('02 September 2026 14:05', Tanggal::formatDateTime('2026-09-02 14:05:00'));
        $this->assertSame('-', Tanggal::formatDateTime(null));
    }
}
