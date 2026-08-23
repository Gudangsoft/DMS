<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Follow-up to wp:import-legacy: switches the document category taxonomy
 * from this DMS's original 5 broad categories (DA/DM/DAK/AKR/ADM) to the
 * old WordPress site's 10-category breakdown (A-J), so the public
 * "Kategori Dokumen" sidebar matches it. Re-fetches the WP site to re-derive
 * each already-imported document's original category (matched back via its
 * document_code = "WP-" . substr(md5(file_url), 0, 16), the same key
 * wp:import-legacy stamped on creation), then reassigns it onto the new
 * DocumentCategory/DocumentSubcategory created by DocumentCategorySeeder /
 * DocumentSubcategorySeeder. Any document left on an old category code
 * (including ones never sourced from WordPress) falls back to a sensible
 * new category, then the old 5 categories are deleted.
 *
 * Safe to re-run: category/subcategory creation is updateOrCreate, and
 * documents already on a new-scheme category are simply reassigned again.
 */
class RestructureDocumentCategoriesToWordpress extends Command
{
    protected $signature = 'wp:restructure-categories {--dry-run : Preview the remap without writing anything}';

    protected $description = 'Move documents from the old 5-category taxonomy to the WordPress-matching 10-category (A-J) taxonomy';

    protected string $baseUrl = 'https://dms.stiekasihbangsa.ac.id';

    /** @var array<int, array<string, mixed>> */
    protected array $categoriesById = [];

    /**
     * Old category code -> new top-level code, for documents that can't be
     * traced back to a WordPress source (manually created, or a failed match).
     *
     * @var array<string, string>
     */
    protected array $oldToNewFallback = [
        'DA' => 'A', 'DM' => 'B', 'DAK' => 'F', 'AKR' => 'E', 'ADM' => 'D',
    ];

    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        DB::disableQueryLog();

        $dryRun = (bool) $this->option('dry-run');

        $this->info('Fetching category tree and posts from '.$this->baseUrl.' ...');
        $this->categoriesById = $this->fetchAllCategories();
        $posts = $this->fetchAll('/wp-json/wp/v2/posts', ['_fields' => 'id,slug,title,date,categories,content']);
        $catalog = $this->dedupeByUrl($this->extractDocumentCatalog($posts));
        $this->info('  '.count($posts).' posts, '.count($catalog).' unique document links.');

        if ($dryRun) {
            $this->previewRemap($catalog);

            return self::SUCCESS;
        }

