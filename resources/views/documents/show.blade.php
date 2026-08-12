@extends('layouts.app', ['title' => $document->title])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <span class="inline-flex rounded bg-brand-navy/10 px-2 py-0.5 text-xs font-medium text-brand-navy">
                {{ $document->category->name }}@if ($document->subcategory) &middot; {{ $document->subcategory->name }} @endif
            </span>
            <h1 class="mt-3 text-2xl font-semibold text-gray-800">{{ $document->title }}</h1>
            @if ($document->description)
                <p class="mt-2 text-sm text-gray-600">{{ $document->description }}</p>
            @endif

            {{-- Poin 17: preview sebelum download --}}
            <div class="mt-6 rounded-lg bg-white p-4 shadow-sm">
                @if ($document->latestVersion?->isLink())
                    <div class="py-10 text-center">
                        <x-heroicon-o-arrow-top-right-on-square class="mx-auto h-8 w-8 text-gray-300" />
                        <p class="mt-3 text-sm text-gray-500">
                            File ini dihosting di situs eksternal. Buka untuk melihat sebelum mengunduh.
                        </p>
                        <a href="{{ route('documents.preview', $document->uuid) }}" target="_blank" rel="noopener"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-md bg-brand-navy px-4 py-2 text-sm font-semibold text-white hover:bg-brand-navy-light">
                            <x-heroicon-o-eye class="h-4 w-4" /> Lihat Dokumen
                        </a>
                    </div>
                @elseif ($document->latestVersion?->mime_type === 'application/pdf')
                    <div id="pdf-viewer" class="relative">
                        <p data-pdf-status class="py-12 text-center text-sm text-gray-500">Memuat preview…</p>
                        <div class="flex items-center justify-between pb-2 text-sm text-gray-600">
                            <button type="button" data-pdf-prev class="rounded border px-2 py-1 hover:bg-gray-50">&larr; Sebelumnya</button>
                            <span data-pdf-page-label></span>
                            <button type="button" data-pdf-next class="rounded border px-2 py-1 hover:bg-gray-50">Berikutnya &rarr;</button>
                        </div>
                        <canvas data-pdf-canvas class="hidden w-full rounded border"></canvas>
                    </div>

                    @vite(['resources/js/pdf-viewer.js'])
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            window.initPdfViewer('pdf-viewer', @json(route('documents.preview', $document->uuid)));
                        });
                    </script>
                @else
                    <p class="py-8 text-center text-sm text-gray-500">
                        Preview hanya tersedia untuk file PDF. Silakan unduh untuk melihat file ini.
                    </p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Nomor</dt><dd class="font-medium">{{ $document->document_number ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Kode</dt><dd class="font-medium">{{ $document->document_code ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Jenis</dt><dd class="font-medium">{{ $document->type->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Unit</dt><dd class="font-medium">{{ $document->unit->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Tahun</dt><dd class="font-medium">{{ $document->year }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Versi</dt><dd class="font-medium">{{ $document->current_version }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Berlaku</dt><dd class="font-medium">{{ $document->effective_date?->translatedFormat('d M Y') ?? '-' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Status</dt><dd class="font-medium">{{ $document->status->getLabel() }}</dd></div>
                </dl>

                @if ($canDownload)
                    <a href="{{ route('documents.download', $document->uuid) }}"
                        class="mt-4 block w-full rounded-md bg-brand-navy px-4 py-2 text-center text-sm font-semibold text-white hover:bg-brand-navy-light">
                        @if ($document->latestVersion?->isLink())
                            Buka File Eksternal
                        @else
                            Download ({{ $document->latestVersion?->humanFileSize() }})
                        @endif
                    </a>
                @else
                    <p class="mt-4 rounded-md bg-gray-50 p-3 text-center text-xs text-gray-500">
                        Anda tidak memiliki akses untuk mengunduh dokumen ini.
                    </p>
                @endif
            </div>

            <div class="rounded-lg bg-white p-5 text-center shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">QR Verifikasi</p>
                <img src="{{ app(\App\Services\QrCodeService::class)->dataUri($document) }}"
                    alt="QR verifikasi dokumen" class="mx-auto mt-2 h-32 w-32">
                <a href="{{ route('verify-document', $document->uuid) }}" class="mt-2 block text-xs text-brand-navy hover:underline">
                    {{ route('verify-document', $document->uuid) }}
                </a>
            </div>
        </div>
    </div>
@endsection
