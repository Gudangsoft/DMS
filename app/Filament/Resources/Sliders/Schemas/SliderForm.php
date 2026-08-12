<?php

namespace App\Filament\Resources\Sliders\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SliderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->label('Judul (opsional)')
                            ->maxLength(255)
                            ->helperText('Kosongkan untuk slide gambar saja tanpa teks.')
                            ->columnSpanFull(),
                        TextInput::make('subtitle')
                            ->label('Subjudul (opsional)')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Gambar Latar')
                            ->image()
                            ->disk('public')
                            ->directory('sliders')
                            ->imageEditor()
                            ->imageEditorAspectRatios(['16:9', '21:9'])
                            ->helperText('Opsional — tanpa gambar akan memakai gradient navy/gold bawaan.')
                            ->columnSpanFull(),
                        TextInput::make('button_text')
                            ->label('Teks Tombol'),
                        TextInput::make('button_url')
                            ->label('URL Tombol')
                            ->helperText('Contoh: /documents atau https://...'),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
