@extends('layouts.app', ['title' => 'Kategori Dokumen'])

@section('content')
    <div class="flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-navy/10">
            <x-heroicon-o-folder-open class="h-6 w-6 text-brand-navy" />
        </span>
        <div>
            <h1 class="text-2xl font-semibold text-brand-navy">Kategori Dokumen</h1>
            <p class="text-sm text-gray-500">Jelajahi dokumen berdasarkan kategori.</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($categories as $category)
            <a href="{{ route('categories.show', $category->code) }}"
                class="group flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-navy/10 transition group-hover:bg-brand-gold/20">
                    <x-dynamic-component :component="$category->icon ?: 'heroicon-o-folder'" class="h-6 w-6 text-brand-navy" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate font-semibold text-gray-800">{{ $category->name }}</h2>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $category->documents_count }} dokumen</p>
                </div>
                <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-gray-300 transition group-hover:text-brand-gold" />
            </a>
        @endforeach
    </div>
@endsection
