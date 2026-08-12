<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    // Wider than the panel default — the RichEditor content field is much
    // more comfortable to write in with more horizontal room.
    protected Width|string|null $maxContentWidth = Width::Full;
}
