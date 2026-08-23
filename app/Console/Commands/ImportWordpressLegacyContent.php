<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use App\Models\DocumentType;
use App\Models\Page;
use App\Models\Unit;
use App\Models\User;
use App\Services\DocumentService;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * One-off migration from the old WordPress site (dms.stiekasihbangsa.ac.id)
 * into this DMS. The old site modeled each document *category* as a single
 * WP post whose body is an HTML accordion of <a href="...pdf"> links (grouped
 * by year/topic) rather than one post per document — so the real document
 * catalog has to be extracted by walking that markup, not the WP REST API's
 * post list directly. See scratchpad investigation this command grew out of.
 *
 * Idempotent: re-running skips documents whose wp_file_url has already been
 * imported (tracked via document_code = "WP-" . substr(md5(url), 0, 16)).
 */
class ImportWordpressLegacyContent extends Command
{
    protected $signature = 'wp:import-legacy
        {--dry-run : Preview counts and mapping without writing anything}
        {--limit= : Only process the first N extracted documents (for testing)}';

    protected $description = 'Import documents and content pages from the old WordPress site into this DMS';

    protected string $baseUrl = 'https://dms.stiekasihbangsa.ac.id';

    /** @var array<int, array<string, mixed>> */
    protected array $categoriesById = [];

    protected ?User $actor = null;

    protected ?DocumentType $fallbackType = null;

