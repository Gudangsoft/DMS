@extends('layouts.app', ['title' => $category->name])

@section('content')
    <a href="{{ route('categories.index') }}" class="flex items-center gap-1 text-sm text-brand-navy hover:underline">
        <x-heroicon-o-arrow-left class="h-4 w-4" /> Semua Kategori
    </a>

    @if ($category->cover_image)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->cover_image) }}" alt="{{ $category->name }}"
            class="mt-3 w-full rounded-xl shadow-sm">
    @endif

    <div class="mt-3 flex items-center gap-3">
        @unless ($category->cover_image)
            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-navy/10">
                <x-dynamic-component :component="$category->icon ?: 'heroicon-o-folder'" class="h-6 w-6 text-brand-navy" />
            </span>
        @endunless
        <div>
            <h1 class="text-2xl font-semibold text-brand-navy">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="text-sm text-gray-500">{{ $category->description }}</p>
            @endif
        </div>
    </div>

    @if ($documentsCount > 0)
        <p class="mt-6 text-sm text-gray-500">{{ $documentsCount }} dokumen ditemukan</p>

        @foreach ($documentsByYear as $year => $yearDocuments)
            <div class="mt-6">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-brand-navy">{{ $year }}</h2>
                    <span class="h-px flex-1 bg-gray-200"></span>
                    <span class="text-xs font-medium text-gray-400">{{ $yearDocuments->count() }} dokumen</span>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($yearDocuments as $document)
                        <a href="{{ route('documents.show', $document->uuid) }}"
                            class="group flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md">
                            @if ($document->subcategory)
                                <span class="inline-flex w-fit rounded bg-brand-navy/10 px-2 py-0.5 text-xs font-medium text-brand-navy">
                                    {{ $document->subcategory->name }}
                                </span>
                            @endif
                            <h3 class="mt-3 line-clamp-2 font-semibold text-gray-800 group-hover:text-brand-navy">{{ $document->title }}</h3>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-500">
                                {{ $document->description ?: 'Belum ada deskripsi untuk dokumen ini.' }}
                            </p>
                            <p class="mt-3 text-xs text-gray-400">{{ $document->document_number ?? 'Belum bernomor' }} &middot; {{ $document->year }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="mt-6 rounded-xl bg-white py-12 text-center shadow-sm ring-1 ring-gray-950/5">
            <x-heroicon-o-document-magnifying-glass class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-3 text-sm text-gray-500">Belum ada dokumen published di kategori ini.</p>
        </div>
    @endif
@endsection
