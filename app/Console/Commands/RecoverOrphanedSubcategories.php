<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentSubcategory;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recovers documents whose subcategory was deleted out from under them.
 *
 * Deleting a DocumentCategory cascades to its subcategories at the database
 * level, and documents.document_subcategory_id is ON DELETE SET NULL, so the
 * documents themselves survive but silently lose their subcategory (and no
 * Eloquent event / activity log entry is written for that change).
 *
 * The Document activity log does record document_subcategory_id on every
 * create/update, so this command reads each orphaned document's last-known
 * subcategory id from it, groups documents by that (now missing) id, and —
 * with --apply — recreates one new subcategory per group under the target
 * category and moves the documents into it.
 *
 * Without --apply it only prints a report; nothing is written.
 */
class RecoverOrphanedSubcategories extends Command
{
    protected $signature = 'dms:recover-subcategories
        {--category= : Target category ID to recreate the subcategories under (required with --apply)}
        {--name=* : Name for a recreated subcategory, as OLD_ID=Nama (repeatable). Groups without a name are skipped.}
        {--apply : Actually write the changes (default is a read-only report)}';

    protected $description = 'Find documents that lost their subcategory (e.g. after a category was deleted) and restore them into recreated subcategories';

    public function handle(): int
    {
        $existingSubIds = DocumentSubcategory::pluck('id')->flip();
        $existingCatIds = DocumentCategory::pluck('id')->flip();

        // Candidates: no subcategory, a dangling subcategory id, or a dangling category id.
        $candidates = Document::withTrashed()
            ->where(function ($q) use ($existingSubIds, $existingCatIds) {
                $q->whereNull('document_subcategory_id')
                    ->orWhereNotIn('document_subcategory_id', $existingSubIds->keys())
                    ->orWhereNotIn('document_category_id', $existingCatIds->keys());
            })
            ->get();

        $groups = collect(); // old subcategory id => Collection<Document>

        foreach ($candidates as $doc) {
            $oldSubId = $doc->document_subcategory_id ?: $this->lastLoggedSubcategoryId($doc);

            if ($oldSubId && ! $existingSubIds->has($oldSubId)) {
                $groups->put($oldSubId, ($groups->get($oldSubId) ?? collect())->push($doc));
            }
        }

        if ($groups->isEmpty()) {
            $this->info('Tidak ada dokumen yang kehilangan subkategori (berdasarkan activity log).');

            return self::SUCCESS;
        }

        $this->line('Dokumen yang subkategorinya sudah terhapus, dikelompokkan per ID subkategori lama:');
        $this->newLine();

        foreach ($groups->sortKeys() as $oldSubId => $docs) {
            $this->warn("Subkategori lama #{$oldSubId} — {$docs->count()} dokumen");
            $this->table(
                ['Doc ID', 'Judul', 'Kategori sekarang', 'Dihapus (trash)?'],
                $docs->map(fn (Document $d) => [
                    $d->id,
                    Str::limit($d->title, 70),
                    $existingCatIds->has($d->document_category_id)
                        ? $d->document_category_id.' '.DocumentCategory::find($d->document_category_id)?->name
                        : $d->document_category_id.' (TERHAPUS)',
                    $d->trashed() ? 'ya ('.$d->deleted_at.')' : '-',
                ])->all()
            );
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->comment('Mode laporan saja. Untuk memulihkan, jalankan ulang dengan:');
            $this->comment('  --apply --category=<ID kategori tujuan> --name="<ID lama>=<Nama subkategori baru>" ...');

            return self::SUCCESS;
        }

        $category = DocumentCategory::find($this->option('category'));
        if (! $category) {
            $this->error('--category wajib diisi dengan ID kategori yang ada.');

            return self::FAILURE;
        }

        $names = $this->parseNames();

        DB::transaction(function () use ($groups, $names, $category) {
            $nextSort = (int) DocumentSubcategory::where('document_category_id', $category->id)->max('sort_order');

            foreach ($groups->sortKeys() as $oldSubId => $docs) {
                if (! isset($names[$oldSubId])) {
                    $this->line("Lewati grup #{$oldSubId} (tidak ada --name untuknya).");

                    continue;
                }

                $sub = DocumentSubcategory::create([
                    'document_category_id' => $category->id,
                    'code' => $this->uniqueCode($names[$oldSubId]),
                    'name' => $names[$oldSubId],
                    'sort_order' => ++$nextSort,
                    'is_active' => true,
                ]);

                foreach ($docs as $doc) {
                    if ($doc->trashed()) {
                        $doc->restore();
                    }
                    $doc->document_category_id = $category->id;
                    $doc->document_subcategory_id = $sub->id;
                    $doc->save();
                }

                $this->info("Grup #{$oldSubId} → subkategori baru #{$sub->id} \"{$sub->name}\" ({$docs->count()} dokumen).");
            }
        });

        $this->comment('Selesai. Atur urutan (sort_order) subkategori di panel admin bila perlu.');

        return self::SUCCESS;
    }

    protected function lastLoggedSubcategoryId(Document $doc): ?int
    {
        $rows = DB::table('activity_log')
            ->whereIn('subject_type', [Document::class, $doc->getMorphClass()])
            ->where('subject_id', $doc->id)
            ->orderByDesc('id')
            ->pluck('properties');

        foreach ($rows as $json) {
            $props = json_decode((string) $json, true) ?: [];
            $attrs = $props['attributes'] ?? [];
            if (array_key_exists('document_subcategory_id', $attrs)) {
                // The most recent logged value; if it was explicitly null, the
                // document genuinely had no subcategory — nothing to recover.
                return $attrs['document_subcategory_id'] ? (int) $attrs['document_subcategory_id'] : null;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    protected function parseNames(): array
    {
        $names = [];
        foreach ((array) $this->option('name') as $pair) {
            [$id, $name] = array_pad(explode('=', $pair, 2), 2, null);
            if (is_numeric($id) && filled($name)) {
                $names[(int) $id] = trim($name);
            }
        }

        return $names;
    }

    protected function uniqueCode(string $name): string
    {
        $base = Str::upper(Str::limit(Str::slug($name), 40, ''));
        $code = $base ?: 'SUB';
        $i = 2;
        while (DocumentSubcategory::where('code', $code)->exists()) {
            $code = $base.'-'.$i++;
        }

        return $code;
    }
}
