@php
    // Fetched once per render — see App\Models\Setting docblock for why this
    // stays uncached rather than using a process-lifetime cache.
    $settings = \App\Models\Setting::query()->pluck('value', 'key');
    $isHome = request()->routeIs('home');
    $siteName = $settings->get('site_name', 'Document Management System');
    $siteTagline = $settings->get('site_tagline', 'STIE Kasih Bangsa');
    $siteLogo = $settings->get('site_logo');
    $headerMenu = \App\Models\MenuItem::tree('header')->get();
    $footerMenu = \App\Models\MenuItem::tree('footer')->get();
    $isActiveMenu = fn (string $url) => $url !== '/' && str_starts_with($url, '/') && request()->is(ltrim($url, '/').'*');

    // Admin-configurable template colors ("Pengaturan Web" → tab "Warna Tema").
    // Defaults match the original hardcoded navy/gold palette, so sites that
    // haven't touched this setting look exactly as before. The accent color is
    // applied globally via CSS custom properties (every bg-brand-gold/
    // text-brand-gold utility already compiles down to var(--color-brand-gold)
    // in Tailwind v4) rather than one-by-one, since it's used across dozens of
    // buttons/links/badges throughout the site — header/body/footer are each a
    // single element, so those are just styled directly.
    $themeHeaderColor = $settings->get('theme_header_color') ?: '#0b2545';
    $themeBodyColor = $settings->get('theme_body_color') ?: '#f9fafb';
    $themeFooterColor = $settings->get('theme_footer_color') ?: '#ffffff';
    $themeAccentColor = $settings->get('theme_accent_color') ?: '#d4af37';
    $lightenHex = function (string $hex, float $percent) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#e8c766';
        }
        [$r, $g, $b] = array_map(fn ($channel) => hexdec($channel), str_split($hex, 2));
        $mix = fn ($channel) => (int) min(255, $channel + ((255 - $channel) * $percent));

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    };
    $themeAccentColorLight = $lightenHex($themeAccentColor, 0.25);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $siteName }} — {{ $settings->get('footer_text', 'Pusat pengelolaan dokumen resmi perguruan tinggi.') }}">
    @if ($favicon = $settings->get('site_favicon'))
        <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($favicon) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --color-brand-gold: {{ $themeAccentColor }};
            --color-brand-gold-light: {{ $themeAccentColorLight }};
        }
    </style>
