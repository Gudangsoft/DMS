<?php

namespace App\Models;

use Database\Factories\DocumentSubcategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['document_category_id', 'code', 'name', 'description', 'sort_order', 'is_active'])]
class DocumentSubcategory extends Model
{
    /** @use HasFactory<DocumentSubcategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
