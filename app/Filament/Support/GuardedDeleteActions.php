<?php

namespace App\Filament\Support;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Delete / restore / force-delete actions for categories & subcategories.
 *
 * Deleting moves the record to the recycle bin (soft delete) and is refused
 * — with a clear message, not a 500 — while the record still has documents
 * or subcategories (see GuardsAgainstDeletingNonEmpty). This check runs for
 * every role, Super Admin included.
 */
class GuardedDeleteActions
{
    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->label('Hapus (ke Tong Sampah)')
            ->modalDescription('Data dipindahkan ke Tong Sampah dan masih bisa dipulihkan.')
            ->before(function (DeleteAction $action, Model $record) {
                if ($reason = $record->deletionBlocker()) {
                    static::refuse($reason);
                    $action->cancel();
                }
            });
    }

    public static function restore(): RestoreAction
    {
        return RestoreAction::make()->label('Pulihkan');
    }

    public static function forceDelete(): ForceDeleteAction
    {
        return ForceDeleteAction::make()
            ->label('Hapus Permanen')
            ->before(function (ForceDeleteAction $action, Model $record) {
                if ($reason = $record->deletionBlocker()) {
                    static::refuse($reason);
                    $action->cancel();
                }
            });
    }

    public static function bulk(): BulkActionGroup
    {
        return BulkActionGroup::make([
            DeleteBulkAction::make()
                ->label('Hapus (ke Tong Sampah)')
                ->fetchSelectedRecords()
                ->before(function (DeleteBulkAction $action, Collection $records) {
                    $reasons = $records->map(fn (Model $r) => $r->deletionBlocker())->filter();
                    if ($reasons->isNotEmpty()) {
                        static::refuse($reasons->implode("\n"));
                        $action->cancel();
                    }
                }),
            RestoreBulkAction::make()->label('Pulihkan'),
            ForceDeleteBulkAction::make()
                ->label('Hapus Permanen')
                ->fetchSelectedRecords()
                ->before(function (ForceDeleteBulkAction $action, Collection $records) {
                    $reasons = $records->map(fn (Model $r) => $r->deletionBlocker())->filter();
                    if ($reasons->isNotEmpty()) {
                        static::refuse($reasons->implode("\n"));
                        $action->cancel();
                    }
                }),
        ]);
    }

    protected static function refuse(string $reason): void
    {
        Notification::make()
            ->danger()
            ->title('Tidak bisa dihapus')
            ->body($reason)
            ->persistent()
            ->send();
    }
}
