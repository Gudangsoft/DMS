<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data fix, not a schema change. wp:import-legacy copied each page's raw HTML
 * verbatim, and a few of them reference banner/header images, one structure-
 * chart <img>, and one <source> video as absolute URLs back on the old
 * WordPress site (e.g. background-image: url(https://dms.stiekasihbangsa.ac.id/wp-content/...),
 * <source src="https://dms.stiekasihbangsa.ac.id/kasih-bangsa.mp4">) rather
 * than a locally stored file — unlike the 662 documents, which were always
 * downloaded and stored on this app's own disk during import. If the old
 * site is ever taken down, those would silently break. This downloads each
 * one, stores it on the 'public' disk, and rewrites the page content to
 * point at the local copy instead.
 *
 * Deliberately scoped to src="..." and url(...) wrapping contexts (not just
 * "any mention of the domain") — wp-tugas-fungsi's content also has a plain
 * prose sentence naming the old site's URL, which isn't a broken asset
 * reference and shouldn't be treated like one.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pages = DB::table('pages')->where('slug', 'like', 'wp-%')->get(['id', 'slug', 'content']);
        $cache = []; // old URL => new local URL, so a shared file is only downloaded once
        $domainPattern = 'dms\.stiekasihbangsa\.ac\.id';

        foreach ($pages as $page) {
            $urls = [];
            if (preg_match_all('/src=["\']([^"\']*'.$domainPattern.'[^"\']*)["\']/i', $page->content, $m)) {
                $urls = array_merge($urls, $m[1]);
            }
            if (preg_match_all('/url\(([^)]*'.$domainPattern.'[^)]*)\)/i', $page->content, $m)) {
                $urls = array_merge($urls, $m[1]);
            }
            $urls = array_unique($urls);
            if ($urls === []) {
                continue;
            }

            $content = $page->content;

            foreach ($urls as $url) {
                if (! isset($cache[$url])) {
                    $cache[$url] = $this->rehost($url, $page->slug);
                }

                if ($cache[$url] !== null) {
                    $content = str_replace($url, $cache[$url], $content);
                }
            }

            if ($content !== $page->content) {
                DB::table('pages')->where('id', $page->id)->update(['content' => $content, 'updated_at' => now()]);
            }
        }
    }

    protected function rehost(string $url, string $pageSlug): ?string
    {
        $response = Http::timeout(30)->get($url);
        if ($response->failed() || $response->body() === '') {
            return null;
        }

        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
        $storedPath = 'page-assets/'.$pageSlug.'/'.Str::uuid().'.'.$ext;

        Storage::disk('public')->put($storedPath, $response->body());

        return Storage::disk('public')->url($storedPath);
    }

    public function down(): void
    {
        // Rehosted files are left in place — reverting the URLs in content
        // would just point back at the old (possibly gone) WordPress site.
    }
};
