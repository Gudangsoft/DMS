@extends('layouts.guest', ['title' => 'Masuk'])

@section('content')
    <div class="flex flex-col items-center text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-navy/5 text-brand-navy">
            <x-heroicon-o-lock-closed class="h-6 w-6" />
        </span>
        <h1 class="mt-3 text-xl font-semibold text-brand-navy">Masuk ke Akun Anda</h1>
        <p class="mt-1 text-sm text-gray-500">Gunakan email dan password institusi Anda.</p>
    </div>

    @if (session('status'))
        <div class="mt-5 flex items-center gap-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
            <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" x-data="{ showPassword: false, submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <x-heroicon-o-envelope class="h-5 w-5" />
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="block w-full rounded-lg border-gray-300 py-3 pl-11 pr-4 text-base shadow-sm focus:border-brand-navy focus:ring-brand-navy">
            </div>
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                </span>
                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
                    class="block w-full rounded-lg border-gray-300 py-3 pl-11 pr-11 text-base shadow-sm focus:border-brand-navy focus:ring-brand-navy">
                <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-brand-navy">
                    <x-heroicon-o-eye class="h-5 w-5" x-show="!showPassword" />
                    <x-heroicon-o-eye-slash class="h-5 w-5" x-show="showPassword" x-cloak />
                    <span class="sr-only">Tampilkan/sembunyikan password</span>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3.5">
            <label for="captcha" class="flex items-center gap-1.5 text-sm font-medium text-gray-700">
                <x-heroicon-o-shield-check class="h-4 w-4 shrink-0 text-brand-navy" />
                Berapa hasil dari {{ $captcha['a'] }} + {{ $captcha['b'] }}?
            </label>
            <input id="captcha" type="number" name="captcha" required autocomplete="off" inputmode="numeric"
                class="mt-2 block w-full rounded-lg border-gray-300 bg-white py-3 px-4 text-base shadow-sm focus:border-brand-navy focus:ring-brand-navy">
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

        <button type="submit" :disabled="submitting"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-navy px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-navy-light focus:outline-none focus:ring-2 focus:ring-brand-gold focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70">
            <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span x-text="submitting ? 'Memproses…' : 'Masuk'">Masuk</span>
        </button>
    </form>
@endsection
