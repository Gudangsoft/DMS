<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\ConfidentialityLevel;
use App\Enums\DocumentStatus;
use App\Enums\DocumentValidity;
use App\Models\Document;
use App\Models\DocumentSubcategory;
use App\Models\DocumentCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\DocumentApprovalService;
use App\Services\DocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_code')
                    ->label('Kode')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('document_number')
                    ->label('Nomor')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->wrap()
                    ->limit(60),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->placeholder('–')
                    ->tooltip('Urutan tampil di web (1 = paling atas). Ubah lewat form Ubah dokumen.')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('validity')
                    ->label('Keberlakuan')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('current_version')
                    ->label('Versi'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(DocumentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])),
                SelectFilter::make('document_category_id')
                    ->label('Kategori')
                    ->options(fn () => DocumentCategory::pluck('name', 'id')),
                SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->options(fn () => Unit::pluck('name', 'id')),
                SelectFilter::make('validity')
                    ->label('Keberlakuan')
                    ->options(DocumentValidity::class),
                SelectFilter::make('document_subcategory_id')
                    ->label('Subkategori')
                    ->searchable()
                    ->options(fn () => DocumentSubcategory::with('category')->orderBy('document_category_id')->orderBy('sort_order')->get()
                        ->mapWithKeys(fn ($s) => [$s->id => "{$s->category?->name} › {$s->name}"])),
                SelectFilter::make('confidentiality_level')
                    ->label('Tingkat Akses')
                    ->options(collect(ConfidentialityLevel::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                // Gate::before gives Super Admin a blanket `true` on every ability check
                // (see AppServiceProvider), which bypasses the status checks each
                // DocumentPolicy method encodes (e.g. approve() requires under_review).
                // Without also checking $record->status here, Super Admin would see
                // every workflow button on every document regardless of state, and
                // clicking one out of order would hit the service layer's own guard
                // and throw instead of doing anything — status checks are duplicated
                // here so the buttons shown are always ones that will actually work.
                // Edit is the exception: DocumentPolicy::update() itself lets admins
                // edit at any status, and the service layer honours the same rule.
                EditAction::make()
                    ->visible(fn ($record) => auth()->user()->can('update', $record)),

                // Workflow actions live in a "⋮" dropdown so View/Edit stay visible
                // at the row's edge instead of being pushed off-screen by up to
                // eight inline buttons.
                ActionGroup::make([
                    Action::make('submit')
                        ->label('Submit')
                        ->icon('heroicon-m-paper-airplane')
                        ->color('info')
                        ->visible(fn ($record) => in_array($record->status, [DocumentStatus::Draft, DocumentStatus::Revision], true)
                            && auth()->user()->can('submit', $record))
                        ->requiresConfirmation()
                        ->schema([
                            Select::make('reviewer_id')
                                ->label('Reviewer')
                                ->options(fn () => User::role(\App\Enums\UserRole::Reviewer->value)->pluck('name', 'id'))
                                ->helperText('Kosongkan untuk penugasan otomatis berdasarkan unit.')
                                ->searchable(),
                        ])
                        ->action(function ($record, array $data) {
                            app(DocumentApprovalService::class)->submitForReview($record, auth()->user(), $data['reviewer_id'] ?? null);
                            Notification::make()->title('Dokumen disubmit untuk review')->success()->send();
                        }),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === DocumentStatus::UnderReview && auth()->user()->can('approve', $record))
                        ->requiresConfirmation()
                        ->schema([
                            Textarea::make('comments')->label('Komentar (opsional)'),
                        ])
                        ->action(function ($record, array $data) {
                            $approval = $record->approvals()->where('status', 'pending')->latest()->first();
                            abort_if(! $approval, 404);
                            app(DocumentApprovalService::class)->approve($approval, auth()->user(), $data['comments'] ?? null);
                            Notification::make()->title('Dokumen disetujui')->success()->send();
                        }),

                    Action::make('request_revision')
                        ->label('Minta Revisi')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === DocumentStatus::UnderReview && auth()->user()->can('requestRevision', $record))
                        ->schema([
                            Textarea::make('comments')->label('Catatan Revisi')->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $approval = $record->approvals()->where('status', 'pending')->latest()->first();
                            abort_if(! $approval, 404);
                            app(DocumentApprovalService::class)->requestRevision($approval, auth()->user(), $data['comments']);
                            Notification::make()->title('Revisi diminta')->warning()->send();
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === DocumentStatus::UnderReview && auth()->user()->can('reject', $record))
                        ->requiresConfirmation()
                        ->schema([
                            Textarea::make('comments')->label('Alasan Penolakan')->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $approval = $record->approvals()->where('status', 'pending')->latest()->first();
                            abort_if(! $approval, 404);
                            app(DocumentApprovalService::class)->reject($approval, auth()->user(), $data['comments']);
                            Notification::make()->title('Dokumen ditolak')->danger()->send();
                        }),

                    Action::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-m-globe-alt')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === DocumentStatus::Approved && auth()->user()->can('publish', $record))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            app(DocumentService::class)->publish($record);
                            Notification::make()->title('Dokumen dipublikasikan')->success()->send();
                        }),

                    Action::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-m-archive-box')
                        ->color('gray')
                        ->visible(fn ($record) => auth()->user()->can('archive', $record))
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            app(DocumentService::class)->archive($record);
                            Notification::make()->title('Dokumen diarsipkan')->success()->send();
                        }),

                    Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('gray')
                        ->visible(fn ($record) => auth()->user()->can('download', $record))
                        ->url(fn ($record) => route('documents.download', $record->uuid))
                        ->openUrlInNewTab(),

                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    static::bulkEdit('bulk_move', 'Pindahkan Kategori', 'heroicon-m-folder-arrow-down', [
                        Select::make('document_category_id')
                            ->label('Kategori')
                            ->options(fn () => DocumentCategory::orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('document_subcategory_id', null)),
                        Select::make('document_subcategory_id')
                            ->label('Subkategori')
                            ->options(fn (Get $get) => DocumentSubcategory::where('document_category_id', $get('document_category_id'))
                                ->orderBy('sort_order')->pluck('name', 'id'))
                            ->placeholder('Tanpa subkategori'),
                    ]),
                    static::bulkEdit('bulk_year', 'Ubah Tahun', 'heroicon-m-calendar', [
                        TextInput::make('year')->label('Tahun')->numeric()->integer()->minValue(1900)->maxValue(2100)->required(),
                    ]),
                    static::bulkEdit('bulk_validity', 'Ubah Keberlakuan', 'heroicon-m-check-badge', [
                        Select::make('validity')->label('Status Keberlakuan')->options(DocumentValidity::class)->required(),
                    ]),
                    static::bulkEdit('bulk_access', 'Ubah Tingkat Akses', 'heroicon-m-lock-closed', [
                        Select::make('confidentiality_level')
                            ->label('Tingkat Akses')
                            ->options(collect(ConfidentialityLevel::cases())->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()]))
                            ->required(),
                    ]),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Poin "edit massal": apply the same field values to every selected
     * document the user is allowed to update; others are skipped and counted.
     *
     * @param  array<int, \Filament\Schemas\Components\Component>  $schema
     */
    protected static function bulkEdit(string $name, string $label, string $icon, array $schema): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->schema($schema)
            ->modalSubmitActionLabel('Terapkan')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data) {
                $updated = 0;
                $skipped = 0;

                foreach ($records as $record) {
                    /** @var Document $record */
                    if (! auth()->user()->can('update', $record)) {
                        $skipped++;

                        continue;
                    }

                    $record->update($data);
                    $updated++;
                }

                Notification::make()
                    ->title("{$updated} dokumen diperbarui")
                    ->body($skipped ? "{$skipped} dokumen dilewati karena Anda tidak berhak mengubahnya." : null)
                    ->success()
                    ->send();
            });
    }
}
