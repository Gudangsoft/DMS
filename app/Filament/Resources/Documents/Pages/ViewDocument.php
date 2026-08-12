<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Gate::before gives Super Admin a blanket `true` on every ability check,
            // bypassing DocumentPolicy::update()'s status guard — without also
            // checking isEditable() here, Super Admin would see Edit on a
            // Published/Archived document and saving would throw in the service layer.
            EditAction::make()
                ->visible(fn ($record) => $record->status->isEditable()),
        ];
    }
}
