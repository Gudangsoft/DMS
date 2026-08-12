@php
    $settings = \App\Models\Setting::query()->pluck('value', 'key');
    $siteName = $settings->get('site_name', 'Document Management System');
    $siteTagline = $settings->get('site_tagline', 'STIE Kasih Bangsa');
    $siteLogo = $settings->get('site_logo');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} — {{ $siteName }}</title>
    @if ($favicon = $settings->get('site_favicon'))
        <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($favicon) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-navy font-sans antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 text-white">
            @if ($siteLogo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteLogo) }}" alt="{{ $siteName }}" class="h-11 w-11 rounded-lg object-cover shadow-sm">
            @else
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-gold text-lg font-bold text-brand-navy">DMS</span>
            @endif
            <span class="text-lg font-semibold tracking-wide">{{ $siteName }}</span>
        </a>

        <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
            <div class="h-1.5 bg-brand-gold"></div>
            <div class="p-8">
                @yield('content')
            </div>
        </div>

        <p class="mt-8 text-sm text-white/60">
            &copy; {{ date('Y') }} {{ $siteTagline }}. Seluruh hak cipta dilindungi.
        </p>
    </div>
</body>
</html>
