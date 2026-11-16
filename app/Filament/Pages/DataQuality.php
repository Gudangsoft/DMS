<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Services\DataQualityService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

/**
 * "Cek Kualitas Data": lists documents with data problems (wrong year,
 * missing file, expired-but-valid, …) so admins can fix them one by one.
 */
class DataQuality extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.data-quality';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Report';

    protected static ?string $navigationLabel = 'Cek Kualitas Data';

    protected static ?string $title = 'Cek Kualitas Data';

    #[Url]
    public string $check = 'year_mismatch';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('documents.view_any') ?? false;
    }

    public function setCheck(string $check): void
    {
        if (array_key_exists($check, app(DataQualityService::class)->checks())) {
            $this->check = $check;
            $this->resetTable();
        }
    }

    public function getChecks(): array
    {
        return app(DataQualityService::class)->checks();
    }

    public function getCounts(): array
    {
        return app(DataQualityService::class)->counts();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rescan')
                ->label('Periksa ulang file')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(fn () => app(DataQualityService::class)->missingFileDocumentIds(fresh: true)),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => app(DataQualityService::class)->query($this->check)->with(['category', 'subcategory', 'supersededBy']))
            ->columns([
                TextColumn::make('title')->label('Nama Dokumen')->wrap()->limit(70)->searchable(),
                TextColumn::make('year')->label('Tahun')->sortable(),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('subcategory.name')->label('Subkategori')->placeholder('—'),
                TextColumn::make('effective_date')->label('Tgl Berlaku')->date('d M Y')->placeholder('—')
                    ->visible(fn () => in_array($this->check, ['no_effective_date', 'year_mismatch'], true)),
                TextColumn::make('expired_date')->label('Kadaluarsa')->date('d M Y')->color('danger')
                    ->visible(fn () => $this->check === 'expired_still_valid'),
                TextColumn::make('validity')->label('Keberlakuan')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->defaultSort('title')
            ->recordActions([
                Action::make('edit')
                    ->label('Perbaiki')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Document $record) => DocumentResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Tidak ada masalah')
            ->emptyStateDescription('Semua dokumen lolos pemeriksaan ini.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
