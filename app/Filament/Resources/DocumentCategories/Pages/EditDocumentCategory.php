<?php

namespace App\Filament\Resources\DocumentCategories\Pages;

use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use App\Filament\Support\GuardedDeleteActions;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentCategory extends EditRecord
{
    protected static string $resource = DocumentCategoryResource::class;

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
