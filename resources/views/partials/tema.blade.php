{{-- Warna tema dari Pengaturan Web; menimpa palet bawaan di resources/css/app.css. --}}
<style>
    :root {
        @foreach (\App\Support\Tema::variabel() as $nama => $nilai)
            {{ $nama }}: {{ $nilai }};
        @endforeach
    }
</style>
