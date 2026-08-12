<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\MenuItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->components([
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('url')
                            ->label('URL Tujuan')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Path internal (mis. /documents, /page/tentang-dms) atau URL penuh (https://...)'),
                        Select::make('location')
                            ->label('Lokasi')
                            ->options(['header' => 'Header (Navbar)', 'footer' => 'Footer'])
                            ->default('header')
                            ->required()
                            ->live(),
                        Select::make('parent_id')
                            ->label('Induk Menu (opsional)')
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get, ?MenuItem $record) => MenuItem::query()
                                ->whereNull('parent_id')
                                ->where('location', $get('location'))
                                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                ->pluck('label', 'id'))
                            ->searchable()
                            ->helperText('Kosongkan untuk menu tingkat atas. Diisi untuk membuat submenu dropdown.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('open_in_new_tab')
                            ->label('Buka di Tab Baru'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
