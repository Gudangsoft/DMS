<?php

namespace App\Filament\Resources\DocumentApprovals;

use App\Filament\Resources\DocumentApprovals\Pages\ListDocumentApprovals;
use App\Filament\Resources\DocumentApprovals\Tables\DocumentApprovalsTable;
use App\Models\DocumentApproval;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Poin 21 — grup menu APPROVAL (Menunggu Review, Riwayat Approval). Tidak ada
 * create/edit: setiap DocumentApproval hanya dibuat oleh DocumentApprovalService
 * saat submit, dan diputuskan lewat action approve/reject/request-revision di
 * tabel ini (lihat DocumentApprovalsTable).
 */
class DocumentApprovalResource extends Resource
{
    protected static ?string $model = DocumentApproval::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Approval';

    protected static ?string $navigationLabel = 'Menunggu Review';

    protected static ?string $modelLabel = 'Approval';

    public static function table(Table $table): Table
    {
        return DocumentApprovalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentApprovals::route('/'),
        ];
    }
}
