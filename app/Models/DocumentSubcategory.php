<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstDeletingNonEmpty;
use Database\Factories\DocumentSubcategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['document_category_id', 'code', 'name', 'description', 'cover_image', 'sort_order', 'is_active'])]
class DocumentSubcategory extends Model
{
    /** @use HasFactory<DocumentSubcategoryFactory> */
    use GuardsAgainstDeletingNonEmpty, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id')->withTrashed();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function deletionBlocker(): ?string
    {
        $documents = $this->documents()->withTrashed()->count();

        return $documents
            ? "Subkategori \"{$this->name}\" masih berisi {$documents} dokumen. Pindahkan dokumennya terlebih dahulu."
            : null;
    }
}
