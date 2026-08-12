<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('causer.name')->label('User')->default('System'),
                TextEntry::make('description')->label('Aktivitas'),
                TextEntry::make('subject_type')->label('Tipe Objek')->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                TextEntry::make('properties.ip_address')->label('IP Address')->default('-'),
                TextEntry::make('created_at')->label('Waktu')->dateTime(),
                TextEntry::make('properties')->label('Detail Perubahan')->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))->columnSpanFull(),
            ]);
    }
}
