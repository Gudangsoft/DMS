<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A curated set of documents shared with external assessors (asesor
 * akreditasi) through a secret, optionally expiring link — no account needed.
 * Sharing is explicit: every document in the package is readable through the
 * link regardless of its confidentiality level, so only staff allowed to
 * publish documents may build packages (see AssessorPackagePolicy).
 */
#[Fillable(['name', 'description', 'expires_at', 'is_active'])]
class AssessorPackage extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'view_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $package) {
            $package->token ??= Str::random(40);
            $package->created_by ??= auth()->id();
        });
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'assessor_package_document')
            ->withPivot('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAccessible(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function publicUrl(): string
    {
        return route('assessor-packages.show', $this->token);
    }

    public function regenerateToken(): void
    {
        $this->forceFill(['token' => Str::random(40)])->save();
    }
}
