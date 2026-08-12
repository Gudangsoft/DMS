@extends('layouts.app', ['title' => 'Beranda'])

@section('hero')
    {{-- Header slider full-bleed (poin tambahan): dikelola lewat menu "Slider Beranda" di admin --}}
    @include('partials.hero-slider', [
        'sliders' => $sliders,
        'compact' => false,
        'eyebrow' => \App\Models\Setting::get('site_tagline', 'STIE Kasih Bangsa'),
        'fallbackTitle' => \App\Models\Setting::get('site_name', 'Document Management System'),
        'fallbackSubtitle' => 'Pusat pengelolaan dokumen resmi perguruan tinggi — regulasi, mutu, akademik, akreditasi, dan administrasi — terstruktur, aman, dan mudah ditelusuri.',
    ])
@endsection

@section('content')
    {{-- Trust badges --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['icon' => 'shield-check', 'label' => 'Aman & Terenkripsi'],
            ['icon' => 'check-badge', 'label' => 'Approval Berjenjang'],
            ['icon' => 'clock', 'label' => 'Riwayat Versi Lengkap'],
            ['icon' => 'qr-code', 'label' => 'Verifikasi QR Code'],
        ] as $badge)
            <div class="flex items-center gap-2.5 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-600 shadow-md ring-1 ring-gray-950/5">
                <x-dynamic-component :component="'heroicon-o-'.$badge['icon']" class="h-5 w-5 shrink-0 text-brand-gold" />
                {{ $badge['label'] }}
            </div>
        @endforeach
    </div>

    {{-- Statistik ringkas --}}
    <div class="mt-10 grid grid-cols-3 gap-4">
        @foreach ([
            ['icon' => 'document-text', 'value' => $stats['documents'], 'label' => 'Dokumen Terbit'],
            ['icon' => 'folder-open', 'value' => $stats['categories'], 'label' => 'Kategori'],
            ['icon' => 'building-office-2', 'value' => $stats['units'], 'label' => 'Unit Kerja'],
        ] as $stat)
            <div class="relative overflow-hidden rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-950/5">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-gold to-brand-gold-light"></div>
                <x-dynamic-component :component="'heroicon-o-'.$stat['icon']" class="mx-auto h-7 w-7 text-brand-navy/40" />
                <p class="mt-2 text-2xl font-bold text-brand-navy sm:text-3xl">{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Kenapa DMS kami --}}
    <div class="mt-16">
        <div class="text-center">
            <p class="text-xs font-semibold tracking-widest text-brand-gold uppercase">Keunggulan</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-800">Kenapa Menggunakan DMS Kami?</h2>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['icon' => 'lock-closed', 'title' => 'Kerahasiaan Berjenjang', 'desc' => 'Lima tingkat akses — dari publik hingga rahasia — memastikan dokumen hanya dilihat pihak yang berhak.'],
                ['icon' => 'arrow-path-rounded-square', 'title' => 'Versi Terkontrol', 'desc' => 'Setiap revisi tersimpan sebagai versi baru, versi lama tidak pernah hilang.'],
                ['icon' => 'magnifying-glass-circle', 'title' => 'Pencarian Cepat', 'desc' => 'Temukan dokumen berdasarkan judul, nomor, kategori, unit, atau tahun dalam hitungan detik.'],
                ['icon' => 'qr-code', 'title' => 'Verifikasi Instan', 'desc' => 'Pindai kode QR untuk memastikan dokumen yang Anda pegang adalah versi resmi & terbaru.'],
            ] as $feature)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-navy/10">
                        <x-dynamic-component :component="'heroicon-o-'.$feature['icon']" class="h-6 w-6 text-brand-navy" />
                    </span>
                    <h3 class="mt-4 font-semibold text-gray-800">{{ $feature['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-gray-500">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Kategori --}}
    <div class="mt-16">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold tracking-widest text-brand-gold uppercase">Jelajahi</p>
                <h2 class="mt-1 text-lg font-semibold text-gray-800">Kategori Dokumen</h2>
            </div>
            <a href="{{ route('documents.index') }}" class="flex items-center gap-1 text-sm font-medium text-brand-navy hover:text-brand-gold">
                Lihat semua <x-heroicon-o-arrow-right class="h-4 w-4" />
            </a>
        </div>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($categories as $category)
                <a href="{{ route('documents.index', ['category' => $category->code]) }}"
                    class="group flex items-center gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    @if ($category->cover_image)
                        <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->cover_image) }}" alt="{{ $category->name }}" class="h-full w-full object-cover">
                        </span>
                    @else
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-navy/10 transition group-hover:bg-brand-gold/20">
                            <x-dynamic-component :component="$category->icon ?: 'heroicon-o-folder'" class="h-6 w-6 text-brand-navy" />
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-semibold text-gray-800">{{ $category->name }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $category->documents_count }} dokumen</p>
                    </div>
                    <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-gray-300 transition group-hover:text-brand-gold" />
                </a>
            @empty
                <p class="col-span-full text-sm text-gray-500">Belum ada kategori aktif.</p>
            @endforelse
        </div>
    </div>

    {{-- Dokumen terbaru --}}
    <div class="mt-16">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold tracking-widest text-brand-gold uppercase">Terbaru</p>
                <h2 class="mt-1 text-lg font-semibold text-gray-800">Dokumen Terbaru</h2>
            </div>
            <a href="{{ route('documents.index') }}" class="flex items-center gap-1 text-sm font-medium text-brand-navy hover:text-brand-gold">
                Lihat semua <x-heroicon-o-arrow-right class="h-4 w-4" />
            </a>
        </div>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($recentDocuments as $document)
                <a href="{{ route('documents.show', $document->uuid) }}"
                    class="group flex flex-col rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <span class="inline-flex w-fit items-center gap-1 rounded bg-brand-navy/10 px-2 py-0.5 text-xs font-medium text-brand-navy">
                            <x-dynamic-component :component="$document->category->icon ?: 'heroicon-o-folder'" class="h-3.5 w-3.5" />
                            {{ $document->category->name }}
                        </span>
                        <x-heroicon-o-document-check class="h-5 w-5 shrink-0 text-brand-gold" />
                    </div>
                    <h3 class="mt-3 line-clamp-2 font-semibold text-gray-800 group-hover:text-brand-navy">{{ $document->title }}</h3>
                    <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                        <x-heroicon-o-building-office-2 class="h-3.5 w-3.5" />
                        {{ $document->unit->name }} &middot; {{ $document->year }}
                    </p>
                </a>
            @empty
                <p class="col-span-full text-sm text-gray-500">Belum ada dokumen yang dipublikasikan.</p>
            @endforelse
        </div>
    </div>
@endsection
