<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Data fix, not a schema change. wp:import-legacy dumped this page's raw WP
 * content verbatim into Page::content, but this particular post used the old
 * theme's page-builder markup (nested .banner/.col-inner divs, ~12 scoped
 * <style> blocks) to lay out 4 YouTube video cards — none of that renders
 * usefully inside this app's plain Tailwind `prose` container, since the
 * builder's own site-wide CSS/JS isn't loaded here. This re-fetches the
 * source post, extracts each video's title/subtitle/YouTube link, and
 * replaces the page's content with clean semantic HTML (a responsive grid of
 * embedded YouTube iframes) that actually renders correctly.
 */
return new class extends Migration
{
    protected string $sourceUrl = 'https://dms.stiekasihbangsa.ac.id/wp-json/wp/v2/posts?slug=video-materi-pelatihan&_fields=content';

    public function up(): void
    {
        $response = Http::timeout(30)->get($this->sourceUrl);
        if ($response->failed() || empty($response->json())) {
            return;
        }

        $rawContent = $response->json()[0]['content']['rendered'] ?? '';
        if ($rawContent === '') {
            return;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root__">'.$rawContent.'</div>');
        libxml_use_internal_errors(false);
        $xpath = new DOMXPath($dom);

        // The intro paragraph is the first top-level <p>, before the video grid.
        $intro = '';
        $firstP = $xpath->query("//div[@id='__root__']/p[1]");
        if ($firstP->length > 0) {
            $intro = trim($dom->saveHTML($firstP->item(0)));
        }

        $videos = [];
        foreach ($xpath->query("//div[contains(@class,'banner')][contains(@class,'has-hover')]") as $banner) {
            $heading = $xpath->query(".//h4[contains(@class,'uppercase')]", $banner);
            $desc = $xpath->query(".//div[contains(@class,'text-inner')]/div/p", $banner);
            $link = $xpath->query(".//a[contains(@class,'open-video')]", $banner);

            if ($link->length === 0) {
                continue;
            }

            $href = $link->item(0)->getAttribute('href');
            preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{6,})/', $href, $m);
            if (! isset($m[1])) {
                continue;
            }

            $videos[] = [
                'title' => $heading->length > 0 ? trim($heading->item(0)->textContent) : 'Materi Pelatihan',
                'desc' => $desc->length > 0 ? trim($desc->item(0)->textContent) : '',
                'youtube_id' => $m[1],
            ];
        }

        if ($videos === []) {
            return;
        }

        $cards = '';
        foreach ($videos as $v) {
            $title = e($v['title']);
            $desc = e($v['desc']);

            // A dead/removed YouTube video still embeds "successfully" — the
            // iframe just renders YouTube's own bare "Video unavailable"
            // error inside it, which looks broken and out of place next to
            // this app's styling. Checking oEmbed first (this is the same
            // public, keyless endpoint YouTube's oEmbed spec exposes — no
            // API key needed) means a dead video gets this app's own graceful
            // placeholder instead of YouTube's ugly default.
            $isAvailable = Http::timeout(10)->get('https://www.youtube.com/oembed', [
                'url' => 'https://www.youtube.com/watch?v='.$v['youtube_id'],
                'format' => 'json',
            ])->successful();

            if ($isAvailable) {
                $embedUrl = 'https://www.youtube.com/embed/'.e($v['youtube_id']);
                $media = <<<HTML
                    <iframe src="{$embedUrl}" title="{$title}" class="h-full w-full" loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    HTML;
            } else {
                // Inline styles, not Tailwind utility classes: this HTML is
                // injected into the page at runtime from the database, not
                // present in any Blade/JS file Tailwind's build scans — a
                // class with no matching rule elsewhere in the compiled
                // stylesheet (e.g. a gradient "to-*" color never used
                // literally anywhere else) silently has zero effect, and the
                // box renders blank instead of just unstyled.
                $media = <<<HTML
                    <div style="display:flex;height:100%;width:100%;flex-direction:column;align-items:center;justify-content:center;gap:0.5rem;background:linear-gradient(135deg,#0b2545,#13315c);padding:0 1rem;text-align:center;color:rgba(255,255,255,0.85);">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="height:2rem;width:2rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <p style="font-size:0.75rem;font-weight:500;margin:0;">Video ini sudah tidak tersedia di YouTube</p>
                    </div>
                    HTML;
            }

            $cards .= <<<HTML
                <div class="vmp-card not-prose">
                    <div class="vmp-card-media">
                        {$media}
                    </div>
                    <div class="vmp-card-body">
                        <span class="vmp-card-eyebrow">{$title}</span>
                        <p class="vmp-card-desc">{$desc}</p>
                    </div>
                </div>
                HTML;
        }

        // A real <style> tag, not Tailwind utility classes: this HTML is
        // injected into the page at runtime from the database, never present
        // in any Blade/JS file Tailwind's build scans, so utility classes
        // used only here compile to nothing. A scoped stylesheet with its
        // own class names sidesteps that entirely and is the only way to get
        // hover/transition effects, which inline style="" can't express.
        $style = <<<'HTML'
            <style>
                .vmp-grid { display: grid; gap: 1.5rem; margin-top: 1.5rem; }
                @media (min-width: 640px) { .vmp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
                .vmp-card {
                    display: flex; flex-direction: column; height: 100%;
                    background: #fff; border-radius: 0.75rem; overflow: hidden;
                    box-shadow: 0 1px 3px rgba(11, 37, 69, 0.08);
                    border: 1px solid #e5e7eb;
                    transition: box-shadow 0.2s ease, transform 0.2s ease;
                }
                .vmp-card:hover { box-shadow: 0 12px 24px -8px rgba(11, 37, 69, 0.22); transform: translateY(-2px); }
                .vmp-card-media { position: relative; aspect-ratio: 16 / 9; width: 100%; background: #0b2545; }
                .vmp-card-media iframe { width: 100%; height: 100%; border: 0; display: block; }
                .vmp-card-body { flex: 1 1 auto; display: flex; flex-direction: column; gap: 0.375rem; padding: 1rem 1.25rem 1.25rem; border-top: 3px solid #d4af37; }
                .vmp-card-eyebrow { font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #0b2545; }
                .vmp-card-desc { flex: 1 1 auto; margin: 0; font-size: 0.875rem; line-height: 1.4; color: #4b5563; }
            </style>
            HTML;

        $content = $style.$intro."\n<div class=\"vmp-grid\">\n{$cards}\n</div>";

        DB::table('pages')
            ->where('slug', 'wp-video-materi-pelatihan')
            ->update(['content' => $content, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // The original page-builder markup isn't worth restoring — it never
        // rendered usefully in this app either way.
    }
};
