<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Poin 3 — "Recent Documents": Nomor Dokumen, Nama Dokumen, Kategori, Unit,
 * Tanggal, Status.
 */
class RecentDocumentsTable extends TableWidget
{
    protected static ?string $heading = 'Dokumen Terbaru';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Document::query()->latest()->limit(10))
            ->columns([
                TextColumn::make('document_number')->label('Nomor')->placeholder('-'),
                TextColumn::make('title')->label('Nama Dokumen')->wrap()->limit(50),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('unit.name')->label('Unit'),
                TextColumn::make('created_at')->label('Tanggal')->date('d M Y'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.documents.view', $record)),
            ])
            ->paginated(false);
    }
}
