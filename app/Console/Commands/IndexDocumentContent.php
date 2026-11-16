<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentContentExtractor;
use Illuminate\Console\Command;

/**
 * Builds the "search inside documents" index for files uploaded before the
 * feature existed (new uploads are indexed automatically). Safe to re-run:
 * by default only documents without an index entry are processed.
 */
class IndexDocumentContent extends Command
{
    protected $signature = 'dms:index-content
        {--force : Re-extract every document, not just the missing ones}
        {--id=* : Only these document IDs}';

    protected $description = 'Extract text from uploaded PDF/DOCX/PPTX/XLSX files for full-text search';

    public function handle(DocumentContentExtractor $extractor): int
    {
        @ini_set('memory_limit', '512M');

        $query = Document::query()
            ->when($this->option('id'), fn ($q, $ids) => $q->whereKey($ids))
            ->when(! $this->option('force') && ! $this->option('id'), fn ($q) => $q->doesntHave('content'));

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Semua dokumen sudah terindeks.');

            return self::SUCCESS;
        }

        $this->info("Mengindeks {$total} dokumen…");
        $bar = $this->output->createProgressBar($total);

        $withText = 0;
        $query->select('id')->chunkById(25, function ($documents) use ($extractor, $bar, &$withText) {
            foreach ($documents as $document) {
                $content = $extractor->index($document);
                if ($content?->content) {
                    $withText++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. {$withText} dari {$total} dokumen berisi teks yang bisa dicari.");
        $this->comment('Dokumen tanpa teks biasanya hasil scan (gambar) atau berupa tautan eksternal.');

        return self::SUCCESS;
    }
}
