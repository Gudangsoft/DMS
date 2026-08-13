@extends('layouts.guest', ['title' => 'Masuk'])

@section('content')
    <h1 class="text-xl font-semibold text-brand-navy">Masuk ke Akun Anda</h1>
    <p class="mt-1 text-sm text-gray-500">Gunakan email dan password institusi Anda.</p>

    @if (session('status'))
        <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
        </div>

        <div>
            <label for="captcha" class="block text-sm font-medium text-gray-700">
                Berapa hasil dari {{ $captcha['a'] }} + {{ $captcha['b'] }}?
            </label>
            <input id="captcha" type="number" name="captcha" required autocomplete="off" inputmode="numeric"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
            @error('captcha')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-navy focus:ring-brand-navy">
                Ingat saya
            </label>

            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-navy hover:text-brand-gold">
                Lupa password?
            </a>
        </div>

        <button type="submit"
            class="w-full rounded-md bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-navy-light focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2">
            Masuk
        </button>
    </form>
@endsection
