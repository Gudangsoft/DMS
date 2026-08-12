<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentDownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Poin 10/18/30 — the only way a document file ever reaches a browser. The file
 * never lives under public/ and is never linked to directly; every hit is
 * authorized against DocumentPolicy and logged to document_downloads.
 *
 * Link-sourced versions have no local bytes for us to stream — the download is
 * still authorized and logged here, then handed off to the external host via
 * redirect (poin 18: every download stays counted regardless of source).
 */
class SecureDownloadController extends Controller
{
    public function __invoke(Request $request, Document $document, DocumentDownloadService $downloads, ?DocumentVersion $version = null): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $document);

        $version ??= $document->latestVersion;

        abort_if($version === null, 404, 'Dokumen belum memiliki file.');
        abort_if($version->document_id !== $document->id, 404);

        if ($version->isLink()) {
            $downloads->record($document, $request->user(), $request);

            return redirect()->away($version->external_url);
        }

        $disk = config('documents.storage_disk');
        abort_unless(Storage::disk($disk)->exists($version->file_path), 404, 'File tidak ditemukan di storage.');

        $downloads->record($document, $request->user(), $request);

        return Storage::disk($disk)->download($version->file_path, $version->file_name);
    }
}
