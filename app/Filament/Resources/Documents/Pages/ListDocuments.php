<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Poin 21 sidebar tabs: Semua Dokumen, Dokumen Saya, Draft, Pending Approval,
 * Approved, Published, Rejected. (Tab Archived dihapus — dokumen terhapus
 * tetap bisa dilihat lewat filter "Trashed" di tabel.)
 */
class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Dokumen'),
            'mine' => Tab::make('Dokumen Saya')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('owner_id', auth()->id())),
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            'pending' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'under_review')),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
        ];
    }
}
