<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Poin 19 — Tampilkan: User, Aktivitas, Dokumen, IP Address, Tanggal, Waktu.
 */
class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System'),
                TextColumn::make('description')
                    ->label('Aktivitas')
                    ->badge(),
                TextColumn::make('subject_type')
                    ->label('Objek')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                TextColumn::make('subject_id')
                    ->label('Dokumen')
                    ->formatStateUsing(function ($record) {
                        if ($record->subject_type !== \App\Models\Document::class || ! $record->subject) {
                            return '-';
                        }

                        return $record->subject->title;
                    }),
                TextColumn::make('properties.ip_address')
                    ->label('IP Address')
                    ->default('-'),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->time('H:i:s'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Modul')
                    ->options([
                        'document' => 'Document',
                        'approval' => 'Approval',
                        'auth' => 'Auth',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
