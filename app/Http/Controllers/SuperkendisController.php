<?php

namespace App\Http\Controllers;

use App\Models\Request as FpaRequest;
use App\Models\SkRatePerjalanan;
use App\Support\Terbilang;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpWord\TemplateProcessor;

class SuperkendisController extends Controller
{
    const FORMATS = ['docx', 'pdf'];

    const TEMPLATE_PATH = '[template] Superkendis.docx';

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
     * Mendukung payload berbasis array (checkbox) maupun flat untuk kompatibilitas.
     */
    public function generate(Request $request, $requestId, $pelaksanaId)
    {
        $format = $request->input('format', 'docx');
        if (! in_array($format, self::FORMATS, true)) {
            $format = 'docx';
        }

        $requestModel = FpaRequest::with('checklists.suratTugasDetail.pelaksanas')->findOrFail($requestId);

        $pelaksana = $this->findPelaksana($requestModel, (int) $pelaksanaId);
        abort_if(! $pelaksana, 404, 'Pelaksana tidak ditemukan.');

        $data = $this->buildDataForPelaksana($request, $requestModel, $pelaksana, (int) $pelaksanaId);

        $filename = 'Superkendis_' . $this->slug($pelaksana->nama_pelaksana) . '.' . $format;

        return $this->buildDocument($data, $format, $filename);
    }

    /**
     * Bulk download beberapa pelaksana Superkendis terpilih.
     * Payload: pelaksana[id][...] (checkbox) + format + method (separate|merged).
     */
    public function bulk(Request $request, $requestId)
    {
        $requestModel = FpaRequest::with('checklists.suratTugasDetail.pelaksanas')->findOrFail($requestId);

        $pelaksanas = $this->selectedPelaksanas($request, $requestModel);
        abort_if($pelaksanas->isEmpty(), 422, 'Pilih minimal satu pelaksana untuk generate Superkendis.');

        $this->ensureValidForExport($request, $pelaksanas);

        $format = $request->input('format', 'docx');
        if (! in_array($format, self::FORMATS, true)) {
            $format = 'docx';
        }

        $method = $request->input('method', 'separate');

        $datas = $pelaksanas->map(fn ($p) => [
            'pelaksana' => $p,
            'data' => $this->buildDataForPelaksana($request, $requestModel, $p, (int) $p->id),
        ]);

        if ($method === 'merged') {
            return $this->buildMerged($datas, $format);
        }

        return $this->buildSeparate($datas, $format);
    }

    protected function buildSeparate($datas, string $format)
    {
        $tmpDir = storage_path('app/superkendis-tmp/' . uniqid());
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        foreach ($datas as $item) {
            $this->writeDocument($item['data'], $format, $tmpDir . '/' . 'Superkendis_' . $this->slug($item['pelaksana']->nama_pelaksana) . '.' . $format);
        }

        $zip = new ZipArchive;
        $zipFile = storage_path('app/superkendis-tmp/superkendis-' . uniqid() . '.zip');
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            abort(500, 'Gagal membuat arsip ZIP.');
        }

        foreach (glob($tmpDir . '/*.' . $format) as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        array_map('unlink', glob($tmpDir . '/*.' . $format) ?: []);
        rmdir($tmpDir);

        return response()->download($zipFile, 'Superkendis_Pisah.zip')->deleteFileAfterSend(true);
    }

    /**
     * Gabungan: isi template per pelaksana lalu gabung dengan menggabungkan
     * isi <w:body> dari setiap dokumen agar tabel/border/style Word tetap utuh.
     */
    protected function buildMerged($datas, string $format)
    {
        $documentXml = 'word/document.xml';

        $sources = [];
        foreach ($datas as $item) {
            $tmp = storage_path('app/superkendis-filled-' . uniqid() . '.docx');
            $this->fillTemplate($item['data'], $tmp);
            $sources[] = $tmp;
        }

        $merged = storage_path('app/superkendis-merged-' . uniqid() . '.docx');

        $baseXml = $this->readEntry($sources[0], $documentXml);
        if ($baseXml === null) {
            abort(500, 'Gagal memproses dokumen Superkendis.');
        }

        // Setiap dokumen sumber disisipkan sebagai section tersendiri (berpindah halaman)
        // dengan mempertahankan seluruh XML (tabel, border, style) secara utuh.
        foreach (array_slice($sources, 1) as $src) {
            $srcXml = $this->readEntry($src, $documentXml);
            if ($srcXml === null) {
                continue;
            }
            $baseXml = $this->appendBody($baseXml, $srcXml);
        }

        $this->writeMergedDocx($sources[0], $merged, $baseXml);

        foreach ($sources as $s) {
            @unlink($s);
        }

        $filename = 'Superkendis_Gabungan.' . $format;

        return $this->downloadFinal($merged, $filename, $format, 'docx');
    }

