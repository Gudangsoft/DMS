<?php

namespace App\Observers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Support\Str;

class DocumentObserver
{
    /**
     * Handle the Document "creating" event.
     */
    public function creating(Document $document): void
    {
        $document->uuid ??= (string) Str::uuid();
        $document->slug ??= $this->generateUniqueSlug($document->title);
        // Falls back to the document owner when created outside an authenticated
        // request (e.g. console commands, seeders, tests) — created_by is NOT NULL.
        $document->created_by ??= auth()->id() ?? $document->owner_id;

        // Set explicitly rather than relying on the migration's ->default(): MySQL
        // does not return computed defaults on INSERT, so the in-memory model would
        // otherwise hold null for these until the record is freshly reloaded.
        $document->status ??= DocumentStatus::Draft;
        $document->current_version ??= '1.0';
        $document->download_count ??= 0;
        $document->is_public ??= false;
    }

    /**
     * Handle the Document "updating" event.
     */
    public function updating(Document $document): void
    {
        $document->updated_by = auth()->id();
    }

    protected function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 1;

        while (Document::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
