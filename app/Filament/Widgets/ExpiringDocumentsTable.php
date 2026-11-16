<?php

namespace App\Filament\Widgets;

use App\Enums\DocumentValidity;
use App\Models\Document;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Documents still marked "Berlaku" whose Tanggal Kadaluarsa has passed or
 * falls within the next 60 days. Hidden when there's nothing to show.
 */
class ExpiringDocumentsTable extends TableWidget
{
    protected static ?string $heading = 'Dokumen Akan / Sudah Kadaluarsa';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public const DAYS_AHEAD = 60;

    public static function canView(): bool
    {
        return static::query()->exists();
    }

    protected static function query(): Builder
    {
        return Document::query()
            ->whereNotNull('expired_date')
            ->whereDate('expired_date', '<=', today()->addDays(self::DAYS_AHEAD))
            ->where('validity', DocumentValidity::Berlaku->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => static::query()->orderBy('expired_date'))
            ->description('Perbarui dokumennya, atau ubah Keberlakuan menjadi "Digantikan"/"Dicabut".')
            ->columns([
                TextColumn::make('title')->label('Nama Dokumen')->wrap()->limit(60),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('owner.name')->label('Pemilik'),
                TextColumn::make('expired_date')
                    ->label('Kadaluarsa')
                    ->date('d M Y')
                    ->color(fn (Document $record) => $record->expired_date->isPast() ? 'danger' : 'warning')
                    ->description(function (Document $record) {
                        $days = (int) today()->diffInDays($record->expired_date, false);

                        return match (true) {
                            $days < 0 => 'lewat '.abs($days).' hari',
                            $days === 0 => 'hari ini',
                            default => "{$days} hari lagi",
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.documents.edit', $record)),
            ])
            ->defaultPaginationPageOption(5);
    }
}
