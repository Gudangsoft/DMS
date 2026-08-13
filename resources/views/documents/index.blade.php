@extends('layouts.app', ['title' => 'Dokumen'])

@php
    $coverGradients = [
        'linear-gradient(135deg, #0b2545 0%, #13315c 100%)',
        'linear-gradient(135deg, #13315c 0%, #d4af37 130%)',
        'linear-gradient(135deg, #0b2545 0%, #3b5b8c 100%)',
        'linear-gradient(135deg, #071a33 0%, #0b2545 60%, #d4af37 140%)',
    ];
@endphp

@section('content')
    <div class="flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-navy/10">
            <x-heroicon-o-document-duplicate class="h-6 w-6 text-brand-navy" />
        </span>
        <div>
            <h1 class="text-2xl font-semibold text-brand-navy">Dokumen</h1>
            <p class="text-sm text-gray-500">Telusuri dokumen resmi yang telah dipublikasikan.</p>
        </div>
    </div>

    <div class="mt-6 lg:flex lg:items-start lg:gap-6">
        {{-- Sidebar --}}
        <aside class="lg:w-72 lg:shrink-0">
            <div class="space-y-4 lg:sticky lg:top-24">
                <form method="GET" action="{{ route('documents.search') }}" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari dokumen..."
                            class="w-full rounded-md border-gray-300 pl-9 text-sm shadow-sm focus:border-brand-navy focus:ring-brand-navy">
                    </div>
                </form>

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
                    <h2 class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                        <x-heroicon-o-folder class="h-4 w-4" /> Kategori Dokumen
                    </h2>
                    <ul class="mt-3 divide-y divide-gray-100">
                        <li class="pb-1">
                            <a href="{{ route('documents.index') }}"
                                class="block rounded px-2 py-2 text-sm font-medium {{ ! request()->hasAny(['category', 'subcategory']) ? 'bg-brand-navy/10 text-brand-navy' : 'text-gray-600 hover:text-brand-navy' }}">
                                Semua Dokumen
                            </a>
                        </li>
                        @foreach ($categoriesTree as $cat)
                            <li x-data="{ open: {{ request('category') === $cat->code ? 'true' : 'false' }} }" class="py-1">
                                <button type="button" @click="open = !open"
                                    class="flex w-full items-center justify-between gap-2 rounded px-2 py-2 text-left text-sm font-medium text-gray-700 hover:bg-brand-navy/5 hover:text-brand-navy">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <x-dynamic-component :component="$cat->icon ?: 'heroicon-o-folder'" class="h-4 w-4 shrink-0 text-brand-navy/60" />
                                        <span class="truncate">{{ $cat->code }}. {{ $cat->name }}</span>
                                    </span>
                                    <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 text-gray-400 transition" ::class="open ? 'rotate-180' : ''" />
                                </button>
                                <ul x-show="open" x-transition x-cloak class="mt-1 ml-6 space-y-0.5 border-l border-gray-100 pl-3">
                                    <li>
                                        <a href="{{ route('documents.index', ['category' => $cat->code]) }}"
                                            class="block rounded px-2 py-1.5 text-sm {{ request('category') === $cat->code && ! request('subcategory') ? 'font-semibold text-brand-navy' : 'text-gray-500 hover:text-brand-navy' }}">
                                            Semua {{ $cat->name }}
                                        </a>
                                    </li>
                                    @foreach ($cat->subcategories as $sub)
                                        <li>
                                            <a href="{{ route('documents.index', ['category' => $cat->code, 'subcategory' => $sub->id]) }}"
                                                class="block rounded px-2 py-1.5 text-sm {{ (int) request('subcategory') === $sub->id ? 'font-semibold text-brand-navy' : 'text-gray-500 hover:text-brand-navy' }}">
                                                @if ($sub->code)
                                                    {{ $sub->code }}.
                                                @endif
                                                {{ $sub->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div x-data="{ open: {{ request()->hasAny(['unit', 'year', 'type']) ? 'true' : 'false' }} }" class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-xs font-semibold tracking-wide text-gray-500 uppercase">
                        <span class="flex items-center gap-1.5"><x-heroicon-o-adjustments-horizontal class="h-4 w-4" /> Filter Lanjutan</span>
                        <x-heroicon-o-chevron-down class="h-4 w-4 transition" ::class="open ? 'rotate-180' : ''" />
                    </button>
                    <form x-show="open" x-transition x-cloak method="GET" action="{{ route('documents.index') }}" class="mt-3 space-y-3">
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <input type="hidden" name="category" value="{{ request('category') }}">
                        <input type="hidden" name="subcategory" value="{{ request('subcategory') }}">

                        <select name="unit" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-navy focus:ring-brand-navy">
                            <option value="">Semua Unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->code }}" @selected(request('unit') === $unit->code)>{{ $unit->name }}</option>
                            @endforeach
                        </select>

                        <select name="type" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-navy focus:ring-brand-navy">
                            <option value="">Semua Jenis</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" @selected((string) request('type') === (string) $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>

                        <select name="year" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-navy focus:ring-brand-navy">
                            <option value="">Semua Tahun</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="w-full rounded-md bg-brand-navy px-3 py-2 text-sm font-semibold text-white hover:bg-brand-navy-light">
                            Terapkan Filter
                        </button>
                    </form>
                </div>

                @if (request()->hasAny(['q', 'category', 'subcategory', 'unit', 'year', 'type']))
                    <a href="{{ route('documents.index') }}" class="block text-center text-xs text-gray-400 hover:text-brand-navy">
                        &times; Reset semua filter
                    </a>
                @endif
            </div>
        </aside>

        {{-- Feed --}}
        <div class="mt-6 min-w-0 flex-1 lg:mt-0">
            <p class="text-sm text-gray-500">{{ $documents->total() }} dokumen ditemukan</p>

            <div class="mt-3 space-y-4">
                @forelse ($documents as $document)
                    <a href="{{ route('documents.show', $document->uuid) }}"
                        class="group flex flex-col gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md sm:flex-row sm:gap-5 sm:p-5">
                        {{-- Wide banner-style cover (matches the reference site's card
                             proportions) instead of a small square thumbnail. --}}
                        <div class="relative h-36 w-full shrink-0 overflow-hidden rounded-lg sm:h-32 sm:w-56"
                            style="{{ $document->category->cover_image ? "background-image:url('".\Illuminate\Support\Facades\Storage::disk('public')->url($document->category->cover_image)."');background-size:cover;background-position:center;" : 'background:'.$coverGradients[$document->document_category_id % count($coverGradients)].';' }}">
                            @unless ($document->category->cover_image)
                                <div class="absolute inset-0 flex items-center justify-center opacity-25">
                                    <x-dynamic-component :component="$document->category->icon ?: 'heroicon-o-document-text'" class="h-12 w-12 text-white sm:h-14 sm:w-14" />
                                </div>
                            @endunless
                            <div class="absolute inset-x-0 bottom-0 bg-black/30 px-2.5 py-1.5">
                                <p class="truncate text-xs font-semibold tracking-wide text-white/90 uppercase">{{ $document->category->name }}</p>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h2 class="line-clamp-2 font-semibold text-gray-800 group-hover:text-brand-navy">{{ $document->title }}</h2>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-500">
                                {{ $document->description ?: 'Belum ada deskripsi untuk dokumen ini.' }}
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-400">
                                <span class="flex items-center gap-1"><x-heroicon-o-building-office-2 class="h-3.5 w-3.5" />{{ $document->unit->name }}</span>
                                <span class="flex items-center gap-1"><x-heroicon-o-calendar class="h-3.5 w-3.5" />{{ $document->year }}</span>
                                <span class="flex items-center gap-1"><x-heroicon-o-document-text class="h-3.5 w-3.5" />{{ $document->document_number ?? 'Belum bernomor' }}</span>
                                <span class="rounded bg-gray-100 px-1.5 py-0.5 font-medium text-gray-500">v{{ $document->current_version }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl bg-white py-12 text-center shadow-sm ring-1 ring-gray-950/5">
                        <x-heroicon-o-document-magnifying-glass class="mx-auto h-10 w-10 text-gray-300" />
                        <p class="mt-3 text-sm text-gray-500">Tidak ada dokumen yang ditemukan.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
@endsection
