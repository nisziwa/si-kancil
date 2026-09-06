<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Konversi DOCX ke PDF memakai LibreOffice headless agar hasil PDF identik
 * dengan template Word (menggantikan DomPDF yang mengubah layout).
 */
class DocxPdfConverter
{
    public const ERROR_NOT_AVAILABLE = 'LibreOffice/soffice belum tersedia pada server.';
    public const ERROR_CONVERSION = 'Gagal melakukan konversi dokumen DOCX ke PDF.';

    /**
     * Konversi satu file DOCX menjadi PDF di folder yang sama.
     * Nama PDF mengikuti nama DOCX (extensi .pdf).
     */
    public function convert(string $docxPath): string
    {
        if (! is_file($docxPath)) {
            throw new RuntimeException('File DOCX tidak ditemukan: '.$docxPath);
        }

        $soffice = $this->binaryPath();
        $outDir = dirname($docxPath);

        $process = new Process([
            $soffice,
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $outDir,
            $docxPath,
        ]);

        try {
            $process->setTimeout(120);
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new RuntimeException(self::ERROR_CONVERSION.' ('.$docxPath.')', 0, $e);
        }

        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        if (! is_file($pdfPath)) {
            throw new RuntimeException(self::ERROR_CONVERSION.' ('.$docxPath.')');
        }

        return $pdfPath;
    }

    /**
     * Cek apakah soffice tersedia (untuk skip test otomatis).
     */
    public static function available(): bool
    {
        try {
            (new static)->binaryPath();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    protected function binaryPath(): string
    {
        $configured = config('app.libreoffice_path');
        if ($configured && $this->isExecutable($configured)) {
            return $configured;
        }

        foreach ($this->defaultCandidates() as $candidate) {
            if ($this->isExecutable($candidate)) {
                return $candidate;
            }
        }

        if ($this->isExecutable('soffice')) {
            return 'soffice';
        }

        throw new RuntimeException(self::ERROR_NOT_AVAILABLE);
    }

    protected function isExecutable(string $binary): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return is_file($binary) || $this->onPath($binary);
        }

        return is_executable($binary) || $this->onPath($binary);
    }

    protected function onPath(string $binary): bool
    {
        foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $dir) {
            if ($dir === '') {
                continue;
            }
            $candidate = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$binary;
            if (is_file($candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lokasi soffice umum per platform.
     *
     * @return array<int, string>
     */
    protected function defaultCandidates(): array
    {
        $candidates = [
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
            '/opt/libreoffice/program/soffice',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ];

        if (DIRECTORY_SEPARATOR === '\\') {
            $localAppData = getenv('LOCALAPPDATA');
            if ($localAppData) {
                $candidates[] = rtrim($localAppData, '\\').'\\Programs\\LibreOffice\\program\\soffice.exe';
            }
        }

        return $candidates;
    }
}