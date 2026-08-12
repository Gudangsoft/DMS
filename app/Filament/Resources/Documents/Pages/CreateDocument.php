<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
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

        unset($data['file'], $data['file_url'], $data['source_type'], $data['change_notes']);

        return app(DocumentService::class)->create($data, $source, auth()->user());
    }
}
