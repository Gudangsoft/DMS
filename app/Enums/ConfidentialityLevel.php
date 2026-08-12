<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ConfidentialityLevel: string implements HasColor, HasLabel
{
    case PublicLevel = 'public';
    case Internal = 'internal';
    case Unit = 'unit';
    case Restricted = 'restricted';
    case Confidential = 'confidential';

    public function getLabel(): string
    {
        return match ($this) {
            self::PublicLevel => 'Public',
            self::Internal => 'Internal',
            self::Unit => 'Unit',
            self::Restricted => 'Restricted',
            self::Confidential => 'Confidential',
        };
    }

    /**
     * @return string|array<int, string>
     */
    public function getColor(): string|array
    {
        return match ($this) {
            self::PublicLevel => 'success',
            self::Internal => 'info',
            self::Unit => 'warning',
            self::Restricted => Color::Orange,
            self::Confidential => 'danger',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PublicLevel => 'Seluruh pengunjung dapat melihat, termasuk yang belum login.',
            self::Internal => 'Hanya user yang sudah login.',
            self::Unit => 'Hanya user dari unit pemilik dokumen.',
            self::Restricted => 'Hanya user yang diberi permission khusus.',
            self::Confidential => 'Hanya Super Admin dan user tertentu.',
        };
    }
}
