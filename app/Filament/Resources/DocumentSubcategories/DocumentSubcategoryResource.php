<?php

namespace App\Filament\Resources\DocumentSubcategories;

use App\Filament\Resources\DocumentSubcategories\Pages\CreateDocumentSubcategory;
use App\Filament\Resources\DocumentSubcategories\Pages\EditDocumentSubcategory;
use App\Filament\Resources\DocumentSubcategories\Pages\ListDocumentSubcategories;
use App\Filament\Resources\DocumentSubcategories\Pages\ViewDocumentSubcategory;
use App\Filament\Resources\DocumentSubcategories\Schemas\DocumentSubcategoryForm;
use App\Filament\Resources\DocumentSubcategories\Schemas\DocumentSubcategoryInfolist;
use App\Filament\Resources\DocumentSubcategories\Tables\DocumentSubcategoriesTable;
use App\Models\DocumentSubcategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DocumentSubcategoryResource extends Resource
{
    protected static ?string $model = DocumentSubcategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Subkategori Dokumen';

    protected static ?string $modelLabel = 'Subkategori Dokumen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DocumentSubcategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentSubcategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentSubcategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentSubcategories::route('/'),
            'create' => CreateDocumentSubcategory::route('/create'),
            'view' => ViewDocumentSubcategory::route('/{record}'),
            'edit' => EditDocumentSubcategory::route('/{record}/edit'),
        ];
    }
}
