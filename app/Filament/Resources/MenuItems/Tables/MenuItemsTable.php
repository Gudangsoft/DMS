<?php

namespace App\Filament\Resources\MenuItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description('Seret ikon di sebelah kiri baris untuk mengubah urutan tampil di menu.')
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record->parent ? '— '.$record->label : $record->label)
                    ->weight(fn ($record) => $record->parent ? null : 'semibold'),
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(40),
                TextColumn::make('parent.label')
                    ->label('Induk')
                    ->placeholder('— (menu utama)'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            // Parent immediately followed by its own children, instead of every
            // parent first and every child afterwards — makes each dropdown
            // group visually obvious while dragging to reorder.
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw('COALESCE(parent_id, id) asc')
                ->orderByRaw('(parent_id is not null) asc')
                ->orderBy('sort_order'))
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
