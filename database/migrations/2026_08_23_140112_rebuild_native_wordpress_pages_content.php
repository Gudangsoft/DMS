<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data fix, not a schema change. wp:import-legacy dumped these 5 native
 * WordPress "pages" (Sambutan Ketua, Tentang DMS, Struktur Organisasi,
 * Tugas & Fungsi, Document Management System) verbatim — same page-builder
 * markup problem already fixed for the Video Materi Pelatihan page: nested
 * .banner/.text-box/.row/.col wrapper divs plus 8-17 scoped <style> blocks
 * per page, none of which render usefully inside this app's plain Tailwind
 * `prose` container. Worse than just "unstyled": several of those <style>
 * blocks set text color to white for a dark page-builder background this
 * app never renders, and the ORIGINAL wp:import-legacy excerpt was built via
 * strip_tags() — which does not drop <style> tag *contents*, so the raw CSS
 * text was leaking into the visible page excerpt (e.g. "STRUKTUR ORGANISASI
 * ... #text-2725914855 { font-size: 0.75rem; ... }").
 *
 * This re-fetches each page from the old site, strips every <style>/<script>
 * tag, pulls the hero banner's heading/lead text and background image into
 * Page::title's companions (featured_image/excerpt — pages/show.blade.php
 * already renders those above the content, so reusing them beats hand-
 * building a duplicate hero inside content), and rebuilds the rest of the
 * body as plain semantic HTML (headings/paragraphs/lists/images) that the
 * existing `prose` wrapper already styles correctly. Any inline image still
 * pointing at the old site is downloaded and rehosted on the 'public' disk,
 * same as the standalone image-rehosting migration.
 */
