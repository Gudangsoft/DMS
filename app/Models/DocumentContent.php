<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Text extracted from a document's latest file (PDF/DOCX), used only for
 * "search inside the document". Kept in its own table so normal listing
 * queries never load it.
 */
#[Fillable(['document_id', 'document_version_id', 'content', 'extracted_at'])]
class DocumentContent extends Model
{
    protected function casts(): array
    {
        return [
            'extracted_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
