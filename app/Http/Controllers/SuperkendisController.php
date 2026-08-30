<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use App\Models\SkRatePerjalanan;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpWord\SimpleType\Jc;

class SuperkendisController extends Controller
{
    const FORMATS = ['docx', 'pdf'];

    /**
     * Ringkasan Superkendis untuk halaman detail FPA.
     */
    public function index($requestId)
    {
        $requestModel = FpaRequest::with([
            'checklists.suratTugasDetail.pelaksanas',
            'expenseType',
        ])->findOrFail($requestId);

        $stChecklist = $requestModel->checklists
            ->first(fn ($c) => str_contains($c->nama_dokumen, 'Surat Tugas'));

        $superkendisDone = $stChecklist && $stChecklist->status === 'Lengkap';

        $kecamatans = SkRatePerjalanan::orderBy('kecamatan')->get();

        return view('requests.superkendis', compact('requestModel', 'stChecklist', 'superkendisDone', 'kecamatans'));
    }

    /**
     * Generate Superkendis untuk satu pelaksana (dari Surat Tugas) pada detail FPA.
     */
    public function generate(Request $request, $requestId, $pelaksanaId)
    {
        $format = $request->input('format', 'docx');
        if (! in_array($format, self::FORMATS, true)) {
            $format = 'docx';
        }

        $requestModel = FpaRequest::with('checklists.suratTugasDetail.pelaksanas')->findOrFail($requestId);

        $pelaksana = $requestModel->checklists
            ->flatMap(fn ($c) => $c->suratTugasDetail ? $c->suratTugasDetail->pelaksanas : collect())
            ->first(fn ($p) => $p->id === (int) $pelaksanaId);

        abort_if(! $pelaksana, 404, 'Pelaksana tidak ditemukan.');

        $data = $this->buildData($request, $requestModel, $pelaksana->nama_pelaksana, $pelaksana->nomor_surat);

        $filename = 'Superkendis_'.$this->slug($pelaksana->nama_pelaksana).'.'.$format;

        return $this->buildDocument($data, $format, $filename);
    }

    /**
     * Bulk download semua pelaksana Superkendis (Pisah file -> ZIP).
     */
    public function bulkSeparate(Request $request, $requestId)
    {
        $requestModel = FpaRequest::with('checklists.suratTugasDetail.pelaksanas')->findOrFail($requestId);

        $pelaksanas = $requestModel->checklists
            ->flatMap(fn ($c) => $c->suratTugasDetail ? $c->suratTugasDetail->pelaksanas : collect());

        abort_if($pelaksanas->isEmpty(), 422, 'Tidak ada pelaksana Superkendis.');

        $this->ensureValidForExport($request, $requestModel, $pelaksanas);

        $format = $request->input('format', 'docx');
        if (! in_array($format, self::FORMATS, true)) {
            $format = 'docx';
        }

        $tmpDir = storage_path('app/superkendis-tmp/'.uniqid());
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        foreach ($pelaksanas as $pelaksana) {
            $data = $this->buildData($request, $requestModel, $pelaksana->nama_pelaksana, $pelaksana->nomor_surat);
            $this->writeDocument($data, $format, $tmpDir.'/'.'Superkendis_'.$this->slug($pelaksana->nama_pelaksana).'.'.$format);
        }

        $zip = new ZipArchive;
        $zipFile = storage_path('app/superkendis-tmp/superkendis-'.uniqid().'.zip');
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            abort(500, 'Gagal membuat arsip ZIP.');
        }

        foreach (glob($tmpDir.'/*.'.$format) as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        // Bersihkan folder temp
        array_map('unlink', glob($tmpDir.'/*.'.$format) ?: []);
        rmdir($tmpDir);

        return response()->download($zipFile, 'Superkendis_Pisah.zip')->deleteFileAfterSend(true);
    }

    /**
     * Bulk download Superkendis gabungan menjadi satu file.
     */
    public function bulkMerged(Request $request, $requestId)
    {
        $requestModel = FpaRequest::with('checklists.suratTugasDetail.pelaksanas')->findOrFail($requestId);

        $pelaksanas = $requestModel->checklists
            ->flatMap(fn ($c) => $c->suratTugasDetail ? $c->suratTugasDetail->pelaksanas : collect());

        abort_if($pelaksanas->isEmpty(), 422, 'Tidak ada pelaksana Superkendis.');

        $this->ensureValidForExport($request, $requestModel, $pelaksanas);

        $format = $request->input('format', 'docx');
        if (! in_array($format, self::FORMATS, true)) {
            $format = 'docx';
        }

        $phpWord = new PhpWord;
        $phpWord->getSettings()->setUpdateFields(true);
        $section = $phpWord->addSection();

        $first = true;
        foreach ($pelaksanas as $pelaksana) {
            if (! $first) {
                $section = $phpWord->addSection();
            }
            $first = false;
            $data = $this->buildData($request, $requestModel, $pelaksana->nama_pelaksana, $pelaksana->nomor_surat);
            $this->populateDocument($section, $data);
        }

        $filename = 'Superkendis_Gabungan.'.$format;

        return $this->downloadPhpWord($phpWord, $filename, $format);
    }

