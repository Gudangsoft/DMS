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
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-b from-brand-navy-light via-brand-navy to-brand-navy-dark px-4 py-12">
        <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-brand-gold/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-brand-gold/10 blur-3xl"></div>

        <a href="{{ route('home') }}" class="relative mb-8 flex items-center gap-3 text-white transition hover:opacity-90">
            @if ($siteLogo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteLogo) }}" alt="{{ $siteName }}" class="h-11 w-11 rounded-lg object-cover shadow-sm ring-1 ring-white/20">
            @else
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-gold text-lg font-bold text-brand-navy shadow-sm ring-1 ring-white/20">DMS</span>
            @endif
            <span class="text-lg font-semibold tracking-wide">{{ $siteName }}</span>
        </a>

        <div class="animate-fade-in-up relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
            <div class="h-1.5 bg-gradient-to-r from-brand-gold via-brand-gold-light to-brand-gold"></div>
            <div class="p-8">
                @yield('content')
            </div>
        </div>

        <p class="relative mt-8 text-sm text-white/60">
            &copy; {{ date('Y') }} {{ $siteTagline }}. Seluruh hak cipta dilindungi.
        </p>
    </div>
</body>
</html>
