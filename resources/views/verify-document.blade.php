@extends('layouts.app', ['title' => 'Verifikasi Dokumen'])

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="overflow-hidden rounded-xl bg-white shadow-lg">
            <div class="{{ $isValid ? 'bg-green-600' : 'bg-red-600' }} px-6 py-8 text-center text-white">
                <p class="text-sm font-medium uppercase tracking-widest opacity-80">Status Dokumen</p>
                <p class="mt-2 text-3xl font-bold">{{ $isValid ? 'VALID' : 'TIDAK VALID' }}</p>
            </div>

            <div class="p-6">
                @if ($document)
                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Nama Dokumen</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $document->title }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Nomor Dokumen</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $document->document_number ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Unit</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $document->unit->name }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Versi</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $document->current_version }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Tanggal Berlaku</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $document->effective_date?->translatedFormat('d F Y') ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Status</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $document->status->getLabel() }}</dd>
                        </div>
                    </dl>

                    @if ($isValid)
                        <a href="{{ route('documents.show', $document->uuid) }}"
                            class="mt-6 block w-full rounded-md bg-brand-navy px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-navy-light">
                            Lihat Dokumen
                        </a>
                    @endif
                @else
                    <p class="text-center text-sm text-gray-500">
                        Kode QR ini tidak cocok dengan dokumen manapun di sistem.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endsection
