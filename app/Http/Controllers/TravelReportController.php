<?php

namespace App\Http\Controllers;

use App\Models\MasterRincianPok;
use App\Models\SpjChecklist;
use App\Models\SuratTugasPelaksana;
use App\Models\TravelReport;
use App\Services\TravelReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TravelReportController extends Controller
{
    public function __construct(private TravelReportService $service)
    {
    }

    /**
     * Autocomplete pencarian POK berdasarkan rincian.
     */
    public function searchPok(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $query = MasterRincianPok::query()
            ->with(['program', 'kegiatan', 'output', 'subOutput', 'komponen', 'akun']);

        if ($q !== '') {
            $query->where('rincian', 'like', '%'.$q.'%');
        }

        $result = $query->orderBy('rincian')->limit(10)->get()->map(fn ($pok) => [
            'id' => $pok->id,
            'rincian' => $pok->rincian,
            'program' => $pok->program ? $pok->program->kode_program.' - '.$pok->program->nama_program : '-',
            'kegiatan' => $pok->kegiatan ? $pok->kegiatan->kode_kegiatan.' - '.$pok->kegiatan->nama_kegiatan : '-',
            'output' => $pok->output ? $pok->output->kode_output.' - '.$pok->output->nama_output : '-',
            'sub_output' => $pok->subOutput ? $pok->subOutput->kode_sub_output.' - '.$pok->subOutput->nama_sub_output : '-',
            'komponen' => $pok->komponen ? $pok->komponen->kode_komponen.' - '.$pok->komponen->nama_komponen : '-',
            'akun' => $pok->akun ? $pok->akun->kode_akun.' - '.$pok->akun->nama_akun : '-',
        ]);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Detail POK terpilih (dipanggil saat memilih autocomplete / saat generate).
     */
    public function pokDetail($id)
    {
        $pok = MasterRincianPok::with(['program', 'kegiatan', 'output', 'subOutput', 'komponen', 'akun'])->find($id);
        if (! $pok) {
            return response()->json(['success' => false, 'message' => 'POK tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pok->id,
                'rincian' => $pok->rincian,
                'program' => $pok->program ? $pok->program->kode_program.' - '.$pok->program->nama_program : '-',
                'kegiatan' => $pok->kegiatan ? $pok->kegiatan->kode_kegiatan.' - '.$pok->kegiatan->nama_kegiatan : '-',
                'output' => $pok->output ? $pok->output->kode_output.' - '.$pok->output->nama_output : '-',
                'sub_output' => $pok->subOutput ? $pok->subOutput->kode_sub_output.' - '.$pok->subOutput->nama_sub_output : '-',
                'komponen' => $pok->komponen ? $pok->komponen->kode_komponen.' - '.$pok->komponen->nama_komponen : '-',
                'akun' => $pok->akun ? $pok->akun->kode_akun.' - '.$pok->akun->nama_akun : '-',
            ],
        ]);
    }

    /**
     * Generate Laporan Perjalanan untuk satu pelaksana Surat Tugas pada sebuah checklist.
     */
    public function generate(Request $request, $checklistId, $pelaksanaId)
    {
        $checklist = SpjChecklist::findOrFail($checklistId);
        $pelaksana = $this->pelaksanaOf($checklist, (int) $pelaksanaId);
        abort_if(! $pelaksana, 404, 'Pelaksana tidak ditemukan pada Surat Tugas.');

        $format = $request->input('format', 'docx');
        if (! in_array($format, TravelReportService::FORMATS, true)) {
            $format = 'docx';
        }

        $data = $this->validateGenerate($request, $checklist, $pelaksana);

        $report = TravelReport::updateOrCreate(
            [
                'fpa_id' => $checklist->request_id,
                'surat_tugas_pelaksana_id' => $pelaksana->id,
            ],
            [
                'jenis_laporan' => $data['jenis_laporan'],
                'judul_laporan' => $data['judul_laporan'],
                'tanggal_laporan' => $data['tanggal_laporan'],
                'pok_rincian_id' => $data['pok_rincian_id'],
            ]
        );

        $relative = $this->storeGeneratedFile($report, $pelaksana, $format);

        $field = $format === 'pdf' ? 'file_pdf' : 'file_docx';
        $report->{$field} = $relative;
        $report->save();

        // Upload/Generate selalu menandai pelaksana telah mengumpulkan.
        $this->markCollected($checklist, $pelaksana);

        $filename = $this->filename($data['judul_laporan'], $pelaksana, $format);

        return response()->download(Storage::disk('public')->path($relative), $filename);
    }

    /**
     * Upload file laporan manual per pelaksana -> otomatis status Sudah Mengumpulkan.
     */
    public function upload(Request $request, $checklistId, $pelaksanaId)
    {
        $request->validate([
            'file_laporan' => 'required|file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ]);

        $checklist = SpjChecklist::findOrFail($checklistId);
        $pelaksana = $this->pelaksanaOf($checklist, (int) $pelaksanaId);
        abort_if(! $pelaksana, 404, 'Pelaksana tidak ditemukan pada Surat Tugas.');

        $report = TravelReport::firstOrCreate(
            ['fpa_id' => $checklist->request_id, 'surat_tugas_pelaksana_id' => $pelaksana->id],
            ['jenis_laporan' => TravelReport::JENIS_PENDATAAN, 'judul_laporan' => 'Laporan Perjalanan', 'tanggal_laporan' => null]
        );

        $path = $request->file('file_laporan')->store('spj-files/laporan-perjalanan', 'public');
        $report->file_docx = $path;
        $report->save();

        $this->markCollected($checklist, $pelaksana);

        return response()->json([
            'success' => true,
            'message' => 'Laporan '.$pelaksana->nama_pelaksana.' berhasil diunggah dan status menjadi Sudah Mengumpulkan.',
        ]);
    }

    /**
     * Ubah status pengumpulan satu pelaksana. Wajib konfirmasi bila mengubah
     * kembali ke "Belum Mengumpulkan" padahal sudah ada file laporan.
     */
    public function updateStatus(Request $request, $checklistId, $pelaksanaId)
    {
        $status = $request->input('status');
        if (! in_array($status, ['Sudah Mengumpulkan', 'Belum Mengumpulkan'], true)) {
            throw ValidationException::withMessages(['status' => 'Status pelaksana tidak valid.']);
        }

        $checklist = SpjChecklist::findOrFail($checklistId);
        $pelaksana = $this->pelaksanaOf($checklist, (int) $pelaksanaId);
        abort_if(! $pelaksana, 404, 'Pelaksana tidak ditemukan.');

        $report = TravelReport::where('fpa_id', $checklist->request_id)
            ->where('surat_tugas_pelaksana_id', $pelaksana->id)
            ->first();

        // Konfirmasi bila ingin kembali ke Belum tetapi file sudah ada.
        if ($status === 'Belum Mengumpulkan' && $report && $this->hasFile($report)) {
            if (! $request->boolean('confirm')) {
                return response()->json([
                    'success' => false,
                    'require_confirm' => true,
                    'message' => 'Pelaksana '.$pelaksana->nama_pelaksana.' sudah memiliki laporan yang diunggah. Yakin ingin mengubah status menjadi Belum Mengumpulkan?',
                ], 422);
            }
        }

        \App\Models\TravelReportPelaksana::updateOrCreate(
            [
                'checklist_id' => $checklist->id,
                'surat_tugas_pelaksana_id' => $pelaksana->id,
            ],
            ['status' => $status]
        );

        $this->syncChecklistStatus($checklist);

        return response()->json([
            'success' => true,
            'message' => 'Status pelaksana diperbarui menjadi '.$status.'.',
        ]);
    }

    /**
     * Ubah status massal beberapa pelaksana pada sebuah checklist.
     */
    public function bulkPelaksanaStatus(Request $request, $checklistId)
    {
        $status = $request->input('status');
        if (! in_array($status, ['Sudah Mengumpulkan', 'Belum Mengumpulkan'], true)) {
            throw ValidationException::withMessages(['status' => 'Pilih status target yang valid.']);
        }

        $ids = collect($request->input('pelaksana_ids', []))->map(fn ($id) => (int) $id)->all();
        $checklist = SpjChecklist::findOrFail($checklistId);
        $st = $this->stDetailFor($checklist);
        abort_if(! $st, 422, 'Pelaksana Surat Tugas belum tersedia.');

        $allIds = $st->pelaksanas->pluck('id')->map(fn ($id) => (int) $id)->all();
        $invalid = array_diff($ids, $allIds);
        abort_if($invalid !== [], 422, 'Pelaksana tidak sah.');

        foreach ($ids as $pelaksanaId) {
            \App\Models\TravelReportPelaksana::updateOrCreate(
                [
                    'checklist_id' => $checklist->id,
                    'surat_tugas_pelaksana_id' => $pelaksanaId,
                ],
                ['status' => $status]
            );
        }

        $this->syncChecklistStatus($checklist);

        return back()->with('success', 'Status '.count($ids).' pelaksana diperbarui menjadi '.$status.'.');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function validateGenerate(Request $request, SpjChecklist $checklist, SuratTugasPelaksana $pelaksana): array
    {
        $validated = $request->validate([
            'jenis_laporan' => 'required|in:'.implode(',', TravelReport::JENIS_LIST),
            'judul_laporan' => 'required|string',
            'tanggal_laporan' => 'required|date',
            'pok_rincian_id' => 'required|exists:master_rincian_pok,id',
        ], [
            'jenis_laporan.required' => 'Lengkapi data laporan perjalanan terlebih dahulu.',
            'jenis_laporan.in' => 'Jenis laporan tidak valid.',
            'judul_laporan.required' => 'Judul laporan wajib diisi.',
            'tanggal_laporan.required' => 'Tanggal laporan wajib diisi.',
            'pok_rincian_id.required' => 'Pilih POK (pembiayaan) terlebih dahulu.',
            'pok_rincian_id.exists' => 'POK yang dipilih tidak ditemukan.',
        ]);

        return $validated;
    }

    protected function pelaksanaOf(SpjChecklist $checklist, int $pelaksanaId): ?SuratTugasPelaksana
    {
        $st = $this->stDetailFor($checklist);
        return $st ? $st->pelaksanas->first(fn ($p) => (int) $p->id === $pelaksanaId) : null;
    }

    protected function stDetailFor(SpjChecklist $checklist)
    {
        $stChecklist = SpjChecklist::where('request_id', $checklist->request_id)
            ->where('nama_dokumen', 'like', '%Surat Tugas%')
            ->with('suratTugasDetail.pelaksanas.superkendis')
            ->first();

        return $stChecklist ? $stChecklist->suratTugasDetail : null;
    }

    protected function storeGeneratedFile(TravelReport $report, SuratTugasPelaksana $pelaksana, string $format): string
    {
        $data = $this->service->buildData($report, $pelaksana);
        $relative = 'spj-files/laporan-perjalanan/'.md5($report->fpa_id.'-'.$pelaksana->id).'.'.$format;
        $localPath = Storage::disk('public')->path($relative);

        $dir = dirname($localPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->service->write($data, $format, $localPath);

        return $relative;
    }

    protected function markCollected(SpjChecklist $checklist, SuratTugasPelaksana $pelaksana): void
    {
        \App\Models\TravelReportPelaksana::updateOrCreate(
            [
                'checklist_id' => $checklist->id,
                'surat_tugas_pelaksana_id' => $pelaksana->id,
            ],
            ['status' => 'Sudah Mengumpulkan']
        );

        $this->syncChecklistStatus($checklist);
    }

    protected function syncChecklistStatus(SpjChecklist $checklist): void
    {
        $st = $this->stDetailFor($checklist);
        if (! $st || $st->pelaksanas->isEmpty()) {
            return;
        }

        $pelaksanaIds = $st->pelaksanas->pluck('id');
        $sudah = \App\Models\TravelReportPelaksana::where('checklist_id', $checklist->id)
            ->whereIn('surat_tugas_pelaksana_id', $pelaksanaIds)
            ->where('status', 'Sudah Mengumpulkan')
            ->count();

        // Sinkronisasi: checklist Lengkap hanya bila seluruh pelaksana sudah mengumpulkan.
        if ($sudah < $pelaksanaIds->count() && $checklist->status === 'Lengkap') {
            $checklist->status = 'Belum Lengkap';
            $checklist->save();
        }
    }

    protected function hasFile(TravelReport $report): bool
    {
        return $report->file_docx !== null || $report->file_pdf !== null;
    }

    protected function filename(string $judul, SuratTugasPelaksana $pelaksana, string $format): string
    {
        $base = 'Laporan_Perjalanan_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', $judul)
            .'_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $pelaksana->nama_pelaksana);

        return $base.'.'.$format;
    }
}
