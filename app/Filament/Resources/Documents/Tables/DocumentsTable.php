<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\ConfidentialityLevel;
use App\Enums\DocumentStatus;
use App\Models\DocumentCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\DocumentApprovalService;
use App\Services\DocumentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
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
                EditAction::make()
                    ->visible(fn ($record) => $record->status->isEditable() && auth()->user()->can('update', $record)),

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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
