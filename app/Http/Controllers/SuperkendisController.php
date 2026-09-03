<?php

namespace App\Http\Controllers;

use App\Models\ChecklistHistory;
use App\Models\Request as FpaRequest;
use App\Models\SkRatePerjalanan;
use App\Models\Superkendis;
use App\Support\Terbilang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\PDF;

class SuperkendisController extends Controller
{
    const FORMATS = ['docx', 'pdf'];

    const TEMPLATE_PATH = '[template] Superkendis 2.docx';

    /**
     * Jenis kegiatan (static list, tanpa master database).
     */
    const JENIS_KEGIATAN_LIST = [
        'Pelatihan',
        'Pendataan Lapangan',
        'Pengawasan Lapangan',
        'Supervisi Lapangan',
    ];

    /**
     * Nama checklist dokumen yang otomatis menjadi Lengkap setelah seluruh
     * Superkendis pelaksana berhasil digenerate.
     */
    const INTEGRASI_CHECKLIST = 'Pengeluaran Riil + Surat Non Kendaraan Dinas';

    /**
     * Ringkasan Superkendis untuk halaman detail FPA.
     */
    public function index($requestId)
    {
        $requestModel = FpaRequest::with([
            'checklists.suratTugasDetail.pelaksanas.superkendis',
            'expenseType',
        ])->findOrFail($requestId);

        $stChecklist = $requestModel->checklists
            ->first(fn ($c) => str_contains($c->nama_dokumen, 'Surat Tugas'));

        $superkendisDone = $stChecklist && $stChecklist->status === 'Lengkap';

        $kecamatans = SkRatePerjalanan::orderBy('kecamatan')->get();

        // Normalisasi ?pelaksana=12 dan ?pelaksana[]=12 menjadi array.
        $selected = request('pelaksana', []);
        if (! is_array($selected)) {
            $selected = [$selected];
        }
        $selectedPelaksanaIds = collect($selected)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return view('requests.superkendis', compact(
            'requestModel',
            'stChecklist',
            'superkendisDone',
            'kecamatans',
            'selectedPelaksanaIds'
        ));
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

        // Simpan file hasil ke storage + catat histori (updateOrCreate).
        $stored = $this->storeGeneratedFile($pelaksana, $data, $format);

        // Tandai checklist integrasi menjadi Lengkap bila seluruh pelaksana sudah digenerate.
        $this->markIntegrasiChecklistLengkap($requestModel);

        $filename = 'Superkendis_'.$this->slug($pelaksana->nama_pelaksana).'.'.$format;

        return response()->download($stored['local_path'], $filename);
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

        // Data per pelaksana.
        $datas = $pelaksanas->map(fn ($p) => [
            'pelaksana' => $p,
            'data' => $this->buildDataForPelaksana($request, $requestModel, $p, (int) $p->id),
        ]);

        // Selalu simpan file + histori per pelaksana (updateOrCreate), tanpa duplikasi.
        $stored = [];
        foreach ($datas as $item) {
            $stored[] = $this->storeGeneratedFile($item['pelaksana'], $item['data'], $format);
        }

        // Bila seluruh pelaksana Surat Tugas sudah digenerate, tandai checklist integrasi Lengkap.
        $this->markIntegrasiChecklistLengkap($requestModel);

        // Satu pelaksana -> langsung download file DOCX (tanpa ZIP / merge).
        if ($pelaksanas->count() === 1) {
            $filename = 'Superkendis_'.$this->slug($pelaksanas->first()->nama_pelaksana).'.'.$format;

            return response()->download($stored[0]['local_path'], $filename);
        }

        if ($method === 'merged') {
            return $this->buildMerged($datas, $format);
        }

        return $this->buildSeparateStored($stored, $format);
    }

    /**
     * ZIP dari file hasil yang sudah tersimpan (mode pisah file).
     */
    protected function buildSeparateStored(array $stored, string $format)
    {
        $tmpDir = storage_path('app/superkendis-tmp/'.uniqid());
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $files = [];
        foreach ($stored as $item) {
            $name = 'Superkendis_'.$this->slug($item['pelaksana']->nama_pelaksana).'.'.$format;
            $target = $tmpDir.'/'.$name;
            copy($item['local_path'], $target);
            $files[] = $target;
        }

        $zip = new ZipArchive;
        $zipFile = storage_path('app/superkendis-tmp/superkendis-'.uniqid().'.zip');
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            abort(500, 'Gagal membuat arsip ZIP.');
        }

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        array_map('unlink', $files ?: []);
        rmdir($tmpDir);

