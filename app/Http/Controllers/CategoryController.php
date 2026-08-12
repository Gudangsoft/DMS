<?php

namespace App\Http\Controllers;

use App\Models\DocumentCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = DocumentCategory::where('is_active', true)
            ->withCount(['documents' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')
            ->get();

        return view('categories.index', ['categories' => $categories]);
    }

    /**
     * Bound by `code` (e.g. DA, DM, AKR) — document_categories has no separate
     * slug column, and the code is already a short, unique, URL-safe identifier.
     */
    public function show(Request $request, DocumentCategory $category): View
    {
        $documents = $category->documents()
            ->visibleTo($request->user())
            ->with(['subcategory', 'type', 'unit'])
            ->latest('published_at')
            ->paginate(12);

        return view('categories.show', [
            'category' => $category,
            'documents' => $documents,
        ]);
    }
}
