<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DocumentStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Revision = 'revision';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Published = 'published';
    case Superseded = 'superseded';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Revision => 'Revision',
            self::Rejected => 'Rejected',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Superseded => 'Superseded',
            self::Archived => 'Archived',
        };
    }

    /**
     * @return string|array<int, string>
     */
    public function getColor(): string|array
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => Color::Blue,
            self::UnderReview => 'warning',
            self::Revision => Color::Orange,
            self::Rejected => 'danger',
            self::Approved, self::Published => 'success',
            self::Superseded, self::Archived => 'gray',
        };
    }

    /**
     * Whether the owner/admin is still allowed to edit the file & metadata directly.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Revision], true);
    }

    /**
     * Statuses considered "final" for reporting/archive purposes.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Superseded, self::Archived], true);
    }

    /**
     * Whether the document has gone through the approval workflow at least once, so
     * confidentiality-level rules (rather than "owner/reviewer only") govern who can
     * view it. Archived/superseded documents remain reachable by direct link for
     * audit purposes even though they are excluded from normal search listings.
     */
    public function isPublicFacing(): bool
    {
        return in_array($this, [self::Published, self::Superseded, self::Archived], true);
    }
}
