<?php

namespace App\Models\Concerns;

use App\Exceptions\NotEmptyException;

/**
 * Refuses to delete (soft or force) a category/subcategory that still holds
 * documents or subcategories. Enforced at model level, so it applies to every
 * caller — including Super Admin, whose Gate::before bypass skipped the policy
 * check that was meant to stop exactly this (a deleted category cascading
 * away its subcategories and leaving their documents orphaned).
 */
trait GuardsAgainstDeletingNonEmpty
{
    /** Human-readable reason this record can't be deleted, or null if it can. */
    abstract public function deletionBlocker(): ?string;

    protected static function bootGuardsAgainstDeletingNonEmpty(): void
    {
        static::deleting(function (self $model) {
            if ($reason = $model->deletionBlocker()) {
                throw new NotEmptyException($reason);
            }
        });
    }
}
