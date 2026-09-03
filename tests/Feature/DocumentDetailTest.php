<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use App\Models\Request as FpaRequest;
use App\Models\SpjChecklist;
use App\Models\SuratTugasDetail;
use App\Models\SuratTugasPelaksana;
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
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'pelaksana_nama' => ['Budi Santoso', 'Siti Rahma'],
            'isi_tugas' => 'Melakukan survei lapangan',
            'file_dokumen' => $file,
        ]);

        $response->assertRedirect(route('requests.show', $this->fpaRequest->id));

        $this->assertDatabaseHas('spj_checklists', [
            'id' => $this->stChecklist->id,
            'status' => 'Lengkap',
            'catatan' => 'ST sudah ditandatangani',
        ]);

        $stDetail = SuratTugasDetail::where('checklist_id', $this->stChecklist->id)->first();
        $this->assertNotNull($stDetail);
        $this->assertDatabaseHas('surat_tugas_details', [
            'checklist_id' => $this->stChecklist->id,
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
        ]);

        // Selain itu, nomor sub otomatis untuk setiap pelaksana
        $pelaksanas = SuratTugasPelaksana::where('surat_tugas_detail_id', $stDetail->id)
            ->orderBy('urutan')
            ->get();
        $this->assertCount(2, $pelaksanas);
        $this->assertEquals('B-1027.1/75040/KP.650/2026', $pelaksanas[0]->nomor_surat);
        $this->assertEquals('Budi Santoso', $pelaksanas[0]->nama_pelaksana);
        $this->assertEquals('B-1027.2/75040/KP.650/2026', $pelaksanas[1]->nomor_surat);

        $this->stChecklist->refresh();
        $this->assertNotNull($this->stChecklist->file_path);
        Storage::disk('public')->assertExists($this->stChecklist->file_path);
    }

    public function test_travel_detail_is_derived_from_surat_tugas_and_no_empty_detail_inserted(): void
    {
        // Sumber data pelaksana berasal dari checklist "Surat Tugas" pada request yang sama.
        $stDetail = SuratTugasDetail::create([
            'checklist_id' => $this->stChecklist->id,
            'nomor_surat_tugas' => 'B-1027/75040/KP.650/2026',
            'tanggal_surat_tugas' => '2026-08-25',
            'isi_tugas' => 'Koordinasi data',
        ]);
        SuratTugasPelaksana::create([
            'surat_tugas_detail_id' => $stDetail->id,
            'nama_pelaksana' => 'Ahmad',
            'nomor_surat' => 'B-1027.1/75040/KP.650/2026',
            'urutan' => 1,
        ]);

        // SPD/SPPD: detail dibuat otomatis dari Surat Tugas, bukan input manual.
        $response1 = $this->actingAs($this->user)->put(route('checklists.update', $this->spdChecklist->id), [
            'status' => 'Belum Lengkap',
        ]);
        $response1->assertRedirect();
        $this->assertDatabaseHas('travel_details', [
            'checklist_id' => $this->spdChecklist->id,
            'nomor_spd' => 'B-1027.1/75040/KP.650/2026',
            'nama_pelaksana' => 'Ahmad',
        ]);

        // Mengubah status checklist Pengeluaran Riil TIDAK membuat baris detail kosong.
        $response2 = $this->actingAs($this->user)->put(route('checklists.update', $this->realChecklist->id), [
            'status' => 'Lengkap',
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseMissing('real_expense_details', ['checklist_id' => $this->realChecklist->id]);
    }
}
