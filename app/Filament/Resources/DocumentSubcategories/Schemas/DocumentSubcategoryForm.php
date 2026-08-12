<?php

namespace App\Filament\Resources\DocumentSubcategories\Schemas;

use App\Models\DocumentCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentSubcategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('document_category_id')
                    ->label('Kategori')
                    ->options(fn () => DocumentCategory::orderBy('sort_order')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('cover_image')
                    ->label('Gambar')
                    ->image()
                    ->disk('public')
                    ->directory('subcategories')
                    ->imageEditor()
                    ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                    ->helperText('Opsional — dipakai sebagai gambar sampul subkategori ini.')
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
