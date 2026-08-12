@extends('layouts.app', ['title' => 'Profil Saya'])

@section('content')
    <h1 class="text-2xl font-semibold text-brand-navy">Profil Saya</h1>
    <p class="mt-1 text-sm text-gray-500">Kelola informasi akun dan password Anda.</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Informasi Profil --}}
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Informasi Profil</h2>

            @if (session('status') === 'profile-updated')
                <div class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700">Profil berhasil diperbarui.</div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700">Jabatan</label>
                    <input id="position" name="position" type="text" value="{{ old('position', $user->position) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
                </div>

                <div class="text-sm text-gray-500">
                    Unit: <span class="font-medium text-gray-700">{{ $user->unit?->name ?? '-' }}</span><br>
                    Role:
                    @foreach ($user->roles as $role)
                        <span class="inline-flex rounded bg-brand-navy/10 px-2 py-0.5 text-xs font-medium text-brand-navy">{{ $role->name }}</span>
                    @endforeach
                </div>

                <button type="submit"
                    class="rounded-md bg-brand-navy px-4 py-2 text-sm font-semibold text-white hover:bg-brand-navy-light">
                    Simpan
                </button>
            </form>
        </div>

        {{-- Ganti Password --}}
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Ganti Password</h2>

            @if (session('status') === 'password-updated')
                <div class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700">Password berhasil diperbarui.</div>
            @endif

            <form method="POST" action="{{ route('profile.password.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                    <input id="current_password" name="current_password" type="password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                    <input id="password" name="password" type="password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-navy focus:ring-brand-navy sm:text-sm">
                </div>

                <button type="submit"
                    class="rounded-md bg-brand-navy px-4 py-2 text-sm font-semibold text-white hover:bg-brand-navy-light">
                    Perbarui Password
                </button>
            </form>
        </div>
    </div>
@endsection
