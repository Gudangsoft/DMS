<?php

namespace App\Filament\Resources\AssessorPackages;

use App\Filament\Resources\AssessorPackages\Pages\CreateAssessorPackage;
use App\Filament\Resources\AssessorPackages\Pages\EditAssessorPackage;
use App\Filament\Resources\AssessorPackages\Pages\ListAssessorPackages;
use App\Models\AssessorPackage;
use App\Models\Document;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Paket Dokumen Asesor — a hand-picked set of documents shared with external
 * assessors via a secret link that can expire. No account needed on their side.
 */
class AssessorPackageResource extends Resource
{
    protected static ?string $model = AssessorPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Document Management';

    protected static ?string $navigationLabel = 'Paket Asesor';

    protected static ?string $modelLabel = 'Paket Asesor';

    protected static ?string $pluralModelLabel = 'Paket Asesor';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Paket')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->label('Nama Paket')
                        ->placeholder('mis. Akreditasi Prodi Manajemen 2026')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label('Keterangan untuk Asesor')
                        ->rows(3)
                        ->columnSpanFull(),
                    DateTimePicker::make('expires_at')
                        ->label('Berlaku sampai')
                        ->native(false)
                        ->default(fn () => now()->addDays(30)->endOfDay())
                        ->helperText('Setelah tanggal ini tautan otomatis tidak bisa dibuka. Kosongkan = tanpa batas.'),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Matikan untuk menutup akses seketika.'),
                ]),
            Section::make('Dokumen')
                ->description('Semua dokumen di paket ini bisa dibuka siapa pun yang memegang tautannya, apa pun tingkat aksesnya.')
                ->components([
                    Select::make('documents')
                        ->label('Dokumen dalam paket')
                        ->relationship('documents', 'title')
                        ->getOptionLabelFromRecordUsing(fn (Document $record) => "{$record->title} ({$record->year})")
                        ->multiple()
                        ->searchable()
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Paket')->searchable()->wrap(),
                TextColumn::make('documents_count')->counts('documents')->label('Dokumen')->badge(),
                TextColumn::make('expires_at')->label('Berlaku sampai')->dateTime('d M Y H:i')->placeholder('Tanpa batas')->sortable(),
                TextColumn::make('access')
                    ->label('Status')
                    ->badge()
                    ->state(fn (AssessorPackage $record) => match (true) {
                        ! $record->is_active => 'Nonaktif',
                        ! $record->isAccessible() => 'Kedaluwarsa',
                        default => 'Aktif',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Aktif' => 'success',
                        'Kedaluwarsa' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('link')
                    ->label('Tautan (klik untuk salin)')
                    ->state(fn (AssessorPackage $record) => $record->publicUrl())
                    ->limit(40)
                    ->copyable()
                    ->copyMessage('Tautan disalin')
                    ->icon('heroicon-m-clipboard-document'),
                TextColumn::make('view_count')->label('Dibuka')->suffix('×')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (AssessorPackage $record) => $record->publicUrl())
                    ->openUrlInNewTab(),
                EditAction::make(),
                Action::make('regenerate')
                    ->label('Ganti Tautan')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Tautan lama langsung tidak berlaku. Bagikan tautan baru ke asesor.')
                    ->action(function (AssessorPackage $record) {
                        $record->regenerateToken();
                        Notification::make()->title('Tautan baru dibuat')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessorPackages::route('/'),
            'create' => CreateAssessorPackage::route('/create'),
            'edit' => EditAssessorPackage::route('/{record}/edit'),
        ];
    }
}