    protected function readEntry(string $docxPath, string $entryName): ?string
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            return null;
        }
        $content = $zip->getFromName($entryName);
        $zip->close();

        return $content === false ? null : $content;
    }

    /**
     * Menyisipkan isi <w:body> dari dokumen sumber ke dokumen dasar,
     * sebelum <w:sectPr> penutup, menambah section break antar pelaksana.
     */
    protected function appendBody(string $baseXml, string $srcXml): string
    {
        if (! preg_match('#<w:body>(.*?)(<w:sectPr\b[^>]*>.*?</w:sectPr>)?</w:body>#s', $srcXml, $m)) {
            return $baseXml;
        }

        $content = $m[1];
        // Section break mempertahankan properti section (ukuran/orientasi halaman dst.)
        $sectPr = $m[2] ?? '';
        $content .= $sectPr !== '' ? $sectPr : '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

        return preg_replace(
            '#(<w:body>)(.*?)(<w:sectPr\b[^>]*>.*?</w:sectPr>)(</w:body>)#s',
            '$1$2' . $content . '$3$4',
            $baseXml,
            1
        );
    }

    /**
     * Menyalin seluruh isi ZIP dari dokumen dasar, mengganti document.xml dengan yang baru.
     */
    protected function writeMergedDocx(string $base, string $merged, string $baseXml): void
    {
        $src = new ZipArchive;
        $dst = new ZipArchive;
        if ($src->open($base) !== true || $dst->open($merged, ZipArchive::CREATE) !== true) {
            abort(500, 'Gagal membuat dokumen Superkendis gabungan.');
        }

        for ($idx = 0; $idx < $src->numFiles; $idx++) {
            $name = $src->getNameIndex($idx);
            if ($name === 'word/document.xml') {
                $dst->addFromString($name, $baseXml);
            } else {
                $dst->addFromString($name, $src->getFromIndex($idx));
            }
        }

        $src->close();
        $dst->close();
    }

    /**
     * Data Superkendis untuk satu pelaksana.
     */
    protected function buildDataForPelaksana(Request $request, FpaRequest $requestModel, $pelaksana, ?int $pelaksanaId): array
    {
        // Dukungan payload array per pelaksana (checkbox) dan flat (legacy).
        $input = $request->input('pelaksana.' . $pelaksanaId, $request->all());

        $kecamatan = trim((string) ($input['kecamatan'] ?? ''));
        $rate = $kecamatan !== ''
            ? SkRatePerjalanan::where('kecamatan', $kecamatan)->first()
            : null;

        $besaran = $rate ? (float) $rate->besaran_biaya_transport : 0;

        return [
            'nama_pelaksana' => $pelaksana->nama_pelaksana,
            'nip' => $this->normalizeNip((string) ($input['nip'] ?? '')),
            'kecamatan' => $kecamatan,
            'tanggal_perjalanan' => (string) ($input['tanggal_perjalanan'] ?? ''),
            'besaran_biaya' => $rate ? number_format($besaran, 0, ',', '.') : '-',
            'terbilang' => $rate ? ucwords(Terbilang::convert($besaran)) . ' Rupiah' : '-',
            'jabatan' => (string) ($input['jabatan'] ?? 'Petugas'),
            'jenis_perjalanan' => (string) ($input['jenis_perjalanan'] ?? 'pendataan lapangan'),
            'nomor_surat' => $pelaksana->nomor_surat ?: '-',
            'tanggal_surat_tugas' => $pelaksana->suratTugasDetail->tanggal_surat_tugas ?? '',
            'fpa' => $requestModel->nomor_fpa ?: 'Belum ada nomor FPA',
            'deskripsi' => $requestModel->deskripsi_permintaan,
        ];
    }

    protected function selectedPelaksanas(Request $request, FpaRequest $requestModel)
    {
        $all = $requestModel->checklists
            ->flatMap(fn ($c) => $c->suratTugasDetail ? $c->suratTugasDetail->pelaksanas : collect());

        // Jika payload berbasis checkbox (ada key pelaksana), gunakan id yang dipilih.
        if ($request->has('pelaksana')) {
            $ids = collect($request->input('pelaksana', []))
                ->map(fn ($_, $id) => (int) $id)
                ->values();

            return $all->filter(fn ($p) => $ids->contains((int) $p->id))->values();
        }

        // Backward compat: tanpa payload per pelaksana, gunakan semua pelaksana.
        return $all;
    }

    protected function findPelaksana(FpaRequest $requestModel, int $pelaksanaId)
    {
        return $requestModel->checklists
            ->flatMap(fn ($c) => $c->suratTugasDetail ? $c->suratTugasDetail->pelaksanas : collect())
            ->first(fn ($p) => (int) $p->id === $pelaksanaId);
    }

    protected function ensureValidForExport(Request $request, $pelaksanas)
    {
        foreach ($pelaksanas as $pelaksana) {
            $input = $request->input('pelaksana.' . $pelaksana->id, $request->all());
            if (trim((string) ($input['kecamatan'] ?? '')) === '' || (string) ($input['tanggal_perjalanan'] ?? '') === '') {
                abort(422, 'Kecamatan tujuan dan tanggal perjalanan wajib diisi untuk setiap pelaksana yang dipilih.');
            }
        }
    }

    /**
     * Generate dokumen tunggal berbasis template.
     * Flow: Template DOCX -> TemplateProcessor -> DOCX final (tidak rebuild).
     */
    protected function buildDocument(array $data, string $format, string $filename)
    {
        $tempDocx = storage_path('app/superkendis-generated-' . uniqid() . '.docx');
        $this->fillTemplate($data, $tempDocx);

        return $this->downloadFinal($tempDocx, $filename, $format, 'docx');
    }

    /**
     * Mengisi template Superkendis.docx dengan TemplateProcessor lalu
     * menyimpan langsung sebagai DOCX final (menjaga layout/table/border/tanda tangan).
     */
    protected function fillTemplate(array $data, string $outPath): void
    {
        $templatePath = $this->templatePath();

        // Langkah 1: isi placeholder dengan TemplateProcessor.
        $template = new TemplateProcessor($templatePath);
        $template->setMacroChars('{{', '}}');

        $tanggalSurat = $data['tanggal_surat_tugas'] ? date('Y-m-d', strtotime($data['tanggal_surat_tugas'])) : '';
        $tanggalPerjalanan = $data['tanggal_perjalanan'] ? date('d-m-Y', strtotime($data['tanggal_perjalanan'])) : '';

        $values = [
            'nama' => $data['nama_pelaksana'],
            'Nama' => $data['nama_pelaksana'],
            'NIP' => $data['nip'],
            'nomor surat tugas' => $data['nomor_surat'],
            'tanggal surat tugas' => $tanggalSurat,
            'tanggal perjalanan' => $tanggalPerjalanan,
            'biaya sk' => $data['besaran_biaya'],
            'terbilangnya berapa' => $data['terbilang'],
            'list dari 4 pilihan dropdown' => $data['jenis_perjalanan'],
            'Bisa Supervisor, PCL atau PML' => $data['jabatan'],
        ];

        foreach ($values as $key => $value) {
            try {
                $template->setValue($key, (string) $value);
            } catch (\Throwable $e) {
                // Abaikan placeholder yang tidak cocok persis.
            }
        }

        // Langkah 2: simpan dokumen yang sudah terisi (struktur & style tetap utuh).
        $template->saveAs($outPath);

        // Langkah 3: bersihkan sisa placeholder yang membungkus field Word (tanpa rebuild elemen).
        $this->cleanupTemplate($outPath, $data);
    }

    protected function templatePath(): string
    {
        $path = storage_path('app/public/' . self::TEMPLATE_PATH);
        if (file_exists($path)) {
            return $path;
        }
        abort(500, 'Template Superkendis.docx tidak ditemukan.');
    }

    /**
     * Membersihkan placeholder yang terpecah antar-run XML atau membungkus field Word
     * (mis. {{terbilangnya berapa: ...}} yang berisi MERGEFIELD) secara langsung pada
     * word/document.xml, tanpa melalui rebuild elemen PhpWord sehingga layout tetap utuh.
     */
    protected function cleanupTemplate(string $docxPath, array $data): void
    {
        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            return;
        }

        // Gabungkan run teks yang berurutan menjadi satu agar placeholder kontigu.
        $xml = preg_replace(
            '#</w:t></w:r><w:r\b[^>]*>(?:<w:rPr>.*?</w:rPr>)?<w:t\b[^>]*>#s',
            '',
            $xml
        );

        $replacements = [
            '#\{\{terbilangnya berapa:.*?\}\}#s' => $data['terbilang'],
            '#\{\{list dari 4 pilihan dropdown:.*?\}\}#s' => $data['jenis_perjalanan'],
            '#\{\{list dari.*?\}\}#s' => $data['jenis_perjalanan'],
            '#\{\{Bisa.*?\}\}#s' => $data['jabatan'] ?: 'Petugas',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $xml = preg_replace($pattern, $replacement, $xml);
        }

        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }

    protected function normalizeNip(string $nip): string
    {
        $nip = trim($nip);
        if ($nip === '') {
            return '-';
        }
        $digits = preg_replace('/\D/', '', $nip);
        if (strlen($digits) < 15 || strlen($digits) > 18) {
            return '-';
        }

        return $nip;
    }

    /**
     * Menyalin/hasil akhir dokumen. Untuk DOCX langsung dikirim; untuk PDF,
     * load hasil DOCX (struktur utuh) lalu dikonversi ke PDF.
     */
    protected function downloadFinal(string $path, string $filename, string $format, string $pathFormat)
    {
        if ($format === 'pdf') {
            $this->configurePdfRenderer();
            $writer = IOFactory::createWriter(IOFactory::load($path), 'PDF');
            $pdf = storage_path('app/superkendis-' . uniqid() . '.pdf');
            $writer->save($pdf);
            @unlink($path);

            return response()->download($pdf, $filename)->deleteFileAfterSend(true);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    protected function writeDocument(array $data, string $format, string $path)
    {
        $tempDocx = storage_path('app/superkendis-generated-' . uniqid() . '.docx');
        $this->fillTemplate($data, $tempDocx);

        if ($format === 'pdf') {
            $this->configurePdfRenderer();
            $writer = IOFactory::createWriter(IOFactory::load($tempDocx), 'PDF');
            $writer->save($path);
            @unlink($tempDocx);
            return;
        }

        copy($tempDocx, $path);
        @unlink($tempDocx);
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
