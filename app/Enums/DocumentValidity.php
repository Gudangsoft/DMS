<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Whether a document is still the one in force — separate from the approval
 * workflow status. A published Statuta 2022 stays published (still viewable,
 * for audit/accreditation history) but is marked "Digantikan" once Statuta
 * 2026 takes over.
 */
enum DocumentValidity: string implements HasColor, HasLabel
{
    case Berlaku = 'berlaku';
    case Digantikan = 'digantikan';
    case Dicabut = 'dicabut';

    public function getLabel(): string
    {
        return match ($this) {
            self::Berlaku => 'Berlaku',
            self::Digantikan => 'Digantikan',
            self::Dicabut => 'Dicabut',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Berlaku => 'success',
            self::Digantikan => 'warning',
            self::Dicabut => 'danger',
        };
    }

    /** Tailwind classes for the public site badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Berlaku => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Digantikan => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Dicabut => 'bg-red-50 text-red-700 ring-red-600/20',
        };
    }
}