        $this->info('Ensuring the 10-category taxonomy exists ...');
        $this->call('db:seed', ['--class' => 'DocumentCategorySeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'DocumentSubcategorySeeder', '--force' => true]);

        $categoryModels = DocumentCategory::whereIn('code', array_keys($this->taxonomyNames()))->get()->keyBy('code');
        $subcategoryModels = DocumentSubcategory::all()->keyBy('code');

        $this->info("\nRemapping documents traced back to WordPress ...");
        $bar = $this->output->createProgressBar(count($catalog));
        $bar->start();
        $updated = 0;
        $notFound = 0;

        foreach ($catalog as $item) {
            $bar->advance();
            $code = 'WP-'.substr(md5($item['file_url']), 0, 16);
            $document = Document::where('document_code', $code)->first();
            if (! $document) {
                $notFound++;

                continue;
            }

            $topCode = $this->topCodeFor($item['wp_category_parent_name'], $item['wp_category_name']);
            $subCode = $this->subCodeFor($topCode, $item['wp_category_name']);

            $document->document_category_id = $categoryModels[$topCode]->id;
            $document->document_subcategory_id = $subCode ? ($subcategoryModels[$subCode]->id ?? null) : null;
            $document->save();
            $updated++;

            if ($updated % 50 === 0) {
                gc_collect_cycles();
            }
        }
        $bar->finish();
        $this->newLine(2);

        $this->info('Remapping stray documents still on an old category ...');
        $strayUpdated = 0;
        foreach ($this->oldToNewFallback as $oldCode => $newCode) {
            $oldCategory = DocumentCategory::where('code', $oldCode)->first();
            if (! $oldCategory) {
                continue;
            }

            $stray = Document::where('document_category_id', $oldCategory->id)->get();
            foreach ($stray as $document) {
                $document->document_category_id = $categoryModels[$newCode]->id;
                $document->document_subcategory_id = null;
                $document->save();
                $strayUpdated++;
            }
        }

        $this->info('Deleting the old 5 categories (subcategories cascade) ...');
        DocumentCategory::whereIn('code', array_keys($this->oldToNewFallback))->delete();

        $this->info("\nUpdated via WordPress match: $updated | Not found (skipped): $notFound | Stray remapped: $strayUpdated");

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------
    // WordPress fetching — same approach as wp:import-legacy
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
                    break;
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
                    'file_url' => $href,
                    'wp_category_name' => $catName ? html_entity_decode($catName) : null,
                    'wp_category_parent_name' => $parentName ? html_entity_decode($parentName) : null,
                ];
            }
        }

        return $catalog;
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

    // ------------------------------------------------------------------
    // New taxonomy mapping
    // ------------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    protected function taxonomyNames(): array
    {
        return [
            'A' => 'Dokumen Acuan', 'B' => 'Dokumen SPMI', 'C' => 'Laporan Perguruan Tinggi',
            'D' => 'Surat Keputusan (SK)', 'E' => 'Info Akreditasi', 'F' => 'Pedoman Akademik & Non Akademik',
            'G' => 'Satgas PPKPT', 'H' => 'Alumni', 'I' => 'Mitra Kerjasama', 'J' => 'Sosialisasi/Pelatihan Dikti',
        ];
    }

    protected function topCodeFor(?string $wpParentName, ?string $wpCatName): string
    {
        $name = $wpParentName ?? $wpCatName ?? '';

        return match (true) {
            str_contains($name, 'Dokumen Acuan') => 'A',
            str_contains($name, 'SPMI') => 'B',
            str_contains($name, 'Laporan Perguruan Tinggi') => 'C',
            str_contains($name, 'Surat Keputusan') => 'D',
            str_contains($name, 'Akreditasi') => 'E',
            str_contains($name, 'Pedoman Akademik') => 'F',
            str_contains($name, 'Satgas') => 'G',
            str_contains($name, 'Alumni') => 'H',
            str_contains($name, 'Mitra Kerjasama') => 'I',
            str_contains($name, 'Sosialisasi') => 'J',
            default => 'A',
        };
    }

    protected function subCodeFor(string $topCode, ?string $wpCatName): ?string
    {
        $name = $wpCatName ?? '';

        $map = match ($topCode) {
            'A' => [
                'Regulasi Pemerintah' => 'A01',
                'Visi Misi Tujuan Strategi' => 'A02',
                'Rencana Induk Pengembangan' => 'A03',
                'Statuta' => 'A04',
                'SOTK' => 'A05',
                'Rencana Strategis' => 'A06', 'Renstra' => 'A06',
                'Rencana Operasional' => 'A07', 'Renop' => 'A07',
                'RKAT' => 'A08',
                'Resbang' => 'A09',
                'Panduan Akreditasi' => 'A10',
            ],
            'B' => [
                'Kebijakan SPMI' => 'B01',
                'Manual SPMI' => 'B02',
                'Standar SPMI' => 'B03',
                'Formulir SPMI' => 'B04',
                'SOP SPMI' => 'B05',
                'SOTK SPMI' => 'B06',
            ],
            'C' => [
                'Audit Laporan Keuangan' => 'C01',
                'Audit Mutu Internal' => 'C02',
                'Kepuasan Mahasiswa' => 'C03',
                'Kepuasan Pengguna' => 'C04',
                'Kepuasan Tendik' => 'C05',
                'VMTS' => 'C06',
                'Monitoring' => 'C07',
                'Tinjauan Kurikulum' => 'C08',
                'Sarana' => 'C09',
                'Evaluasi Kerjasama' => 'C10',
                'Laporan LPPM' => 'C11',
                'PMB' => 'C12',
                'PKKMB' => 'C13',
                'Pembukaan Tabung' => 'C14',
                'RTM' => 'C15',
                'RTL' => 'C16',
            ],
            'D' => [
                'Wakil Ketua' => 'D02',
                'Ketua STIE' => 'D01',
                'Ketua Prodi Manajemen' => 'D03',
                'Ketua Prodi Akuntansi' => 'D04',
                'Ketua LPPM' => 'D05',
            ],
            'E' => [
                'Akreditasi Manajemen' => 'E01',
                'Akreditasi Akuntansi' => 'E02',
            ],
            'F' => [
                'Non Akademik' => 'F02',
                'Pedoman Akademik' => 'F01',
            ],
            'G' => [
                'Satgas PPKPT' => 'G01',
            ],
            'H' => [
                'Tracer Study' => 'H01',
                'Buku Wisuda' => 'H02',
            ],
            'I' => [
                'Dunia Usaha' => 'I01',
                'Lembaga Pendidikan' => 'I02',
            ],
            'J' => [
                'Materi' => 'J01',
                'Video' => 'J02',
            ],
            default => [],
        };

        foreach ($map as $needle => $code) {
            if (stripos($name, $needle) !== false) {
                return $code;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Preview (--dry-run)
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $catalog
     */
    protected function previewRemap(array $catalog): void
    {
        $byTop = [];
        $matched = 0;
        $notMatched = 0;

        foreach ($catalog as $item) {
            $code = 'WP-'.substr(md5($item['file_url']), 0, 16);
            $exists = Document::where('document_code', $code)->exists();
            $exists ? $matched++ : $notMatched++;

            $topCode = $this->topCodeFor($item['wp_category_parent_name'], $item['wp_category_name']);
            $byTop[$topCode] = ($byTop[$topCode] ?? 0) + 1;
        }

        $this->info("\nDocuments that will move to each new category:");
        $names = $this->taxonomyNames();
        foreach ($byTop as $code => $count) {
            $this->line("  $code ({$names[$code]}): $count");
        }
        $this->line("\nMatched to an existing imported Document: $matched | Not found (would be skipped): $notMatched");
    }
}
