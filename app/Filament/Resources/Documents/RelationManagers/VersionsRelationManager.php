<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Poin 11 — versions are append-only: no create/edit/delete here, only
 * View/Download per version (poin 38 "View Version", "Download Version").
 */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Riwayat Versi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')
                    ->label('Versi')
                    ->badge(),
                TextColumn::make('file_name')
                    ->label('Nama File')
                    ->wrap(),
                TextColumn::make('file_size')
                    ->label('Ukuran')
                    ->formatStateUsing(fn ($record) => $record->humanFileSize()),
                TextColumn::make('checksum')
                    ->label('Checksum (SHA-256)')
                    ->limit(16)
                    ->fontFamily('mono')
                    ->copyable()
                    ->placeholder('Tautan eksternal — tidak terverifikasi')
                    ->toggleable(),
                TextColumn::make('uploader.name')
                    ->label('Diunggah Oleh'),
                TextColumn::make('change_notes')
                    ->label('Catatan')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn ($record) => route('documents.download.version', [$this->getOwnerRecord()->uuid, $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }
}
