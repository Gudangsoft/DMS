<?php

namespace App\Filament\Widgets;

use App\Models\DocumentApproval;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Poin 3 — "Pending Approval": dokumen yang menunggu persetujuan.
 */
class PendingApprovalsTable extends TableWidget
{
    protected static ?string $heading = 'Menunggu Approval';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DocumentApproval::query()->where('status', 'pending')->latest())
            ->columns([
                TextColumn::make('document.title')->label('Dokumen')->wrap()->limit(50),
                TextColumn::make('document.unit.name')->label('Unit'),
                TextColumn::make('reviewer.name')->label('Reviewer'),
                TextColumn::make('created_at')->label('Diajukan')->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.documents.view', $record->document)),
            ])
            ->paginated(false);
    }
}