    public function handle(): int
    {
        // Some hosts run CLI with a very tight default memory_limit (seen as
        // low as 128M) — hundreds of sequential HTTP downloads plus Eloquent
        // model churn exhausts that quickly even though each step alone is
        // small. This is a batch job, not a web request, so raising it here
        // is safe and doesn't depend on editing php.ini on the server.
        ini_set('memory_limit', '512M');
        DB::disableQueryLog();

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->actor = User::role(UserRole::SuperAdmin->value)->first();
        if (! $this->actor) {
            $this->error('No Super Admin user found — cannot set an owner/approver for imported documents.');

            return self::FAILURE;
        }

        $this->info('Fetching category tree from '.$this->baseUrl.' ...');
        $this->categoriesById = $this->fetchAllCategories();

        $this->info('Fetching posts and pages ...');
        $posts = $this->fetchAll('/wp-json/wp/v2/posts', ['_fields' => 'id,slug,title,date,categories,content']);
        $pages = $this->fetchAll('/wp-json/wp/v2/pages', ['_fields' => 'id,slug,title,content']);
        $this->info('  '.count($posts).' posts, '.count($pages).' pages.');

        $catalog = $this->extractDocumentCatalog($posts);
        $catalog = $this->dedupeByUrl($catalog);
        if ($limit) {
            $catalog = array_slice($catalog, 0, $limit);
        }
        $this->info('Extracted '.count($catalog).' unique documents from post content.');

        $infoPages = $this->extractInfoPages($posts, $pages);
        $this->info('Found '.count($infoPages).' informational pages to import.');

        if ($dryRun) {
            $this->previewCatalog($catalog);
            $this->previewPages($infoPages);

            return self::SUCCESS;
        }

        $this->importPages($infoPages);
        $this->importDocuments($catalog);

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // WordPress fetching
    // ------------------------------------------------------------------

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchAllCategories(): array
    {
        $categories = $this->fetchAll('/wp-json/wp/v2/categories', ['per_page' => 100, '_fields' => 'id,name,slug,parent,count']);
        $byId = [];
        foreach ($categories as $c) {
            $byId[$c['id']] = $c;
        }

        return $byId;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    protected function fetchAll(string $path, array $query): array
    {
        $items = [];
        $page = 1;

        do {
            $response = Http::retry(2, 500)->timeout(30)->get($this->baseUrl.$path, $query + [
                'per_page' => $query['per_page'] ?? 20,
                'page' => $page,
            ]);

            if ($response->failed()) {
                if ($response->status() === 400 && $page > 1) {
                    break; // WP returns 400 "invalid page number" past the last page
                }
                $this->warn("Request failed for $path page $page: HTTP {$response->status()}");
                break;
            }

            $chunk = $response->json();
            if (! is_array($chunk) || $chunk === []) {
                break;
            }

            array_push($items, ...$chunk);
            $page++;
        } while (count($chunk) >= ($query['per_page'] ?? 20));

        return $items;
    }

    // ------------------------------------------------------------------
    // Extraction
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $posts
     * @return array<int, array<string, mixed>>
     */
    protected function extractDocumentCatalog(array $posts): array
    {
        $fileExtPattern = '/\.(pdf|docx?|xlsx?|pptx?)(\?.*)?$/i';
        $catalog = [];

        foreach ($posts as $post) {
            $content = $post['content']['rendered'] ?? '';
            if (trim($content) === '') {
                continue;
            }

            $postYear = (int) substr($post['date'] ?? '1970-01-01', 0, 4);

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root__">'.$content.'</div>');
            libxml_use_internal_errors(false);
            $xpath = new DOMXPath($dom);

            foreach ($xpath->query('//a[@href]') as $a) {
                $href = html_entity_decode($a->getAttribute('href'));
                $path = parse_url($href, PHP_URL_PATH) ?? '';
                if (! preg_match($fileExtPattern, $path)) {
                    continue;
                }

                $linkText = trim($a->textContent);
                if ($linkText === '') {
                    $linkText = basename($path);
                }

                $context = $this->nearestAccordionTitle($xpath, $a);
                $isPureYear = $context && preg_match('/^Tahun\s+\d{4}$/i', trim($context));
                $title = ($context && ! $isPureYear && stripos($linkText, $context) === false)
                    ? "$context - $linkText"
                    : $linkText;
                $title = Str::limit($title, 250, '');

                $year = $postYear;
                foreach ([$linkText, $context ?? '', $path] as $candidate) {
                    if (preg_match('/(20[0-2][0-9])/', $candidate, $ym)) {
                        $year = (int) $ym[1];
                        break;
                    }
                }

                $wpCatId = null;
                foreach (($post['categories'] ?? []) as $cid) {
                    if ($cid != 1) {
                        $wpCatId = $cid;
                        break;
                    }
                }
                if ($wpCatId === null && count($post['categories'] ?? [])) {
                    $wpCatId = $post['categories'][0];
                }

                $catName = $wpCatId ? ($this->categoriesById[$wpCatId]['name'] ?? null) : null;
                $parentId = $wpCatId ? ($this->categoriesById[$wpCatId]['parent'] ?? 0) : 0;
                $parentName = $parentId ? ($this->categoriesById[$parentId]['name'] ?? null) : null;

                $catalog[] = [
                    'title' => html_entity_decode($title),
                    'year' => $year,
                    'file_url' => $href,
                    'wp_category_name' => $catName ? html_entity_decode($catName) : null,
                    'wp_category_parent_name' => $parentName ? html_entity_decode($parentName) : null,
                    'wp_post_title' => html_entity_decode($post['title']['rendered'] ?? ''),
                ];
            }
        }

        return $catalog;
    }

    protected function nearestAccordionTitle(DOMXPath $xpath, \DOMNode $node): ?string
    {
        $depth = 0;
        while ($node && $depth < 8) {
            if ($node instanceof DOMElement && str_contains($node->getAttribute('class'), 'accordion-item')) {
                $spans = $xpath->query('.//a[contains(@class,"accordion-title")]//span', $node);
                if ($spans->length > 0) {
                    return trim($spans->item(0)->textContent);
                }
                break;
            }
            $node = $node->parentNode;
            $depth++;
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $catalog
     * @return array<int, array<string, mixed>>
     */
    protected function dedupeByUrl(array $catalog): array
    {
        $seen = [];
        $out = [];
        foreach ($catalog as $item) {
            if (isset($seen[$item['file_url']])) {
                continue;
            }
            $seen[$item['file_url']] = true;
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $posts
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<int, array<string, mixed>>
     */
    protected function extractInfoPages(array $posts, array $pages): array
    {
        $out = [];

        // The 7 native WP "pages" — two are empty theme boilerplate, skip those.
        $skipSlugs = ['kekasih', 'dms'];
        foreach ($pages as $p) {
            if (in_array($p['slug'], $skipSlugs, true)) {
                continue;
            }
            $content = trim(strip_tags($p['content']['rendered'] ?? ''));
            if ($content === '') {
                continue;
            }
            $out[] = [
                'slug' => $p['slug'],
                'title' => html_entity_decode($p['title']['rendered']),
                'content' => $p['content']['rendered'],
            ];
        }

        // Posts that carry zero downloadable-file links are informational
        // content (profile/visi-misi/etc.), not document listings.
        $fileExtPattern = '/\.(pdf|docx?|xlsx?|pptx?)(\?.*)?$/i';
        foreach ($posts as $post) {
            $content = $post['content']['rendered'] ?? '';
            if (trim(strip_tags($content)) === '') {
                continue;
            }
            if (preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\']/i', $content, $m)) {
                $hasFile = false;
                foreach ($m[1] as $href) {
                    if (preg_match($fileExtPattern, parse_url(html_entity_decode($href), PHP_URL_PATH) ?? '')) {
                        $hasFile = true;
                        break;
                    }
                }
                if ($hasFile) {
                    continue;
                }
            }

            $out[] = [
                'slug' => $post['slug'],
                'title' => html_entity_decode($post['title']['rendered']),
                'content' => $content,
            ];
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Mapping rules
    // ------------------------------------------------------------------

    /**
     * Top-level WP category name -> existing DocumentCategory code. These 5
     * categories already exist in this DMS (seeded ahead of this import) —
     * we reuse them rather than creating a parallel taxonomy.
     */
    protected function categoryCodeFor(?string $wpParentName, ?string $wpCatName): string
    {
        $name = $wpParentName ?? $wpCatName ?? '';

        return match (true) {
            str_contains($name, 'Dokumen Acuan') => 'DA',
            str_contains($name, 'SPMI') => 'DM',
            str_contains($name, 'Laporan Perguruan Tinggi') => 'ADM',
            str_contains($name, 'Surat Keputusan') => 'ADM',
            str_contains($name, 'Akreditasi') => 'AKR',
            str_contains($name, 'Pedoman Akademik') => 'DAK',
            str_contains($name, 'Satgas') => 'ADM',
            str_contains($name, 'Alumni') => 'ADM',
            str_contains($name, 'Mitra Kerjasama') => 'ADM',
            str_contains($name, 'Sosialisasi') => 'ADM',
            default => 'ADM',
        };
    }

    /**
     * WP subcategory name -> existing DocumentSubcategory code (null when no
     * confident match — the document still gets its top-level category).
     */
    protected function subcategoryCodeFor(?string $wpCatName): ?string
    {
        $name = $wpCatName ?? '';

        return match (true) {
            str_contains($name, 'Regulasi Pemerintah') => 'DA01',
            str_contains($name, 'Rencana Induk Pengembangan') => 'DA03',
            str_contains($name, 'Statuta') => 'DA04',
            str_contains($name, 'SOTK') && str_contains($name, 'SPMI') === false => 'DA05',
            str_contains($name, 'Rencana Strategis') || str_contains($name, 'Renstra') => 'DA06',
            str_contains($name, 'Rencana Operasional') || str_contains($name, 'Renop') => 'DA07',
            str_contains($name, 'RKAT') => 'DA08',
            str_contains($name, 'Kebijakan SPMI') => 'DM01',
            str_contains($name, 'Manual SPMI') => 'DM02',
            str_contains($name, 'Standar SPMI') => 'DM03',
            str_contains($name, 'SOP SPMI') => 'DM04',
            str_contains($name, 'Formulir SPMI') => 'DM06',
            $name === 'Pedoman Akademik' => 'DAK03',
            str_contains($name, 'SK ') || str_contains($name, 'Surat Keputusan') => 'ADM01',
            str_contains($name, 'Mitra Kerjasama') || str_contains($name, 'Dunia Usaha') || str_contains($name, 'Lembaga Pendidikan') => 'ADM04',
            str_contains($name, 'Laporan') => 'ADM05',
            default => null,
        };
    }

    /**
     * Unit code inferred from the WP category context — best-effort, since
     * the old site tracked no per-document owning unit at all.
     */
    protected function unitCodeFor(?string $wpParentName, ?string $wpCatName): string
    {
        $name = ($wpParentName ?? '').' '.($wpCatName ?? '');

        return match (true) {
            str_contains($name, 'LPPM') => 'LPPM',
            str_contains($name, 'Prodi Manajemen') => 'PRODI',
            str_contains($name, 'Prodi Akuntansi') => 'PRODI',
            preg_match('/PKKMB|PMB|Wisuda|Tracer Study|Alumni/i', $name) === 1 => 'BAAK',
            preg_match('/Kepuasan|VMTS|Tinjauan Kurikulum|Monitoring|\bRTM\b|\bRTL\b|Akreditasi|SPMI|Mutu/i', $name) === 1 => 'LPM',
            str_contains($name, 'Keuangan') => 'KEU',
            preg_match('/Pedoman Akademik/i', $name) === 1 => 'BAAK',
            default => 'REKT',
        };
    }

    protected function documentTypeFor(string $title): DocumentType
    {
        $name = match (true) {
            (bool) preg_match('/\bUndang[- ]?Undang\b|\bUU\b/i', $title) => 'Undang-Undang',
            (bool) preg_match('/Permendikbud|Kepmendikbud|Kemendikbudristek/i', $title) => 'Permendikbud',
            (bool) preg_match('/Peraturan Pemerintah|Permen(?!dikbud)|Kepdirjen|Keputusan Dirjen/i', $title) => 'Peraturan Pemerintah',
            (bool) preg_match('/^SK\b|Surat Keputusan/i', $title) => 'Surat Keputusan',
            (bool) preg_match('/\bSOP\b/i', $title) => 'SOP',
            (bool) preg_match('/Instruksi Kerja/i', $title) => 'Instruksi Kerja',
            (bool) preg_match('/\bKebijakan\b/i', $title) => 'Kebijakan',
            (bool) preg_match('/\bManual\b/i', $title) => 'Manual',
            (bool) preg_match('/\bFormulir\b/i', $title) => 'Formulir',
            (bool) preg_match('/Pedoman|Panduan/i', $title) => 'Pedoman',
            (bool) preg_match('/Laporan/i', $title) => 'Laporan',
            default => null,
        };

        if ($name === null) {
            return $this->fallbackType ??= DocumentType::firstOrCreate(
                ['name' => 'Dokumen Lainnya'],
                ['description' => 'Dokumen hasil migrasi WordPress tanpa jenis yang jelas.', 'is_active' => true],
            );
        }

        return DocumentType::where('name', $name)->firstOrFail();
    }

    // ------------------------------------------------------------------
    // Preview (--dry-run)
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $catalog
     */
    protected function previewCatalog(array $catalog): void
    {
        $byCategory = [];
        $missingSubcat = 0;
        $tooLarge = [];

        foreach ($catalog as $item) {
            $code = $this->categoryCodeFor($item['wp_category_parent_name'], $item['wp_category_name']);
            $sub = $this->subcategoryCodeFor($item['wp_category_name']);
            $byCategory[$code] = ($byCategory[$code] ?? 0) + 1;
            if ($sub === null) {
                $missingSubcat++;
            }
        }

        $this->info("\nDocuments per DocumentCategory code:");
        foreach ($byCategory as $code => $count) {
            $this->line("  $code: $count");
        }
        $this->line("  (no subcategory match: $missingSubcat)");

        $this->info("\nSample of 10 extracted documents:");
        foreach (array_slice($catalog, 0, 10) as $item) {
            $code = $this->categoryCodeFor($item['wp_category_parent_name'], $item['wp_category_name']);
            $sub = $this->subcategoryCodeFor($item['wp_category_name']) ?? '-';
            $unit = $this->unitCodeFor($item['wp_category_parent_name'], $item['wp_category_name']);
            $this->line("  [$code/$sub/$unit] ({$item['year']}) {$item['title']}");
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     */
    protected function previewPages(array $pages): void
    {
        $this->info("\nPages to import:");
        foreach ($pages as $p) {
            $this->line('  '.$p['slug'].' — '.$p['title']);
        }
    }

    // ------------------------------------------------------------------
    // Import
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $pages
     */
    protected function importPages(array $pages): void
    {
        $this->info("\nImporting pages ...");
        foreach ($pages as $p) {
            $slug = 'wp-'.$p['slug'];
            if (Page::where('slug', $slug)->exists()) {
                $this->line("  skip (exists): $slug");

                continue;
            }

            Page::create([
                'slug' => $slug,
                'title' => $p['title'],
                'content' => $p['content'],
                'excerpt' => Str::limit(trim(strip_tags($p['content'])), 200),
                'is_published' => true,
                'published_at' => now(),
            ]);
            $this->line("  + $slug");
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $catalog
     */
    protected function importDocuments(array $catalog): void
    {
        $this->info("\nImporting documents ...");
        $bar = $this->output->createProgressBar(count($catalog));
        $bar->start();

        $created = 0;
        $skippedExisting = 0;
        $skippedTooLarge = 0;
        $failed = [];
        $maxBytes = ((int) config('documents.max_upload_size')) * 1024;

        foreach ($catalog as $item) {
            $bar->advance();
            $code = 'WP-'.substr(md5($item['file_url']), 0, 16);

            if (Document::where('document_code', $code)->exists()) {
                $skippedExisting++;

                continue;
            }

            $tmpPath = null;

            try {
                $ext = strtolower(pathinfo(parse_url($item['file_url'], PHP_URL_PATH), PATHINFO_EXTENSION));
                $tmpPath = tempnam(sys_get_temp_dir(), 'wpimport_').'.'.$ext;

                // Streamed straight to disk via Guzzle's sink option instead of
                // buffering the whole body as a PHP string — the previous
                // approach held every downloaded file fully in memory, which is
                // what exhausted a tight 128M CLI memory_limit after ~86 files.
                $response = Http::timeout(60)->retry(2, 1000)->sink($tmpPath)->get($item['file_url']);
                if ($response->failed()) {
                    $failed[] = "{$item['title']} — HTTP {$response->status()}";

                    continue;
                }

                clearstatcache(true, $tmpPath);
                $size = file_exists($tmpPath) ? filesize($tmpPath) : 0;
                if ($size === 0) {
                    $failed[] = "{$item['title']} — empty response";

                    continue;
                }
                if ($maxBytes > 0 && $size > $maxBytes) {
                    $skippedTooLarge++;
                    $failed[] = "{$item['title']} — ".round($size / 1048576, 1).'MB exceeds max upload size';

                    continue;
                }

                $categoryCode = $this->categoryCodeFor($item['wp_category_parent_name'], $item['wp_category_name']);
                $category = DocumentCategory::where('code', $categoryCode)->firstOrFail();

                $subcategoryCode = $this->subcategoryCodeFor($item['wp_category_name']);
                $subcategory = $subcategoryCode
                    ? DocumentSubcategory::where('document_category_id', $category->id)->where('code', $subcategoryCode)->first()
                    : null;

                $unitCode = $this->unitCodeFor($item['wp_category_parent_name'], $item['wp_category_name']);
                $unit = Unit::where('code', $unitCode)->firstOrFail();

                $type = $this->documentTypeFor($item['title']);

                $originalName = basename(parse_url($item['file_url'], PHP_URL_PATH));
                $uploadedFile = new UploadedFile($tmpPath, $originalName, null, null, true);

                $document = DB::transaction(function () use ($item, $category, $subcategory, $unit, $type, $uploadedFile, $code) {
                    $document = app(DocumentService::class)->create([
                        'title' => $item['title'],
                        'description' => 'Diimpor dari WordPress DMS lama — sumber: "'.$item['wp_post_title'].'".',
                        'document_category_id' => $category->id,
                        'document_subcategory_id' => $subcategory?->id,
                        'document_type_id' => $type->id,
                        'unit_id' => $unit->id,
                        'owner_id' => $this->actor->id,
                        'year' => $item['year'],
                        'confidentiality_level' => 'public',
                        'is_public' => true,
                        'document_code' => $code,
                    ], $uploadedFile, $this->actor);

                    // Mirrors DocumentApprovalService::approveDirectly() + DocumentService::publish(),
                    // but skips ->notify() — a bulk historical migration shouldn't spam the
                    // acting admin's own notification center 700+ times.
                    DocumentApproval::create([
                        'document_id' => $document->id,
                        'reviewer_id' => $this->actor->id,
                        'status' => 'approved',
                        'comments' => 'Disetujui otomatis — migrasi dari WordPress DMS lama.',
                        'reviewed_at' => now(),
                    ]);
                    $document->status = 'approved';
                    $document->approved_by = $this->actor->id;
                    $document->approved_at = now();
                    $document->save();

                    $document->status = 'published';
                    $document->published_at = now();
                    $document->save();

                    return $document;
                });

                $created++;
            } catch (\Throwable $e) {
                $failed[] = "{$item['title']} — {$e->getMessage()}";
            } finally {
                if ($tmpPath && file_exists($tmpPath)) {
                    @unlink($tmpPath);
                }
                unset($response, $document, $uploadedFile, $tmpPath);

                // Eloquent models retain references to booted event listeners and
                // relations that PHP's refcounting alone doesn't always reclaim
                // promptly across a long-running loop — nudging the cyclic
                // collector periodically keeps memory flat instead of climbing
                // for the whole run.
                if ($bar->getProgress() % 20 === 0) {
                    gc_collect_cycles();
                }
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Created: $created | Skipped (already imported): $skippedExisting | Skipped (too large): $skippedTooLarge | Failed: ".count($failed));

        if ($failed) {
            $this->warn("\nFailures:");
            foreach ($failed as $f) {
                $this->line("  - $f");
            }
        }
    }
}
