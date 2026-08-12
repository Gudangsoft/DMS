<?php

namespace App\Filament\Resources\DocumentApprovals\Pages;

use App\Filament\Resources\DocumentApprovals\DocumentApprovalResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDocumentApprovals extends ListRecords
{
    protected static string $resource = DocumentApprovalResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Menunggu Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'history' => Tab::make('Riwayat Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['approved', 'revision', 'rejected'])),
        ];
    }
}
