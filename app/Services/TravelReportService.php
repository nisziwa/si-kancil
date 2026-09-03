<?php

namespace App\Services;

use App\Models\MasterRincianPok;
use App\Models\SuratTugasPelaksana;
use App\Models\TravelReport;
use App\Support\Tanggal;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Writer\PDF;

/**
 * Generator dokumen Laporan Perjalanan (Laporan Pendataan / Laporan Pengawasan
 * dan Pemeriksaan). Data nama & NIP diambil dari pelaksana Surat Tugas,
 * pembiayaan diambil dari Master POK (tidak di-hardcode).
 */
class TravelReportService
{
    const FORMATS = ['docx', 'pdf'];

    /**
     * Bangun data lengkap untuk generate dokumen.
     */
    public function buildData(TravelReport $report, SuratTugasPelaksana $pelaksana): array
    {
        $pok = $report->pokRincian;

        return [
            'jenis_laporan' => TravelReport::JENIS_LABELS[$report->jenis_laporan] ?? $report->jenis_laporan,
            'judul_laporan' => $report->judul_laporan,
            'tanggal_laporan' => Tanggal::format($report->tanggal_laporan, ''),
            'nama' => (string) $pelaksana->nama_pelaksana,
            'nip' => $this->nipOf($pelaksana),
            'nomor_surat' => (string) $pelaksana->nomor_surat,
            'pembiayaan' => $pok ? $this->pokLines($pok) : [],
        ];
    }

    protected function nipOf(SuratTugasPelaksana $pelaksana): string
    {
        $nip = $pelaksana->superkendis->nip ?? null;
        return trim((string) $nip) === '' ? '-' : (string) $nip;
    }

    /**
     * Baris pembiayaan dari POK (Program s/d Akun + rincian).
     *
     * @return array<int, array{label: string, value: string}>
     */
    protected function pokLines(MasterRincianPok $pok): array
    {
        $lines = [];
        if ($pok->program) {
            $lines[] = ['label' => 'Program', 'value' => $pok->program->kode_program.' - '.$pok->program->nama_program];
        }
        if ($pok->kegiatan) {
            $lines[] = ['label' => 'Kegiatan', 'value' => $pok->kegiatan->kode_kegiatan.' - '.$pok->kegiatan->nama_kegiatan];
        }
        if ($pok->output) {
            $lines[] = ['label' => 'Output', 'value' => $pok->output->kode_output.' - '.$pok->output->nama_output];
        }
        if ($pok->subOutput) {
            $lines[] = ['label' => 'Sub Output', 'value' => $pok->subOutput->kode_sub_output.' - '.$pok->subOutput->nama_sub_output];
        }
        if ($pok->komponen) {
            $lines[] = ['label' => 'Komponen', 'value' => $pok->komponen->kode_komponen.' - '.$pok->komponen->nama_komponen];
        }
        if ($pok->akun) {
            $lines[] = ['label' => 'Akun', 'value' => $pok->akun->kode_akun.' - '.$pok->akun->nama_akun];
        }
        $lines[] = ['label' => 'Rincian', 'value' => $pok->rincian];

        return $lines;
    }

    /**
     * Tulis dokumen DOCX/PDF ke lokasi penyimpanan.
     */
    public function write(array $data, string $format, string $path): void
    {
        $docx = $this->buildDocument($data);
        $tempDocx = storage_path('app/travel-report-'.uniqid().'.docx');
        $writer = IOFactory::createWriter($docx, 'Word2007');
        $writer->save($tempDocx);

        if ($format === 'pdf') {
            $this->configurePdfRenderer();
            $pdfWriter = IOFactory::createWriter(IOFactory::load($tempDocx), 'PDF');
            $pdfWriter->save($path);
            @unlink($tempDocx);

            return;
        }

        copy($tempDocx, $path);
        @unlink($tempDocx);
    }

    protected function buildDocument(array $data): PhpWord
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);

        // Header judul jenis laporan.
        $section->addText(
            $data['jenis_laporan'],
            ['name' => 'Times New Roman', 'size' => 14, 'bold' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addText('Nomor Surat Tugas: '.$data['nomor_surat'], $this->normal(), ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

        $section->addTextBreak(1);

        // Identitas.
        $section->addText('1. Pelaksana', $this->heading());
        $section->addText('Nama: '.$data['nama'], $this->normal());
        $section->addText('NIP: '.$data['nip'], $this->normal());

        // Judul kegiatan.
        $section->addText('2. Judul Kegiatan', $this->heading());
        $section->addText($data['judul_laporan'], $this->normal());

        // Tanggal laporan.
        $section->addText('3. Tanggal Laporan', $this->heading());
        $section->addText($data['tanggal_laporan'], $this->normal());

        // Pembiayaan kegiatan.
        $section->addText('5. Pembiayaan Kegiatan', $this->heading());
        if ($data['pembiayaan'] === []) {
            $section->addText('Belum ada data pembiayaan (POK belum dipilih).', $this->normal());
        } else {
            foreach ($data['pembiayaan'] as $line) {
                $section->addText($line['label'].': '.$line['value'], $this->normal());
            }
        }

        return $phpWord;
    }

    protected function heading(): array
    {
        return ['name' => 'Times New Roman', 'size' => 12, 'bold' => true];
    }

    protected function normal(): array
    {
        return ['name' => 'Times New Roman', 'size' => 12];
    }

    protected function configurePdfRenderer(): void
    {
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf/src/Dompdf.php'));
    }
}
