<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListMenuItems extends ListRecords
{
    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Splitting Header/Footer into tabs (instead of a single flat table with a
     * filter dropdown) means drag-reordering only ever touches items from one
     * location at a time — no more dragging a footer link in between header
     * items by accident.
     */
    public function getTabs(): array
    {
        return [
            'header' => Tab::make('Header (Navbar)')
                ->modifyQueryUsing(fn ($query) => $query->where('location', 'header')),
            'footer' => Tab::make('Footer')
                ->modifyQueryUsing(fn ($query) => $query->where('location', 'footer')),
        ];
    }
}
