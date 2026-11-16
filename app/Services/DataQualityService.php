<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentValidity;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data-quality checks for the "Cek Kualitas Data" admin page. Each check is a
 * query over documents (non-archived) so the page can list and count them.
 */
class DataQualityService
{
    /**
     * @return array<string, array{label: string, description: string, color: string}>
     */
    public function checks(): array
    {
        return [
            'year_mismatch' => [
                'label' => 'Tahun tidak cocok',
                'description' => 'Judul menyebut tahun (mis. "Statuta Tahun 1998") yang berbeda dari kolom Tahun. Urutan di web memakai kolom Tahun.',
                'color' => 'danger',
            ],
            'missing_file' => [
                'label' => 'File hilang',
                'description' => 'File dokumen tidak ditemukan di server, sehingga tidak bisa dilihat/diunduh.',
                'color' => 'danger',
            ],
            'expired_still_valid' => [
                'label' => 'Kadaluarsa tapi "Berlaku"',
                'description' => 'Tanggal Kadaluarsa sudah lewat, tapi keberlakuannya masih "Berlaku".',
                'color' => 'danger',
            ],
            'superseded_without_replacement' => [
                'label' => 'Digantikan tanpa pengganti',
                'description' => 'Ditandai "Digantikan" tapi belum dipilih dokumen penggantinya.',
                'color' => 'warning',
            ],
            'no_subcategory' => [
                'label' => 'Tanpa subkategori',
                'description' => 'Kategorinya punya subkategori, tapi dokumen ini belum dimasukkan ke salah satunya — tidak muncul di menu subkategori.',
                'color' => 'warning',
            ],
            'duplicate_title' => [
                'label' => 'Judul ganda',
                'description' => 'Ada lebih dari satu dokumen dengan judul persis sama. Periksa apakah duplikat.',
                'color' => 'warning',
            ],
            'no_effective_date' => [
                'label' => 'Tanggal Berlaku kosong',
                'description' => 'Dokumen published tanpa Tanggal Berlaku. Isi agar urutan di web akurat.',
                'color' => 'gray',
            ],
            'not_searchable' => [
                'label' => 'Isi tidak bisa dicari',
                'description' => 'File diunggah tapi tidak ada teks yang terbaca (biasanya PDF hasil scan). Pencarian hanya bisa lewat judul.',
                'color' => 'gray',
            ],
        ];
    }

    public function query(string $check): Builder
    {
        $query = Document::query();

        return match ($check) {
            'year_mismatch' => $query
                ->whereRaw("title REGEXP '(19|20)[0-9]{2}'")
                ->whereRaw('LOCATE(CAST(year AS CHAR), title) = 0'),

            'missing_file' => $query->whereKey($this->missingFileDocumentIds()),

            'expired_still_valid' => $query
                ->whereNotNull('expired_date')
                ->whereDate('expired_date', '<', today())
                ->where('validity', DocumentValidity::Berlaku->value),

            'superseded_without_replacement' => $query
                ->where('validity', DocumentValidity::Digantikan->value)
                ->whereNull('superseded_by_id'),

            'no_subcategory' => $query
                ->whereNull('document_subcategory_id')
                ->whereHas('category.subcategories'),

            'duplicate_title' => $query->whereIn('title', Document::query()
                ->select('title')
                ->groupBy('title')
                ->having(DB::raw('count(*)'), '>', 1)),

            'no_effective_date' => $query
                ->where('status', DocumentStatus::Published->value)
                ->whereNull('effective_date'),

            'not_searchable' => $query
                ->whereHas('latestVersion', fn ($v) => $v->where('source_type', 'upload'))
                ->whereHas('content', fn ($c) => $c->whereNull('content')),

            default => $query->whereRaw('1 = 0'),
        };
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return collect(array_keys($this->checks()))
            ->mapWithKeys(fn (string $key) => [$key => $this->query($key)->count()])
            ->all();
    }

    /**
     * Checking every file on disk is the only check that can't be done in SQL;
     * cached briefly so switching tabs stays fast.
     *
     * @return array<int, int>
     */
    public function missingFileDocumentIds(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('dms.data-quality.missing-files');
        }

        return Cache::remember('dms.data-quality.missing-files', now()->addMinutes(10), function () {
            $disk = Storage::disk(config('documents.storage_disk'));

            // Latest version per document.
            $latestIds = DocumentVersion::query()->selectRaw('max(id)')->groupBy('document_id');

            return DocumentVersion::query()
                ->whereIn('id', $latestIds)
                ->where('source_type', 'upload')
                ->get(['document_id', 'file_path'])
                ->reject(fn (DocumentVersion $v) => $v->file_path && $disk->exists($v->file_path))
                ->pluck('document_id')
                ->all();
        });
    }
}
