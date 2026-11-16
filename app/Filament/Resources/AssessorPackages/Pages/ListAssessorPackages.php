<?php

namespace App\Filament\Resources\AssessorPackages\Pages;

use App\Filament\Resources\AssessorPackages\AssessorPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessorPackages extends ListRecords
{
    protected static string $resource = AssessorPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Paket'),
        ];
    }
}