        return response()->download($zipFile, 'Superkendis_Pisah.zip')->deleteFileAfterSend(true);
    }

    /**
     * Simpan file hasil generate ke storage dan catat histori per pelaksana.
     * Generate ulang = updateOrCreate (tidak membuat duplikat record).
     *
     * @return array{local_path: string, relative_path: string}
     */
    protected function storeGeneratedFile($pelaksana, array $data, string $format): array
    {
        $relative = 'spj-files/superkendis/'.$data['nomor_surat'].'_'.$this->slug($pelaksana->nama_pelaksana).'.'.$format;
        $localPath = Storage::disk('public')->path($relative);

        // Pastikan direktori ada.
        $dir = dirname($localPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Tulis file DOCX (dan konversi PDF bila diminta) langsung ke lokasi penyimpanan.
        $this->writeDocument($data, $format, $localPath);

        // Simpan/update record Superkendis per pelaksana.
        $field = $format === 'pdf' ? 'file_pdf' : 'file_docx';
        $payload = [
            'nip' => $data['nip'] === '-' ? null : $data['nip'],
            'kecamatan' => $data['kecamatan'],
            'tanggal_perjalanan' => $data['tanggal_perjalanan'] !== '' ? $data['tanggal_perjalanan'] : null,
            'jenis_kegiatan' => $data['jenis_kegiatan'],
            'jabatan' => $data['jabatan'],
            $field => $relative,
        ];

        if (Superkendis::where('surat_tugas_pelaksana_id', $pelaksana->id)->exists()) {
            Superkendis::where('surat_tugas_pelaksana_id', $pelaksana->id)->update($payload);
        } else {
            $payload['surat_tugas_pelaksana_id'] = $pelaksana->id;
            Superkendis::create($payload);
        }

        return [
            'local_path' => $localPath,
            'relative_path' => $relative,
            'pelaksana' => $pelaksana,
        ];
    }

    /**
     * Setelah seluruh pelaksana Surat Tugas berhasil digenerate, tandai checklist
     * integrasi menjadi Lengkap. Hanya checklist "Pengeluaran Riil + Surat Non
     * Kendaraan Dinas" yang diubah. Jika sudah berstatus "Perlu Perbaikan"
     * jangan otomatis ditimpa. Seluruh checklist lain tidak disentuh.
     */
    protected function markIntegrasiChecklistLengkap(FpaRequest $requestModel): void
    {
        $stChecklist = $requestModel->checklists
            ->first(fn ($c) => str_contains($c->nama_dokumen, 'Surat Tugas'));

        if (! $stChecklist || ! $stChecklist->suratTugasDetail) {
            return;
        }

        $pelaksanas = $stChecklist->suratTugasDetail->pelaksanas;
        if ($pelaksanas->isEmpty()) {
            return;
        }

        // Seluruh pelaksana harus sudah memiliki record Superkendis tersimpan.
        foreach ($pelaksanas as $pelaksana) {
            if (! $pelaksana->superkendis) {
                return;
            }
        }

        $target = $requestModel->checklists
            ->first(fn ($c) => $c->nama_dokumen === self::INTEGRASI_CHECKLIST);

        if (! $target) {
            return;
        }

        // Jangan overwrite status selain 'Belum Ada'/'Belum Lengkap'.
        if ($target->status === 'Perlu Perbaikan' || $target->status === 'Lengkap') {
            return;
        }

        $oldStatus = $target->status;
        $target->status = 'Lengkap';
        $target->save();

        ChecklistHistory::create([
            'checklist_id' => $target->id,
            'status_lama' => $oldStatus,
            'status_baru' => 'Lengkap',
            'user_id' => Auth::id(),
        ]);
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
            $tmp = storage_path('app/superkendis-filled-'.uniqid().'.docx');
            $this->fillTemplate($item['data'], $tmp);
            $sources[] = $tmp;
        }

        $merged = storage_path('app/superkendis-merged-'.uniqid().'.docx');

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

        $filename = 'Superkendis_Gabungan.'.$format;

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

        // Setiap Superkendis pelaksana WAJIB dimulai pada halaman baru.
        // Catatan: <w:sectPr> tidak valid jika diletakkan di tengah <w:body>
        // (hanya boleh di akhir body atau di dalam <w:pPr> paragraf), sehingga
        // pendekatan section-break di tengah sering diabaikan Word. Solusi paling
        // andal adalah menyisipkan paragraf page-break eksplisit DI ANTARA konten
        // pelaksana, sehingga pelaksana berikutnya (termasuk judulnya) mulai di
        // halaman baru tanpa merusak tabel/border/tanda tangan.
        $pageBreak = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

        $insert = $pageBreak.$content;

        return preg_replace(
            '#(<w:body>)(.*?)(<w:sectPr\b[^>]*>.*?</w:sectPr>)(</w:body>)#s',
            '$1$2'.$insert.'$3$4',
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
        $input = $request->input('pelaksana.'.$pelaksanaId, $request->all());

        $kecamatan = trim((string) ($input['kecamatan'] ?? ''));
        $rate = $kecamatan !== ''
            ? SkRatePerjalanan::where('kecamatan', $kecamatan)->first()
            : null;

        $besaran = $rate ? (float) $rate->besaran_biaya_transport : 0;

        $jenisKegiatan = trim((string) ($input['jenis_kegiatan'] ?? ''));
        if ($jenisKegiatan === '') {
            $jenisKegiatan = trim((string) ($input['jenis_perjalanan'] ?? 'Pendataan Lapangan'));
        }
        if ($jenisKegiatan === '') {
            $jenisKegiatan = 'Pendataan Lapangan';
        }

        return [
            'nama_pelaksana' => $pelaksana->nama_pelaksana,
            'nip' => $this->normalizeNip((string) ($input['nip'] ?? '')),
            'kecamatan' => $kecamatan,
            'tanggal_perjalanan' => (string) ($input['tanggal_perjalanan'] ?? ''),
            'besaran_biaya' => $rate ? number_format($besaran, 0, ',', '.') : '-',
            'terbilang' => $rate ? ucwords(Terbilang::convert($besaran)).' Rupiah' : '-',
            'jenis_kegiatan' => $jenisKegiatan,
            'jabatan' => $this->jabatanUntukKegiatan($jenisKegiatan),
            'nomor_surat' => $pelaksana->nomor_surat ?: '-',
            'tanggal_surat_tugas' => $pelaksana->suratTugasDetail->tanggal_surat_tugas ?? '',
            'fpa' => $requestModel->nomor_fpa ?: 'Belum ada nomor FPA',
            'deskripsi' => $requestModel->deskripsi_permintaan,
        ];
    }

    /**
     * Pemetaan jenis kegiatan ke jabatan pelaksana.
     */
    protected function jabatanUntukKegiatan(string $jenisKegiatan): string
    {
        return match (strtolower(trim($jenisKegiatan))) {
            'supervisi lapangan', 'supervisi' => 'Supervisor',
            'pengawasan lapangan', 'pengawasan' => 'PML',
            'pelatihan' => 'PCL',
            default => 'PCL',
        };
    }

    /**
     * Format tanggal ke format Indonesia, mis. "25 Juli 2026".
     */
    protected function formatTanggalIndonesia($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return date('j', $ts).' '.$bulan[(int) date('n', $ts) - 1].' '.date('Y', $ts);
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
            $input = $request->input('pelaksana.'.$pelaksana->id, $request->all());
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
        $tempDocx = storage_path('app/superkendis-generated-'.uniqid().'.docx');
        $this->fillTemplate($data, $tempDocx);

        return $this->downloadFinal($tempDocx, $filename, $format, 'docx');
    }

    /**
     * Mengisi template Superkendis 2.docx dengan TemplateProcessor lalu
     * menyimpan langsung sebagai DOCX final (menjaga layout/table/border/tanda tangan).
     */
    protected function fillTemplate(array $data, string $outPath): void
    {
        $templatePath = $this->templatePath();

        // Langkah 1: isi placeholder dengan TemplateProcessor.
        $template = new TemplateProcessor($templatePath);
        $template->setMacroChars('{{', '}}');

        // Seluruh tanggal wajib dalam format Indonesia (mis. "25 Juli 2026").
        $tanggalSurat = $this->formatTanggalIndonesia($data['tanggal_surat_tugas']);
        $tanggalPerjalanan = $this->formatTanggalIndonesia($data['tanggal_perjalanan']);

        $values = [
            'nama' => $data['nama_pelaksana'],
            'Nama' => $data['nama_pelaksana'],
            'NIP' => $data['nip'],
            'nomor surat tugas' => $data['nomor_surat'],
            'tanggal surat tugas' => $tanggalSurat,
            'tanggal perjalanan' => $tanggalPerjalanan,
            'biaya sk' => $data['besaran_biaya'],
            'terbilangnya berapa' => $data['terbilang'],
            'jenis kegiatan' => $data['jenis_kegiatan'],
            'jabatan' => $data['jabatan'],
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

        // Langkah 3: bersihkan sisa placeholder (tanpa rebuild elemen).
        $this->cleanupTemplate($outPath, $data);
    }

    protected function templatePath(): string
    {
        $path = storage_path('app/public/'.self::TEMPLATE_PATH);
        if (file_exists($path)) {
            return $path;
        }
        abort(500, 'Template Superkendis 2.docx tidak ditemukan.');
    }

    /**
     * Membersihkan placeholder yang terpecah antar-run XML secara langsung pada
     * word/document.xml, tanpa melalui rebuild elemen PhpWord sehingga layout tetap utuh.
     * Semua placeholder {{key}} diganti dari data agar tidak ada yang tertinggal.
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

        $tanggalSurat = $this->formatTanggalIndonesia($data['tanggal_surat_tugas']);
        $tanggalPerjalanan = $this->formatTanggalIndonesia($data['tanggal_perjalanan']);

        $map = [
            'nama' => $data['nama_pelaksana'],
            'NIP' => $data['nip'],
            'nomor surat tugas' => $data['nomor_surat'],
            'tanggal surat tugas' => $tanggalSurat,
            'tanggal perjalanan' => $tanggalPerjalanan,
            'biaya sk' => $data['besaran_biaya'],
            'terbilangnya berapa' => $data['terbilang'],
            'jenis kegiatan' => $data['jenis_kegiatan'],
            'jabatan' => $data['jabatan'],
        ];

        foreach ($map as $key => $replacement) {
            $pattern = '#\{\{\s*'.preg_quote($key, '#').'\s*\}\}#u';
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
            $writer = $this->pdfWriter($path);
            $pdf = storage_path('app/superkendis-'.uniqid().'.pdf');
            $writer->save($pdf);
            @unlink($path);

            return response()->download($pdf, $filename)->deleteFileAfterSend(true);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    protected function writeDocument(array $data, string $format, string $path)
    {
        $tempDocx = storage_path('app/superkendis-generated-'.uniqid().'.docx');
        $this->fillTemplate($data, $tempDocx);

        if ($format === 'pdf') {
            $this->configurePdfRenderer();
            $writer = $this->pdfWriter($tempDocx);
            $writer->save($path);
            @unlink($tempDocx);

            return;
        }

        copy($tempDocx, $path);
        @unlink($tempDocx);
    }

    /**
     * Bangun writer PDF dari DOCX yang sudah terisi. Menggunakan API resmi
     * editCallback PhpWord untuk menghapus CSS border default tebal (table/td
     * 1px solid black) yang disuntikkan writer HTML, sehingga border PDF
     * mengikuti template DOCX (hanya tabel yang memang perlu garis).
     */
    protected function pdfWriter(string $docxPath): PDF
    {
        $writer = IOFactory::createWriter(IOFactory::load($docxPath), 'PDF');
        $writer->setEditCallback(fn (string $html): string => $this->stripDefaultTableBorders($html));

        return $writer;
    }

    /**
     * Hapus CSS global bawaan PhpWord yang memberi border tebal ke SEMUA tabel
     * dan sel: `table {border: 1px solid black; ...}` dan `td {border: 1px solid
     * black;}`. Perbaiki juga agar border sel menyatu (collapse) seperti di Word.
     */
    protected function stripDefaultTableBorders(string $html): string
    {
        // table: buang `border: 1px solid black;`, aktifkan collapse.
        $html = preg_replace(
            '/table\s*\{[^}]*border\s*:\s*1px\s+solid\s+black;[^}]*\}/i',
            'table { border-collapse: collapse; border-spacing: 0px; width: 100%; }',
            $html
        );

        // td: buang border default sama sekali.
        $html = preg_replace(
            '/td\s*\{[^}]*border\s*:\s*1px\s+solid\s+black;[^}]*\}/i',
            'td { }',
            $html
        );

        return $html;
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
