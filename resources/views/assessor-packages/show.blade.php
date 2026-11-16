@extends('layouts.app', ['title' => $package->name])

@section('content')
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="flex items-center gap-1.5 text-xs font-semibold tracking-widest text-brand-gold uppercase">
                    <x-heroicon-o-briefcase class="h-4 w-4" /> Paket Dokumen Asesor
                </p>
                <h1 class="mt-1 text-2xl font-semibold text-brand-navy">{{ $package->name }}</h1>
                @if ($package->description)
                    <p class="mt-2 text-sm whitespace-pre-line text-gray-600">{{ $package->description }}</p>
                @endif
                <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><x-heroicon-o-document-duplicate class="h-4 w-4" /> {{ $total }} dokumen</span>
                    @if ($package->expires_at)
                        <span class="flex items-center gap-1"><x-heroicon-o-clock class="h-4 w-4" /> Akses berlaku sampai {{ $package->expires_at->translatedFormat('d F Y, H:i') }}</span>
                    @endif
                </p>
            </div>
            @if ($total > 0)
                <a href="{{ route('assessor-packages.zip', $package->token) }}"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md bg-brand-navy px-4 py-2 text-sm font-semibold text-white hover:bg-brand-navy-light">
                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Unduh Semua (ZIP)
                </a>
            @endif
        </div>
    </div>

    @forelse ($documentsByCategory as $categoryName => $documents)
        <div class="mt-8">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold text-brand-navy">{{ $categoryName }}</h2>
                <span class="h-px flex-1 bg-gray-200"></span>
                <span class="text-xs font-medium text-gray-400">{{ $documents->count() }} dokumen</span>
            </div>

            <div class="mt-3 divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                @foreach ($documents as $document)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-validity-badge :document="$document" />
                                @if ($document->subcategory)
                                    <span class="text-xs text-gray-500">{{ $document->subcategory->name }}</span>
                                @endif
                            </div>
                            <p class="mt-1 font-medium text-gray-800">{{ $document->title }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $document->document_number ?? 'Belum bernomor' }} &middot; {{ $document->year }}
                                @if ($document->latestVersion && ! $document->latestVersion->isLink())
                                    &middot; {{ $document->latestVersion->humanFileSize() }}
                                @endif
                            </p>
                        </div>
                        @if ($document->latestVersion)
                            <a href="{{ route('assessor-packages.file', [$package->token, $document->uuid]) }}" target="_blank" rel="noopener"
                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-medium text-brand-navy ring-1 ring-brand-navy/20 hover:bg-brand-navy/5">
                                @if ($document->latestVersion->isLink())
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" /> Buka Tautan
                                @elseif ($document->latestVersion->mime_type === 'application/pdf')
                                    <x-heroicon-o-eye class="h-4 w-4" /> Lihat
                                @else
                                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Unduh
                                @endif
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="mt-6 rounded-xl bg-white py-12 text-center shadow-sm ring-1 ring-gray-950/5">
            <x-heroicon-o-document-magnifying-glass class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-3 text-sm text-gray-500">Paket ini belum berisi dokumen.</p>
        </div>
    @endforelse
@endsection
