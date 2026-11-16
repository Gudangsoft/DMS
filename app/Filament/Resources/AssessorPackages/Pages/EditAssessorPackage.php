<?php

namespace App\Filament\Resources\AssessorPackages\Pages;

use App\Filament\Resources\AssessorPackages\AssessorPackageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssessorPackage extends EditRecord
{
    protected static string $resource = AssessorPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Buka Halaman Asesor')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->record->publicUrl())
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
