<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        // Filament defaults the *root* schema to 2 columns whenever it isn't
        // set explicitly (EditRecord/CreateRecord::form() — `$schema->hasCustomColumns()
        // ? $schema : $schema->columns(2)`), reserving a second column for the
        // page. This form only ever puts one Section in that root schema, so
        // without this the second column just renders empty — invisible at the
        // panel's default width, but an obvious dead rectangle once the page
        // is widened (see EditPage/CreatePage's maxContentWidth).
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create'
                                ? $set('slug', Str::slug($state))
                                : null)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Dipakai di URL: /page/{slug}. Tambahkan link ke menu navigasi lewat menu "Menu Navigasi".')
                            ->columnSpanFull(),
                        Textarea::make('excerpt')
                            ->label('Ringkasan')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        RichEditor::make('content')
                            ->label('Isi Konten')
                            ->columnSpanFull(),
                        FileUpload::make('featured_image')
                            ->label('Gambar Sampul')
                            ->image()
                            ->disk('public')
                            ->directory('pages'),
                        DateTimePicker::make('published_at')
                            ->label('Tanggal Publikasi')
                            ->default(now()),
                        Toggle::make('is_published')
                            ->label('Publikasikan')
                            ->default(true),
                    ]),
            ]);
    }
}
