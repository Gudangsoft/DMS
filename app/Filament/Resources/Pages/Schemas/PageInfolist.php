<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')->label('Judul'),
                TextEntry::make('slug'),
                TextEntry::make('excerpt')->label('Ringkasan')->columnSpanFull(),
                TextEntry::make('content')->label('Isi')->html()->columnSpanFull(),
                IconEntry::make('is_published')->label('Terbit')->boolean(),
                TextEntry::make('published_at')->label('Tanggal Publikasi')->dateTime(),
            ]);
    }
}
