<?php

namespace App\Models;

use Database\Factories\DocumentDownloadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'user_id', 'ip_address', 'user_agent', 'downloaded_at'])]
class DocumentDownload extends Model
{
    /** @use HasFactory<DocumentDownloadFactory> */
    use HasFactory;

    /**
     * This table only tracks a single `downloaded_at` moment — no created_at/updated_at columns.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