    /**
     * Data Superkendis untuk satu pelaksana.
     */
    protected function buildData(Request $request, FpaRequest $requestModel, string $nama, ?string $nomorSurat): array
    {
        $kecamatan = trim($request->input('kecamatan') ?? '');
        $rate = $kecamatan !== ''
            ? SkRatePerjalanan::where('kecamatan', $kecamatan)->first()
            : null;

        return [
            'nama_pelaksana' => $nama,
            'nip' => $this->normalizeNip($request->input('nip') ?? ''),
            'kecamatan' => $kecamatan,
            'tanggal_perjalanan' => $request->input('tanggal_perjalanan') ?? '',
            'besaran_biaya' => $rate ? number_format((float) $rate->besaran_biaya_transport, 0, ',', '.') : '-',
            'nomor_surat' => $nomorSurat ?: '-',
            'fpa' => $requestModel->nomor_fpa ?: 'Belum ada nomor FPA',
            'deskripsi' => $requestModel->deskripsi_permintaan,
        ];
    }

    /**
     * NIP tidak wajib; jika kosong atau format tidak sesuai, isi "-".
     */
    protected function normalizeNip(string $nip): string
    {
        $nip = trim($nip);
        if ($nip === '') {
            return '-';
        }
        // Format NIP standar: 18 digit (YYYYMMDD YYYYMMDD NNN), sering ditulis dgn/tanpa spasi
        $digits = preg_replace('/\D/', '', $nip);
        if (strlen($digits) < 15 || strlen($digits) > 18) {
            return '-';
        }

        return $nip;
    }

    protected function ensureValidForExport(Request $request, FpaRequest $requestModel, $pelaksanas)
    {
        $tujuan = trim((string) $request->input('kecamatan'));
        $tanggal = (string) $request->input('tanggal_perjalanan');

        if ($tujuan === '' || $tanggal === '') {
            abort(422, 'Tempat tujuan dan tanggal perjalanan wajib diisi untuk export Superkendis.');
        }
    }

    protected function buildDocument(array $data, string $format, string $filename)
    {
        $phpWord = new PhpWord;
        $phpWord->getSettings()->setUpdateFields(true);
        $section = $phpWord->addSection();
        $this->populateDocument($section, $data);

        return $this->downloadPhpWord($phpWord, $filename, $format);
    }

    protected function populateDocument($section, array $data)
    {
        $section->addText(
            'SURAT KETERANGAN BUKAN KENDARAAN DINAS',
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER]
        );
        $section->addText('Nomor : '.($data['nomor_surat'] ?: '-'), null, ['alignment' => Jc::CENTER]);
        $section->addTextBreak();

        $section->addText('Yang bertanda tangan di bawah ini menerangkan bahwa:');
        $section->addTextBreak();

        $this->addLabeledLine($section, 'Nama', $data['nama_pelaksana']);
        $this->addLabeledLine($section, 'NIP', $data['nip']);
        $this->addLabeledLine($section, 'Kecamatan Tujuan', $data['kecamatan']);
        $this->addLabeledLine($section, 'Tanggal Perjalanan', $data['tanggal_perjalanan'] ? date('d-m-Y', strtotime($data['tanggal_perjalanan'])) : '');
        $this->addLabeledLine($section, 'Besaran Biaya Transport', 'Rp '.$data['besaran_biaya']);
        $this->addLabeledLine($section, 'Nomor FPA', $data['fpa']);
        $this->addLabeledLine($section, 'Uraian Kegiatan', $data['deskripsi']);
    }

    protected function addLabeledLine($section, string $label, string $value)
    {
        $section->addText($label.'  :  '.$value.'   ');
        $section->addTextBreak(0);
    }

    protected function downloadPhpWord(PhpWord $phpWord, string $filename, string $format)
    {
        if ($format === 'pdf') {
            $this->configurePdfRenderer();
            $writer = IOFactory::createWriter($phpWord, 'PDF');
            $temp = storage_path('app/superkendis-'.uniqid().'.pdf');
            $writer->save($temp);

            return response()->download($temp, $filename)->deleteFileAfterSend(true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $temp = storage_path('app/superkendis-'.uniqid().'.docx');
        $writer->save($temp);

        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }

    protected function writeDocument(array $data, string $format, string $path)
    {
        $phpWord = new PhpWord;
        $phpWord->getSettings()->setUpdateFields(true);
        $section = $phpWord->addSection();
        $this->populateDocument($section, $data);

        if ($format === 'pdf') {
            $this->configurePdfRenderer();
        }
        $writer = $format === 'pdf'
            ? IOFactory::createWriter($phpWord, 'PDF')
            : IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
    }

    protected function configurePdfRenderer(): void
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf/src/Dompdf.php'));
    }

    protected function slug(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($name));
    }
}
