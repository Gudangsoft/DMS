<?php

namespace App\Filament\Resources\DocumentSubcategories\Pages;

use App\Filament\Resources\DocumentSubcategories\DocumentSubcategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentSubcategory extends EditRecord
{
    protected static string $resource = DocumentSubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
