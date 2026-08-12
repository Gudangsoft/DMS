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
            // Plain sort_order ascending — NOT clustered by parent/id. Filament's
            // drag-to-reorder assigns new sort_order values based on final visual
            // row position, then the table re-renders using whatever ordering this
            // query defines. A COALESCE(parent_id, id)-based clustering used to sit
            // here to group each parent with its children, but for top-level items
            // COALESCE(parent_id, id) === id, so drags between top-level items had
            // zero visible effect after the drop (id order always won over the
            // freshly-written sort_order) — reordering looked completely broken.
            ->defaultSort('sort_order')
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
