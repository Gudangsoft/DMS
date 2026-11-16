<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstDeletingNonEmpty;
use Database\Factories\DocumentCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'description', 'icon', 'cover_image', 'sort_order', 'is_active'])]
class DocumentCategory extends Model
{
    /** @use HasFactory<DocumentCategoryFactory> */
    use GuardsAgainstDeletingNonEmpty, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(DocumentSubcategory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function deletionBlocker(): ?string
    {
        $subcategories = $this->subcategories()->count();
        $documents = $this->documents()->withTrashed()->count();

        if ($subcategories === 0 && $documents === 0) {
            return null;
        }

        $parts = array_filter([
            $subcategories ? "{$subcategories} subkategori" : null,
            $documents ? "{$documents} dokumen" : null,
        ]);

        return "Kategori \"{$this->name}\" masih berisi ".implode(' dan ', $parts)
            .'. Pindahkan atau hapus isinya terlebih dahulu.';
    }
}
