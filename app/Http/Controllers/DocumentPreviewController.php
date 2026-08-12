<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Poin 17 — streams the PDF bytes inline (not as an attachment, and without
 * incrementing download_count / writing a document_downloads row) so the
 * frontend can render it with PDF.js without the user downloading a copy.
 * Only PDF is previewable in-browser; other office formats fall back to
 * download-only in the UI.
 *
 * Link-sourced versions have no local bytes to stream — we don't control the
 * remote host's CORS headers, and fetching an arbitrary admin-supplied URL
 * server-side would open an SSRF hole, so previewing one just opens the
 * external URL directly and lets the browser's own PDF viewer handle it.
 */
class DocumentPreviewController extends Controller
{
    public function __invoke(Request $request, Document $document, ?DocumentVersion $version = null): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $document);

        $version ??= $document->latestVersion;

        abort_if($version === null, 404, 'Dokumen belum memiliki file.');
        abort_if($version->document_id !== $document->id, 404);

        if ($version->isLink()) {
            return redirect()->away($version->external_url);
        }

        abort_unless($version->mime_type === 'application/pdf', 415, 'Preview hanya tersedia untuk file PDF.');

        $disk = config('documents.storage_disk');
        abort_unless(Storage::disk($disk)->exists($version->file_path), 404);

        return Storage::disk($disk)->response($version->file_path, $version->file_name, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$version->file_name.'"',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
