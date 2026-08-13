@extends('layouts.app', ['title' => $page->title])

@section('content')
    {{-- Full width (matches the rest of the site — no narrow centered column
         leaving dead space on wider screens). Featured image shows at its
         natural aspect ratio, never cropped. --}}
    @if ($page->featured_image)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($page->featured_image) }}" alt="{{ $page->title }}"
            class="mb-6 w-full rounded-lg shadow-sm">
    @endif

    <h1 class="text-2xl font-semibold text-brand-navy">{{ $page->title }}</h1>
    @if ($page->excerpt)
        <p class="mt-2 text-sm text-gray-500">{{ $page->excerpt }}</p>
    @endif

    {{-- Plain `prose` (no `sm:prose` responsive variant) — Tailwind compiles
         `sm:prose`'s max-width:65ch inside a @media block that lands *after*
         max-w-none in the stylesheet, so at same specificity it silently wins
         back the width cap at ≥640px screens and `max-w-none` has no effect.
         `overflow-x-auto` guards against wide tables/embeds pasted into the
         rich text editor — they scroll inside this box on narrow screens
         instead of stretching the whole page horizontally. --}}
    <div class="prose mt-6 max-w-none overflow-x-auto rounded-lg bg-white p-6 shadow-sm">
        {!! $page->content !!}
    </div>
@endsection
