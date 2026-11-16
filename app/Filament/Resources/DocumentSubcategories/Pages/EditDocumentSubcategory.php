<?php

namespace App\Filament\Resources\DocumentSubcategories\Pages;

use App\Filament\Resources\DocumentSubcategories\DocumentSubcategoryResource;
use App\Filament\Support\GuardedDeleteActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentSubcategory extends EditRecord
{
    protected static string $resource = DocumentSubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            GuardedDeleteActions::delete(),
            GuardedDeleteActions::restore(),
            GuardedDeleteActions::forceDelete(),
        ];
    }
}
