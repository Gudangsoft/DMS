<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Poin 13/22 — riwayat approval, read-only (tindakan approve/reject/request
 * revision dilakukan lewat action di tabel DocumentResource utama, bukan di sini).
 */
class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static ?string $title = 'Riwayat Approval';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('reviewer.name')
                    ->label('Reviewer'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('comments')
                    ->label('Komentar')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('reviewed_at')
                    ->label('Ditinjau Pada')
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime(),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
