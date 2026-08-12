<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Filament's default is to stay on the edit form after saving — this
    // resource has no "view" page to fall back to, so go straight back to
    // the list instead (matches how admins actually use this: pick an item,
    // toggle/edit it, and expect to land back among the rest).
    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl('index');
    }
}
