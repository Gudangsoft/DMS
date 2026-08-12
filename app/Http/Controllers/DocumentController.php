<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Poin 23/24 — public document catalog. /documents and /search share this same
 * listing so the "search box" and "browse with filters" experience stay consistent.
 */
class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Document::query()
            ->visibleTo($request->user())
            ->with(['category', 'subcategory', 'type', 'unit']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('document_code', 'like', "%{$search}%");
            });
        }

        if ($categoryCode = $request->string('category')->value()) {
            $query->whereHas('category', fn ($q) => $q->where('code', $categoryCode));
        }

        if ($subcategoryId = $request->integer('subcategory')) {
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

        $documents = $query->latest('published_at')->paginate(10)->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
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

        return view('documents.show', [
            'document' => $document,
            'canDownload' => Gate::forUser($request->user())->allows('download', $document),
        ]);
    }
}
