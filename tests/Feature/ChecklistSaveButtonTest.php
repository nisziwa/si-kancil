<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistSaveButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_edit_renders_single_save_button_inside_main_form(): void
    {
        // Regression test: form generate (#generate-form) sebelumnya bersarang di dalam
        // form utama, menyebabkan browser menutup form utama lebih awal sehingga tombol
        // "Simpan Perubahan" (footer) berada di luar form dan tidak melakukan submit.
        $user = User::factory()->create();
        $exp = ExpenseType::create(['nama' => 'Perjalanan Dinas', 'kode' => 'PERJADIN', 'is_active' => true]);
        $fpa = FpaRequest::create([
            'nomor_fpa' => 'FPA-001', 'deskripsi_permintaan' => 'x',
            'jenis_pengeluaran_id' => $exp->id, 'periode' => 'Subround',
            'user_id' => $user->id, 'status_spj' => 'Persiapan',
        ]);
        $st = SpjChecklist::create(['request_id' => $fpa->id, 'nama_dokumen' => 'Surat Tugas', 'status' => 'Lengkap', 'is_required' => true]);
        $det = SuratTugasDetail::create(['checklist_id' => $st->id, 'nomor_surat_tugas' => 'B-1', 'tanggal_surat_tugas' => '2026-09-01', 'isi_tugas' => 'x']);
        SuratTugasPelaksana::create(['surat_tugas_detail_id' => $det->id, 'nama_pelaksana' => 'Budi', 'nomor_surat' => 'B-1.1', 'urutan' => 1]);
        $lapor = SpjChecklist::create(['request_id' => $fpa->id, 'nama_dokumen' => 'Laporan Perjalanan', 'status' => 'Belum Lengkap', 'is_required' => true]);

        $resp = $this->actingAs($user)->get("/checklists/{$lapor->id}/edit");
        $resp->assertOk();
        $html = $resp->getContent();

        // Hanya satu tombol "Simpan Perubahan".
        $this->assertSame(1, substr_count($html, 'Simpan Perubahan'));

        // Tombol submit berada di dalam form utama checklists (bukan di luar / bukan generate form).
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $buttons = $xpath->query("//button[contains(.,'Simpan Perubahan')]");
        $this->assertSame(1, $buttons->length);

        $inMainForm = false;
        foreach ($buttons as $btn) {
            $anc = $btn;
            while ($anc = $anc->parentNode) {
                if ($anc->nodeType === XML_ELEMENT_NODE
                    && $anc->nodeName === 'form'
                    && $anc->getAttribute('method') === 'POST'
                    && strpos((string) $anc->getAttribute('action'), 'checklists/') !== false) {
                    $inMainForm = true;
                }
            }
        }

        $this->assertTrue($inMainForm, 'Simpan Perubahan button must be inside the main checklists form.');
    }
}
