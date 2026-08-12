<?php

namespace App\Filament\Resources\DocumentApprovals\Tables;

use App\Services\DocumentApprovalService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentApprovalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document.title')
                    ->label('Dokumen')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('document.category.name')
                    ->label('Kategori'),
                TextColumn::make('document.unit.name')
                    ->label('Unit'),
                TextColumn::make('reviewer.name')
                    ->label('Reviewer'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('comments')
                    ->label('Komentar')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('Ditinjau')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.documents.view', $record->document)),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('comments')->label('Komentar (opsional)')])
                    ->action(function ($record, array $data) {
                        app(DocumentApprovalService::class)->approve($record, auth()->user(), $data['comments'] ?? null);
                        Notification::make()->title('Dokumen disetujui')->success()->send();
                    }),

                Action::make('request_revision')
                    ->label('Minta Revisi')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn ($record) => auth()->user()->can('update', $record))
                    ->schema([Textarea::make('comments')->label('Catatan Revisi')->required()])
                    ->action(function ($record, array $data) {
                        app(DocumentApprovalService::class)->requestRevision($record, auth()->user(), $data['comments']);
                        Notification::make()->title('Revisi diminta')->warning()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('comments')->label('Alasan Penolakan')->required()])
                    ->action(function ($record, array $data) {
                        app(DocumentApprovalService::class)->reject($record, auth()->user(), $data['comments']);
                        Notification::make()->title('Dokumen ditolak')->danger()->send();
                    }),
            ]);
    }
}
