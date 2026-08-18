<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Services\DocumentApprovalService;
use App\Services\DocumentService;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    /**
     * Routed through DocumentService instead of a plain Document::create() so the
     * first file version, checksum, and observer-managed defaults are always
     * created consistently — the same path used by the frontend/service tests.
     */
    protected function handleRecordCreation(array $data): Document
    {
        $source = ($data['source_type'] ?? 'upload') === 'link'
            ? $data['file_url']
            : $data['file'];

        $approveAndPublish = (bool) ($data['approve_and_publish'] ?? false);

        unset($data['file'], $data['file_url'], $data['source_type'], $data['change_notes'], $data['approve_and_publish']);

        $document = app(DocumentService::class)->create($data, $source, auth()->user());

        // Re-checked server-side: the toggle is only rendered for users with both
        // permissions, but the client-submitted value can't be trusted on its own.
        $actor = auth()->user();
        if ($approveAndPublish && $actor->can('documents.approve') && $actor->can('documents.publish')) {
            app(DocumentApprovalService::class)->approveDirectly($document, $actor, 'Disetujui otomatis saat pembuatan dokumen.');
            app(DocumentService::class)->publish($document);
        }

        return $document->fresh();
    }
}
