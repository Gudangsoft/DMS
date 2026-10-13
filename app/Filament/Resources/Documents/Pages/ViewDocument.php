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
            // DocumentPolicy::update() lets admins edit at any status and owners
            // only during Draft/Revision; the service layer enforces the same rule.
            EditAction::make()
                ->visible(fn ($record) => auth()->user()->can('update', $record)),
        ];
    }
}
