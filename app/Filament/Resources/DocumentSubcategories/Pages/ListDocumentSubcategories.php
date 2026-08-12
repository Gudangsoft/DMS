<?php

namespace App\Filament\Resources\DocumentSubcategories\Pages;

use App\Filament\Resources\DocumentSubcategories\DocumentSubcategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentSubcategories extends ListRecords
{
    protected static string $resource = DocumentSubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
