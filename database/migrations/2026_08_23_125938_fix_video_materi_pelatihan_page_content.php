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
            $embedUrl = 'https://www.youtube.com/embed/'.e($v['youtube_id']);
            $cards .= <<<HTML
                <div class="not-prose overflow-hidden rounded-lg border border-gray-200">
                    <div class="aspect-video w-full">
                        <iframe src="{$embedUrl}" title="{$title}" class="h-full w-full" loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <div class="p-4">
                        <h4 class="text-sm font-semibold uppercase text-brand-navy">{$title}</h4>
                        <p class="mt-1 text-sm text-gray-600">{$desc}</p>
                    </div>
                </div>
                HTML;
        }

        $content = $intro."\n<div class=\"not-prose mt-6 grid gap-6 sm:grid-cols-2\">\n{$cards}\n</div>";

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
