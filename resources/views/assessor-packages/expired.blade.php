@extends('layouts.app', ['title' => 'Akses Berakhir'])

@section('content')
    <div class="mx-auto max-w-lg rounded-xl bg-white py-12 text-center shadow-sm ring-1 ring-gray-950/5">
        <x-heroicon-o-lock-closed class="mx-auto h-10 w-10 text-gray-300" />
        <h1 class="mt-3 text-lg font-semibold text-gray-800">Akses paket dokumen telah berakhir</h1>
        <p class="mt-2 px-6 text-sm text-gray-500">
            Tautan "{{ $package->name }}" sudah tidak aktif.
            Silakan hubungi pengelola dokumen untuk mendapatkan akses baru.
        </p>
    </div>
@endsection
