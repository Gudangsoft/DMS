<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentDownloadService
{
    /**
     * Poin 18: every download is logged and document.download_count is kept
     * in sync, atomically.
     */
    public function record(Document $document, ?User $user, Request $request): DocumentDownload
    {
        return DB::transaction(function () use ($document, $user, $request) {
            $download = DocumentDownload::create([
                'document_id' => $document->id,
                'user_id' => $user?->id,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'downloaded_at' => now(),
            ]);

            $document->increment('download_count');

            return $download;
        });
    }
}
