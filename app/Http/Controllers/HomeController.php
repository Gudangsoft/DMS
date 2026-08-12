<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Slider;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $visible = Document::query()->visibleTo($request->user());

        return view('home', [
            'sliders' => Slider::active()->get(),
            'stats' => [
                'documents' => (clone $visible)->count(),
                'categories' => DocumentCategory::where('is_active', true)->count(),
                'units' => Unit::where('is_active', true)->count(),
            ],
            'categories' => DocumentCategory::where('is_active', true)
                ->withCount(['documents' => fn ($q) => $q->where('status', 'published')])
                ->orderBy('sort_order')
                ->limit(6)
                ->get(),
            'recentDocuments' => (clone $visible)
                ->with(['category', 'unit'])
                ->latest('published_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
