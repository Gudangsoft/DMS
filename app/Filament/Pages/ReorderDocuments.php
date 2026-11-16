<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * Drag & drop ordering of documents within one menu (a subcategory, or a
 * category's documents that have no subcategory). Saving writes 1..n into
 * documents.sort_order — the same "Urutan" the public listing pins by.
 */
class ReorderDocuments extends Page
{
    protected string $view = 'filament.pages.reorder-documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Document Management';

    protected static ?string $navigationLabel = 'Atur Urutan Dokumen';

    protected static ?string $title = 'Atur Urutan Dokumen';

    protected static ?int $navigationSort = 5;

    /** "sub:{id}" or "cat:{id}" (documents of the category without a subcategory). */
    #[Url]
    public ?string $menu = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('master_data.manage') || auth()->user()?->can('documents.publish');
    }

    /** @return array<string, array<string, string>> grouped options for the menu picker */
    public function getMenuOptions(): array
    {
        $options = [];

        $categories = DocumentCategory::orderBy('sort_order')
            ->with(['subcategories' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        foreach ($categories as $category) {
            $group = [];
            foreach ($category->subcategories as $sub) {
                $group["sub:{$sub->id}"] = $sub->name;
            }
            $group["cat:{$category->id}"] = $category->subcategories->isEmpty()
                ? "Semua dokumen {$category->name}"
                : "(Tanpa subkategori)";
            $options[$category->name] = $group;
        }

        return $options;
    }

    /** @return Collection<int, Document> */
    public function getDocuments(): Collection
    {
        if (! $this->menu || ! str_contains($this->menu, ':')) {
            return collect();
        }

        [$type, $id] = explode(':', $this->menu, 2);

        return Document::query()
            ->when($type === 'sub',
                fn ($q) => $q->where('document_subcategory_id', (int) $id),
                fn ($q) => $q->where('document_category_id', (int) $id)->whereNull('document_subcategory_id'))
            ->newestFirst()
            ->get(['id', 'title', 'year', 'effective_date', 'sort_order', 'status', 'validity', 'published_at']);
    }

    /** @param  array<int, string|int>  $orderedIds */
    public function saveOrder(array $orderedIds): void
    {
        $allowed = $this->getDocuments()->modelKeys();
        $orderedIds = array_values(array_filter(array_map('intval', $orderedIds), fn ($id) => in_array($id, $allowed, true)));

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Document::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        Notification::make()->title('Urutan disimpan')->success()->send();
    }

    public function resetOrder(): void
    {
        Document::whereKey($this->getDocuments()->modelKeys())->update(['sort_order' => null]);

        Notification::make()
            ->title('Urutan dikembalikan ke otomatis')
            ->body('Tahun & Tanggal Berlaku terbaru di atas.')
            ->success()
            ->send();
    }

    public function getSelectedMenuLabel(): ?string
    {
        foreach ($this->getMenuOptions() as $category => $group) {
            if (isset($group[$this->menu])) {
                return "{$category} › {$group[$this->menu]}";
            }
        }

        return null;
    }
}
