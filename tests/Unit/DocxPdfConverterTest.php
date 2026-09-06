<?php

namespace Tests\Unit;

use App\Services\DocxPdfConverter;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Tests\TestCase;

class DocxPdfConverterTest extends TestCase
{
    public function test_convert_throws_when_docx_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak ditemukan');

        (new DocxPdfConverter)->convert(storage_path('app/nonexistent-'.uniqid().'.docx'));
    }

    public function test_convert_throws_when_soffice_unavailable(): void
    {
        $docx = $this->makeTempDocx();

        // Paksa memakai binary yang pasti tidak ada.
        config(['app.libreoffice_path' => 'soffice-'.uniqid().'-tidak-ada']);

        $converter = new class extends DocxPdfConverter
        {
            protected function binaryPath(): string
            {
                throw new RuntimeException(self::ERROR_NOT_AVAILABLE);
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(DocxPdfConverter::ERROR_NOT_AVAILABLE);

        $converter->convert($docx);
    }

    public function test_convert_creates_pdf_when_soffice_available(): void
    {
        if (! DocxPdfConverter::available()) {
            $this->markTestSkipped('LibreOffice/soffice tidak tersedia pada mesin ini.');
        }

        $docx = $this->makeTempDocx();

        $pdf = (new DocxPdfConverter)->convert($docx);

        $this->assertFileExists($pdf);
        $this->assertStringEndsWith('.pdf', $pdf);
        $this->assertNotEmpty(file_get_contents($pdf));

        $headers = @get_headers($pdf);
        $this->assertStringStartsWith('%PDF', substr((string) file_get_contents($pdf), 0, 4));

        @unlink($docx);
        @unlink($pdf);
    }

    protected function makeTempDocx(): string
    {
        $path = storage_path('app/converter-test-'.uniqid().'.docx');
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Test DOCX');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }
}