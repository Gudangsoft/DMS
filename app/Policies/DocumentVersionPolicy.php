<?php

namespace App\Policies;

use App\Models\DocumentVersion;
use App\Models\User;

class DocumentVersionPolicy
{
    public function view(?User $user, DocumentVersion $version): bool
    {
        return app(DocumentPolicy::class)->view($user, $version->document);
    }

    public function download(?User $user, DocumentVersion $version): bool
    {
        return app(DocumentPolicy::class)->download($user, $version->document);
    }

    /**
     * Uploading a new version is only allowed while the parent document itself
     * is still editable (poin 39, poin 11 — versi lama tidak boleh dihapus, versi
     * baru hanya ditambahkan lewat alur edit dokumen yang sah).
     */
    public function create(User $user, DocumentVersion $version): bool
    {
        return app(DocumentPolicy::class)->update($user, $version->document);
    }
}
