<?php

namespace App\Filament\Resources\DocumentSubcategories\Pages;

use App\Filament\Resources\DocumentSubcategories\DocumentSubcategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentSubcategory extends ViewRecord
{
    protected static string $resource = DocumentSubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
