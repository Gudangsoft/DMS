<?php

namespace App\Http\Controllers;

use App\Models\AssessorPackage;
use App\Models\Document;
use App\Services\DocumentDownloadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Public, account-less access for external assessors to an AssessorPackage.
 * The secret token in the URL is the credential; documents outside the
 * package are never reachable through these routes. Every file access is
 * counted in document_downloads like any other download.
 */
class AssessorPackageController extends Controller
{
    public function show(string $token): View|Response
    {
        $package = $this->resolve($token);

        if (! $package->isAccessible()) {
            return response()->view('assessor-packages.expired', ['package' => $package], 410);
        }

        $package->increment('view_count');

        $documents = $package->documents()
            ->with(['category', 'subcategory', 'latestVersion'])
            ->get()
            ->sortBy([
                fn ($a, $b) => ($a->category?->sort_order ?? 0) <=> ($b->category?->sort_order ?? 0),
                fn ($a, $b) => ($a->subcategory?->sort_order ?? 0) <=> ($b->subcategory?->sort_order ?? 0),
                fn ($a, $b) => $b->year <=> $a->year,
            ])
            ->groupBy(fn (Document $d) => $d->category?->name ?? 'Lainnya');

        return response()
            ->view('assessor-packages.show', [
                'package' => $package,
                'documentsByCategory' => $documents,
                'total' => $documents->flatten()->count(),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function file(Request $request, string $token, string $uuid, DocumentDownloadService $downloads): Response|RedirectResponse
    {
        $package = $this->resolve($token);
        abort_unless($package->isAccessible(), 410);

        /** @var Document $document */
        $document = $package->documents()->where('uuid', $uuid)->firstOrFail();
        $version = $document->latestVersion;
        abort_if($version === null, 404, 'Dokumen belum memiliki file.');

        $downloads->record($document, null, $request);

        if ($version->isLink()) {
            return redirect()->away($version->external_url);
        }

        $disk = Storage::disk(config('documents.storage_disk'));
        abort_unless($disk->exists($version->file_path), 404, 'File tidak ditemukan.');

        // PDFs open in the browser; other formats download.
        $disposition = $version->mime_type === 'application/pdf' ? 'inline' : 'attachment';

        return $disk->response($version->file_path, $version->file_name, [
            'X-Robots-Tag' => 'noindex, nofollow',
        ], $disposition);
    }

    public function zip(Request $request, string $token, DocumentDownloadService $downloads): BinaryFileResponse
    {
        $package = $this->resolve($token);
        abort_unless($package->isAccessible(), 410);

        @set_time_limit(300);

        $disk = Storage::disk(config('documents.storage_disk'));
        $documents = $package->documents()->with(['category', 'latestVersion'])->get();

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        $zipPath = $tmpDir.'/paket-'.Str::random(12).'.zip';

        $zip = new ZipArchive;
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Gagal membuat ZIP.');

        $links = [];
        $used = [];
        foreach ($documents as $i => $document) {
            $version = $document->latestVersion;
            if (! $version) {
                continue;
            }

            if ($version->isLink()) {
                $links[] = "- {$document->title}\n  {$version->external_url}";

                continue;
            }

            if (! $disk->exists($version->file_path)) {
                continue;
            }

            $folder = Str::of($document->category?->name ?? 'Lainnya')->slug(' ')->title()->limit(60, '');
            $ext = pathinfo($version->file_name ?: $version->file_path, PATHINFO_EXTENSION);
            $name = Str::of($document->title)->replaceMatches('/[\\\\\/:*?"<>|]+/', '-')->squish()->limit(100, '');
            $entry = "{$folder}/{$name}.{$ext}";
            // Same title twice in one folder → keep both.
            if (isset($used[$entry])) {
                $entry = "{$folder}/{$name} ({$document->id}).{$ext}";
            }
            $used[$entry] = true;

            $zip->addFile($disk->path($version->file_path), $entry);
            $downloads->record($document, null, $request);
        }

        if ($links) {
            $zip->addFromString(
                'DOKUMEN-TAUTAN-EKSTERNAL.txt',
                "Dokumen berikut tersimpan di situs eksternal, buka lewat tautannya:\n\n".implode("\n\n", $links)."\n"
            );
        }

        if ($zip->numFiles === 0) {
            // An empty archive isn't written to disk at all.
            $zip->addFromString('BACA-SAYA.txt', "Paket ini belum berisi file yang bisa diunduh.\n");
        }

        $zip->close();

        $filename = Str::slug($package->name) ?: 'paket-dokumen';

        return response()->download($zipPath, "{$filename}.zip")->deleteFileAfterSend();
    }

    protected function resolve(string $token): AssessorPackage
    {
        return AssessorPackage::where('token', $token)->firstOrFail();
    }
}
