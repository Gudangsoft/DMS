@extends('layouts.guest', ['title' => 'Lupa Password'])

@section('content')
    <h1 class="text-xl font-semibold text-brand-navy">Lupa Password</h1>
    <p class="mt-1 text-sm text-gray-500">
        Masukkan email Anda, kami akan mengirimkan tautan untuk membuat password baru.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="w-full rounded-md bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-navy-light focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2">
            Kirim Tautan Reset Password
        </button>

        <p class="text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-medium text-brand-navy hover:text-brand-gold">Kembali ke halaman masuk</a>
        </p>
    </form>
@endsection