return new class extends Migration
{
    protected string $baseUrl = 'https://dms.stiekasihbangsa.ac.id/wp-json/wp/v2/pages';

    /** @var array<string, string> old URL => local URL, shared across pages */
    protected array $assetCache = [];

    public function up(): void
    {
        $slugs = [
            'sambutan-ketua-stie-kasih-bangsa',
            'tentang-document-management-system-stie-kasih-bangsa',
            'struktur-organisasi',
            'tugas-fungsi',
            'document-management-system',
        ];

        foreach ($slugs as $slug) {
            $response = Http::timeout(30)->get($this->baseUrl, ['slug' => $slug, '_fields' => 'content']);
            if ($response->failed() || empty($response->json())) {
                continue;
            }

            $raw = $response->json()[0]['content']['rendered'] ?? '';
            if ($raw === '') {
                continue;
            }

            $cleaned = $this->clean($raw);

            $update = ['content' => $cleaned['content'], 'updated_at' => now()];
            if ($cleaned['excerpt'] !== null) {
                $update['excerpt'] = $cleaned['excerpt'];
            }
            if ($cleaned['featured_image'] !== null) {
                $update['featured_image'] = $cleaned['featured_image'];
            }

            DB::table('pages')->where('slug', 'wp-'.$slug)->update($update);
        }
    }

    /**
     * @return array{content: string, excerpt: ?string, featured_image: ?string}
     */
    protected function clean(string $raw): array
    {
        // Hero background image lives only inside a <style> block
        // (#banner-xxx .bg.bg-loaded { background-image: url(...) }) — grab
        // it via regex before the DOM parse strips <style> tags entirely.
        $heroImageUrl = null;
        if (preg_match('/background-image:\s*url\(([^)]*dms\.stiekasihbangsa\.ac\.id[^)]*)\)/i', $raw, $m)) {
            $heroImageUrl = trim($m[1], "\"' ");
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root__">'.$raw.'</div>');
        libxml_use_internal_errors(false);
        $xpath = new DOMXPath($dom);

        foreach (iterator_to_array($xpath->query('//style | //script')) as $node) {
            $node->parentNode?->removeChild($node);
        }

        $heroTitle = null;
        $heroSubtitle = null;
        $banners = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' banner ')]");
        if ($banners->length > 0) {
            $bannerNode = $banners->item(0);
            $h = $xpath->query('.//h1 | .//h2 | .//h3', $bannerNode);
            if ($h->length > 0) {
                $heroTitle = trim(preg_replace('/\s+/', ' ', $h->item(0)->textContent));
            }
            $lead = $xpath->query(".//p[contains(concat(' ', normalize-space(@class), ' '), ' lead ')]", $bannerNode);
            if ($lead->length > 0) {
                $heroSubtitle = trim(preg_replace('/\s+/', ' ', $lead->item(0)->textContent));
            }
            $bannerNode->parentNode?->removeChild($bannerNode);
        }

        $root = $xpath->query("//div[@id='__root__']")->item(0);
        $contentXpath = './/h1 | .//h2 | .//h3 | .//h4 | .//h5 | .//h6'
            ." | .//p[not(ancestor::ul) and not(ancestor::ol) and normalize-space(.) != '']"
            .' | .//ul[not(ancestor::ul) and not(ancestor::ol)]'
            .' | .//ol[not(ancestor::ul) and not(ancestor::ol)]'
            .' | .//img[not(ancestor::ul) and not(ancestor::ol)]';
        $nodes = $xpath->query($contentXpath, $root);

        // Some page-builder layouts render two copies of the same header
        // block (one per responsive breakpoint, toggled via CSS this app
        // never loads) — both land in the DOM, so both would otherwise show
        // up as visible duplicate text once <style> is stripped.
        $body = '';
        $firstParagraph = null;
        $lastText = null;
        foreach ($nodes as $node) {
            if ($node->nodeName === 'img') {
                $src = $node->getAttribute('src');
                $local = str_contains($src, 'dms.stiekasihbangsa.ac.id') ? $this->rehost($src) : null;
                $finalSrc = $local ?? $src;
                if ($finalSrc === '') {
                    continue;
                }
                $alt = e($node->getAttribute('alt'));
                $body .= '<img src="'.e($finalSrc).'" alt="'.$alt.'" class="rounded-lg shadow-sm" />'."\n";
                $lastText = null;

                continue;
            }

            $normalizedText = trim(preg_replace('/\s+/', ' ', $node->textContent));
            if ($normalizedText !== '' && $normalizedText === $lastText) {
                continue;
            }
            $lastText = $normalizedText;

            if ($node->nodeName === 'p' && $firstParagraph === null) {
                $firstParagraph = $normalizedText;
            }

            $this->stripPresentationalAttributes($node);
            $body .= trim($dom->saveHTML($node))."\n";
        }

        $featuredImage = $heroImageUrl ? $this->rehostToPath($heroImageUrl) : null;

        return [
            'content' => $body,
            // A hero without a ".lead" subtitle would otherwise leave the
            // page's excerpt untouched — still the original CSS-polluted
            // strip_tags() excerpt from wp:import-legacy. Falling back to
            // the first real paragraph guarantees every page gets a clean one.
            'excerpt' => $heroSubtitle ?? ($firstParagraph !== null ? Str::limit($firstParagraph, 200) : null),
            'featured_image' => $featuredImage,
        ];
    }

    protected function stripPresentationalAttributes(DOMNode $node): void
    {
        if ($node instanceof DOMElement) {
            foreach (['id', 'style', 'class'] as $attr) {
                $node->removeAttribute($attr);
            }
            foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                if (str_starts_with($attribute->nodeName, 'data-')) {
                    $node->removeAttribute($attribute->nodeName);
                }
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->stripPresentationalAttributes($child);
        }
    }

    /**
     * Downloads and rehosts an asset, returning its public URL for use in an
     * <img src="..."> or similar. Cached per source URL across all 5 pages.
     */
    protected function rehost(string $url): ?string
    {
        $path = $this->rehostToPath($url);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Same as rehost() but returns the raw storage path (what Page::
     * featured_image expects — pages/show.blade.php builds the URL itself).
     */
    protected function rehostToPath(string $url): ?string
    {
        if (isset($this->assetCache[$url])) {
            return $this->assetCache[$url];
        }

        $response = Http::timeout(30)->get($url);
        if ($response->failed() || $response->body() === '') {
            return null;
        }

        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
        $storedPath = 'page-assets/'.Str::uuid().'.'.$ext;
        Storage::disk('public')->put($storedPath, $response->body());

        return $this->assetCache[$url] = $storedPath;
    }

    public function down(): void
    {
        // The original page-builder markup isn't worth restoring — it never
        // rendered usefully in this app either way.
    }
};
