<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FpaRequest $fpaRequest;
    protected SpjChecklist $stChecklist;
    protected SpjChecklist $spdChecklist;
    protected SpjChecklist $realChecklist;
    protected SpjChecklist $reportChecklist;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $expenseType = ExpenseType::create([
            'nama' => 'Perjalanan Dinas',
            'kode' => 'PERJADIN',
            'is_active' => true,
        ]);

        $this->fpaRequest = FpaRequest::create([
            'nomor_fpa' => 'FPA-DOC-001',
            'deskripsi_permintaan' => 'Dinas Luar Kota',
            'jenis_pengeluaran_id' => $expenseType->id,
            'periode' => 'Agustus 2026',
            'user_id' => $this->user->id,
            'status_spj' => 'Persiapan',
        ]);

        $this->stChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'Surat Tugas',
            'status' => 'Belum Ada',
        ]);

        $this->spdChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'SPD/SPPD',
            'status' => 'Belum Ada',
        ]);

        $this->realChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'Pengeluaran Riil + Surat Non Kendaraan Dinas',
            'status' => 'Belum Ada',
        ]);

        $this->reportChecklist = SpjChecklist::create([
            'request_id' => $this->fpaRequest->id,
            'nama_dokumen' => 'Laporan Perjalanan',
            'status' => 'Belum Ada',
        ]);
    }

    public function test_can_update_surat_tugas_detail_and_upload_file(): void
    {
        $file = UploadedFile::fake()->create('surat_tugas.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->put(route('checklists.update', $this->stChecklist->id), [
            'status' => 'Lengkap',
            'catatan' => 'ST sudah ditandatangani',
            'nomor_surat_tugas' => 'ST/001/VIII/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'pelaksana' => 'Budi, Siti',
            'isi_tugas' => 'Melakukan survei lapangan',
            'file_dokumen' => $file,
        ]);

        $response->assertRedirect(route('requests.show', $this->fpaRequest->id));

        $this->assertDatabaseHas('spj_checklists', [
            'id' => $this->stChecklist->id,
            'status' => 'Lengkap',
            'catatan' => 'ST sudah ditandatangani',
        ]);

        $this->assertDatabaseHas('surat_tugas_details', [
            'checklist_id' => $this->stChecklist->id,
            'nomor_surat_tugas' => 'ST/001/VIII/2026',
            'pelaksana' => 'Budi, Siti',
        ]);

        $this->stChecklist->refresh();
        $this->assertNotNull($this->stChecklist->file_path);
        Storage::disk('public')->assertExists($this->stChecklist->file_path);
    }

    public function test_can_update_travel_detail_and_real_expense_detail(): void
    {
        // SPD / SPPD
        $response1 = $this->actingAs($this->user)->put(route('checklists.update', $this->spdChecklist->id), [
            'status' => 'Belum Lengkap',
            'nomor_spd' => 'SPD/999/2026',
            'travel_nama_pelaksana' => 'Ahmad',
            'maksud_perjalanan' => 'Koordinasi data',
            'tempat_berangkat' => 'Kantor',
            'tempat_tujuan' => 'Dinas Prov',
            'tanggal_berangkat' => '2026-08-20',
            'tanggal_kembali' => '2026-08-22',
            'transportasi' => 'Darat',
        ]);
        $response1->assertRedirect();
        $this->assertDatabaseHas('travel_details', [
            'checklist_id' => $this->spdChecklist->id,
            'nomor_spd' => 'SPD/999/2026',
        ]);

        // Real Expense
        $response2 = $this->actingAs($this->user)->put(route('checklists.update', $this->realChecklist->id), [
            'status' => 'Lengkap',
            'real_nomor_surat_tugas' => 'ST/001/VIII/2026',
            'real_tanggal_surat_tugas' => '2026-08-25',
            'real_nama_pelaksana' => 'Ahmad',
            'real_jabatan' => 'Staff',
            'real_tanggal_kegiatan' => '2026-08-26',
            'uraian_pengeluaran' => 'Biaya BBM dan Tol',
            'jumlah_pengeluaran' => 350000,
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('real_expense_details', [
            'checklist_id' => $this->realChecklist->id,
            'jumlah_pengeluaran' => 350000.00,
        ]);
    }
}