</head>
<body class="flex min-h-screen flex-col font-sans antialiased" style="background-color: {{ $themeBodyColor }};">
    {{--
        Always sticky + solid — never floats transparently over the hero below
        it. That "fixed overlay" treatment used to make the hero's own content
        (search bar, buttons) collide visually with the header; keeping the
        header in normal flow means the slider always starts cleanly below it.
    --}}
    <header
        x-data="{ mobileOpen: false }"
        class="sticky inset-x-0 top-0 z-40 border-b border-white/10 shadow-lg shadow-brand-navy/20"
        style="background-color: {{ $themeHeaderColor }};">
        <div class="h-1 bg-gradient-to-r from-brand-gold via-brand-gold-light to-brand-gold"></div>

        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-white">
                @if ($siteLogo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteLogo) }}" alt="{{ $siteName }}" class="h-12 w-12 rounded-lg object-cover shadow-sm">
                @else
                    <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand-gold text-base font-bold text-brand-navy shadow-sm">DMS</span>
                @endif
                <span class="hidden text-lg font-bold tracking-tight sm:inline">{{ $siteName }}</span>
                <span class="text-lg font-bold tracking-tight sm:hidden">DMS</span>
            </a>

            {{-- Desktop links --}}
            <nav class="hidden items-center gap-6 text-sm font-medium text-white/80 lg:flex">
                @foreach ($headerMenu as $item)
                    @if ($item->children->isNotEmpty())
                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <button type="button" class="flex items-center gap-1 transition hover:text-brand-gold">
                                {{ $item->label }}
                                <x-heroicon-o-chevron-down class="h-3.5 w-3.5" />
                            </button>
                            <div x-show="open" x-transition x-cloak class="absolute left-0 top-full z-10 mt-1 w-56 rounded-lg bg-white py-2 shadow-lg ring-1 ring-gray-950/5">
                                @foreach ($item->children as $child)
                                    <a href="{{ $child->url }}" @if ($child->open_in_new_tab) target="_blank" rel="noopener" @endif
                                        class="block px-4 py-2 text-sm text-gray-600 hover:bg-brand-navy/5 hover:text-brand-navy">
                                        {{ $child->label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->url }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
                            class="transition hover:text-brand-gold {{ $isActiveMenu($item->url) || ($item->url === '/' && $isHome) ? 'text-brand-gold' : '' }}">
                            {{ $item->label }}
                        </a>
                    @endif
                @endforeach
                <a href="{{ route('documents.search') }}" class="text-white/60 transition hover:text-brand-gold" aria-label="Pencarian">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                </a>
            </nav>

            <div class="hidden items-center gap-4 text-sm lg:flex">
                @auth
                    @if (auth()->user()->hasAnyRole(array_map(fn ($r) => $r->value, \App\Enums\UserRole::panelRoles())))
                        <a href="{{ url('/admin') }}" class="font-medium text-white/80 hover:text-brand-gold">Panel Admin</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-1.5 font-medium text-white/80 hover:text-brand-gold">
                        <x-heroicon-o-user-circle class="h-5 w-5" />
                        {{ \Illuminate\Support\Str::limit(auth()->user()->name, 16) }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-white/20 px-3 py-1.5 font-medium text-white/80 hover:border-brand-gold hover:text-brand-gold">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-brand-gold px-4 py-2 font-semibold text-brand-navy shadow-sm transition hover:bg-brand-gold-light">Masuk</a>
                @endauth
            </div>

            {{-- Mobile toggle — `-m-2 p-2` grows the tap target to ~44px without
                 changing the icon's visual size or the row's layout. --}}
            <button type="button" @click="mobileOpen = !mobileOpen" class="-m-2 p-2 text-white lg:hidden" aria-label="Buka menu">
                <x-heroicon-o-bars-3 class="h-7 w-7" x-show="!mobileOpen" />
                <x-heroicon-o-x-mark class="h-7 w-7" x-show="mobileOpen" x-cloak />
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileOpen" x-cloak x-transition
            class="space-y-1 border-t border-white/10 px-4 py-3 text-sm font-medium text-white/85 lg:hidden"
            style="background-color: {{ $themeHeaderColor }};">
            @foreach ($headerMenu as $item)
                @if ($item->children->isNotEmpty())
                    <p class="px-2 py-2 text-xs font-semibold tracking-wide text-white/50 uppercase">{{ $item->label }}</p>
                @else
                    <a href="{{ $item->url }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif
                        class="block rounded px-2 py-2 hover:bg-white/5 hover:text-brand-gold">
                        {{ $item->label }}
                    </a>
                @endif
                @foreach ($item->children as $child)
                    <a href="{{ $child->url }}" class="block rounded px-2 py-2 pl-6 text-white/70 hover:bg-white/5 hover:text-brand-gold">
                        {{ $child->label }}
                    </a>
                @endforeach
            @endforeach
            <a href="{{ route('documents.search') }}" class="block rounded px-2 py-2 hover:bg-white/5 hover:text-brand-gold">Pencarian</a>
            <div class="mt-2 border-t border-white/10 pt-2">
                @auth
                    @if (auth()->user()->hasAnyRole(array_map(fn ($r) => $r->value, \App\Enums\UserRole::panelRoles())))
                        <a href="{{ url('/admin') }}" class="block rounded px-2 py-2 hover:bg-white/5 hover:text-brand-gold">Panel Admin</a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="block rounded px-2 py-2 hover:bg-white/5 hover:text-brand-gold">Profil ({{ auth()->user()->name }})</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded px-2 py-2 text-left hover:bg-white/5 hover:text-brand-gold">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block rounded px-2 py-2 font-semibold text-brand-gold">Masuk</a>
                @endauth
            </div>
        </div>
    </header>

    @yield('hero')

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-gray-200" style="background-color: {{ $themeFooterColor }};">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        @if ($siteLogo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-9 rounded-lg object-cover">
                        @else
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-navy text-sm font-bold text-white">DMS</span>
                        @endif
                        <span class="font-semibold text-brand-navy">{{ $siteTagline }}</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-500">
                        {{ $settings->get('footer_text', 'Pusat pengelolaan dokumen resmi perguruan tinggi — terstruktur, aman, dan mudah ditelusuri.') }}
                    </p>
                    @php
                        $socialLinks = collect(['social_facebook' => 'globe-alt', 'social_instagram' => 'camera', 'social_twitter' => 'at-symbol', 'social_youtube' => 'play-circle'])
                            ->mapWithKeys(fn ($icon, $key) => [$key => ['icon' => $icon, 'url' => $settings->get($key)]])
                            ->filter(fn ($item) => filled($item['url']));
                    @endphp
                    @if ($socialLinks->isNotEmpty())
                        <div class="mt-4 flex items-center gap-3">
                            @foreach ($socialLinks as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-brand-navy">
                                    <x-dynamic-component :component="'heroicon-o-'.$social['icon']" class="h-5 w-5" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Navigasi</p>
                    <ul class="mt-3 space-y-2 text-sm text-gray-500">
                        @foreach ($footerMenu as $item)
                            <li><a href="{{ $item->url }}" class="hover:text-brand-navy">{{ $item->label }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Kontak</p>
                    <ul class="mt-3 space-y-2 text-sm text-gray-500">
                        @if ($email = $settings->get('contact_email'))
                            <li class="flex items-center gap-1.5"><x-heroicon-o-envelope class="h-4 w-4 shrink-0" /> {{ $email }}</li>
                        @endif
                        @if ($phone = $settings->get('contact_phone'))
                            <li class="flex items-center gap-1.5"><x-heroicon-o-phone class="h-4 w-4 shrink-0" /> {{ $phone }}</li>
                        @endif
                        @if ($address = $settings->get('contact_address'))
                            <li class="flex items-start gap-1.5"><x-heroicon-o-map-pin class="h-4 w-4 shrink-0" /> {{ $address }}</li>
                        @endif
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Keaslian Dokumen</p>
                    <p class="mt-3 flex items-start gap-2 text-sm text-gray-500">
                        <x-heroicon-o-qr-code class="h-5 w-5 shrink-0 text-brand-gold" />
                        Pindai kode QR pada dokumen resmi untuk memverifikasi keasliannya kapan saja.
                    </p>
                </div>
            </div>
            <div class="mt-10 flex flex-col items-center justify-between gap-2 border-t border-gray-100 pt-6 text-xs text-gray-400 sm:flex-row">
                <p>&copy; {{ date('Y') }} {{ $siteTagline }}. Seluruh hak cipta dilindungi.</p>
                <p class="flex items-center gap-1.5">
                    <x-heroicon-o-shield-check class="h-4 w-4 text-brand-gold" />
                    Dikelola dengan {{ $siteName }}
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
