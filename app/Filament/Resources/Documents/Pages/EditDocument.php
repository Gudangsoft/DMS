<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Services\DocumentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Document
    {
        $source = ($data['source_type'] ?? 'upload') === 'link'
            ? ($data['file_url'] ?? null)
            : ($data['file'] ?? null);

        $changeNotes = $data['change_notes'] ?? null;
        unset($data['file'], $data['file_url'], $data['source_type'], $data['change_notes']);

        return app(DocumentService::class)->updateWithNewFile($record, $data, $source ?: null, auth()->user(), $changeNotes);
    }
}
