<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentContent;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Config as PdfConfig;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

/**
 * Extracts plain text from a document's latest uploaded file so the public
 * search can match words *inside* the document, not just its title.
 *
 * Supports PDF (text layer only — scanned images have none) and DOCX/PPTX/XLSX
 * (Office Open XML). External links have no local bytes and are skipped.
 * Failures never break an upload: the document simply isn't searchable by
 * content until re-indexed (php artisan dms:index-content).
 */
class DocumentContentExtractor
{
    /** Files larger than this are skipped to keep memory/time bounded. */
    public const MAX_BYTES = 40 * 1024 * 1024;

    /** Stored text is capped; plenty for search, keeps the table lean. */
    public const MAX_CHARS = 500_000;

    public function index(Document $document): ?DocumentContent
    {
        $version = $document->latestVersion()->first();

        if (! $version || $version->isLink() || ! $version->file_path) {
            // Latest file is now an external link: drop any stale text.
            DocumentContent::where('document_id', $document->id)->delete();

            return null;
        }

        $text = $this->extract($version);

        return DocumentContent::updateOrCreate(
            ['document_id' => $document->id],
            [
                'document_version_id' => $version->id,
                'content' => $text,
                'extracted_at' => now(),
            ],
        );
    }

    public function extract(DocumentVersion $version): ?string
    {
        $disk = Storage::disk(config('documents.storage_disk'));

        if (! $disk->exists($version->file_path) || $disk->size($version->file_path) > self::MAX_BYTES) {
            return null;
        }

        $path = $disk->path($version->file_path);
        $extension = strtolower(pathinfo($version->file_name ?: $version->file_path, PATHINFO_EXTENSION));

        try {
            $text = match (true) {
                $version->mime_type === 'application/pdf' || $extension === 'pdf' => $this->fromPdf($path),
                in_array($extension, ['docx', 'pptx', 'xlsx'], true) => $this->fromOfficeXml($path, $extension),
                default => null,
            };
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if ($text === null) {
            return null;
        }

        $text = trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', preg_replace('/\R{3,}/u', "\n\n", $text)));

        return $text === '' ? null : mb_substr($text, 0, self::MAX_CHARS);
    }

    protected function fromPdf(string $path): string
    {
        $config = new PdfConfig;
        $config->setRetainImageContent(false); // don't hold image bytes in memory
        $config->setDecodeMemoryLimit(64 * 1024 * 1024);

        return (new PdfParser([], $config))->parseFile($path)->getText();
    }

    protected function fromOfficeXml(string $path, string $extension): ?string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }

        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $wanted = match ($extension) {
                'docx' => preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $name),
                'pptx' => preg_match('#^ppt/slides/slide\d+\.xml$#', $name),
                'xlsx' => $name === 'xl/sharedStrings.xml',
            };
            if ($wanted) {
                $xml = $zip->getFromIndex($i);
                // Paragraph/row ends become line breaks before tags are stripped.
                $xml = preg_replace('#</(w:p|a:p|si)>#', "\n", (string) $xml);
                $parts[] = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }
        $zip->close();

        return implode("\n", $parts);
    }
}
