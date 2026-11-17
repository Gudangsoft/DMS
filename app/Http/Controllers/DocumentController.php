<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentContent;
use App\Models\DocumentType;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Poin 23/24 — public document catalog. /documents and /search share this same
 * listing so the "search box" and "browse with filters" experience stay consistent.
 */
class DocumentController extends Controller
{
    /** "Urutkan" options on the public list (?sort=…). First one is the default. */
    public const SORTS = [
        'newest' => 'Terbaru',
        'oldest' => 'Terlama',
        'recent' => 'Baru ditambahkan',
        'popular' => 'Paling banyak diunduh',
        'title' => 'Judul A–Z',
        'title_desc' => 'Judul Z–A',
    ];

    public function index(Request $request): View
    {
        $query = Document::query()
            ->visibleTo($request->user())
            ->with(['category', 'subcategory', 'type', 'unit']);

        $search = $request->string('q')->trim()->value();

        if ($search !== '') {
            // Matches the title/number/code, or text inside the file itself
            // (extracted into document_contents — see DocumentContentExtractor).
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('document_code', 'like', "%{$search}%")
                    ->orWhereHas('content', fn ($c) => $c->where('content', 'like', "%{$search}%"));
            });
        }

        $categoryCode = $request->string('category')->value();
        if ($categoryCode) {
            $query->whereHas('category', fn ($q) => $q->where('code', $categoryCode));
        }

        $subcategoryId = $request->integer('subcategory');
        if ($subcategoryId) {
            $query->where('document_subcategory_id', $subcategoryId);
        }

        if ($unitCode = $request->string('unit')->value()) {
            $query->whereHas('unit', fn ($q) => $q->where('code', $unitCode));
        }

        if ($typeId = $request->integer('type')) {
            $query->where('document_type_id', $typeId);
        }

        if ($year = $request->integer('year')) {
            $query->where('year', $year);
        }

        $sort = array_key_exists($request->string('sort')->value(), self::SORTS)
            ? $request->string('sort')->value()
            : 'newest';

        match ($sort) {
            'oldest' => $query->orderByRaw('year IS NULL')->orderBy('year')
                ->orderByRaw('effective_date IS NULL')->orderBy('effective_date')->orderBy('id'),
            'title' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'popular' => $query->orderByDesc('download_count')->orderByDesc('id'),
            'recent' => $query->orderByDesc('published_at')->orderByDesc('id'),
            // Manual "Urutan" pins only make sense inside one menu.
            default => $query->newestFirst(usePins: (bool) ($categoryCode || $subcategoryId)),
        };

        $documents = $query->paginate(10)->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'snippets' => $search !== '' ? $this->snippets($documents->getCollection()->modelKeys(), $search) : [],
            'categoriesTree' => DocumentCategory::where('is_active', true)
                ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'types' => DocumentType::where('is_active', true)->orderBy('name')->get(),
            'years' => Document::visibleTo($request->user())->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }

    public function show(Request $request, Document $document): View
    {
        $this->authorize('view', $document);

        $document->load(['category', 'subcategory', 'type', 'unit', 'owner', 'latestVersion']);

        $user = $request->user();

        return view('documents.show', [
            'document' => $document,
            'canDownload' => Gate::forUser($user)->allows('download', $document),
            // Only link to documents this visitor may actually open.
            'replacement' => $document->superseded_by_id
                ? Document::visibleTo($user)->find($document->superseded_by_id)
                : null,
            'replaces' => $document->supersedes()->visibleTo($user)->orderByDesc('year')->get(),
        ]);
    }

    /**
     * A short, HTML-escaped excerpt around the first match inside each
     * document's extracted text, with the match highlighted.
     *
     * @param  array<int, int>  $documentIds
     * @return array<int, string>
     */
    protected function snippets(array $documentIds, string $search): array
    {
        $snippets = [];

        DocumentContent::whereIn('document_id', $documentIds)
            ->whereNotNull('content')
            ->get(['document_id', 'content'])
            ->each(function (DocumentContent $row) use ($search, &$snippets) {
                $text = preg_replace('/\s+/u', ' ', (string) $row->content);
                $pos = mb_stripos($text, $search);
                if ($pos === false) {
                    return;
                }

                $start = max(0, $pos - 80);
                $excerpt = mb_substr($text, $start, 220);
                $escaped = e($excerpt);
                $highlighted = preg_replace(
                    '/'.preg_quote(e($search), '/').'/iu',
                    '<mark class="rounded bg-brand-gold/30 px-0.5 text-gray-900">$0</mark>',
                    $escaped
                );

                $snippets[$row->document_id] = ($start > 0 ? '… ' : '').$highlighted.' …';
            });

        return $snippets;
    }
}
