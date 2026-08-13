{{--
    Reusable header slider — used on the homepage (full, with CTA buttons) and
    as a compact page banner on /documents, /categories, etc.

    Expected variables:
    - $sliders        Collection<Slider> (active, ordered)
    - $compact        bool — shorter banner, no CTA buttons (default false)
    - $fallbackTitle  string — shown when there are no active slides
    - $fallbackSubtitle string|null
    - $eyebrow        string|null — small uppercase label above the title (non-compact only)
--}}
@php
    // The header is always `sticky` (normal flow, never overlapping), so the
    // slider simply starts right below it — no extra top padding needed to
    // clear anything.
    //
    // Fixed `h-` (not `min-h-`) on the slide itself — not just its inner content —
    // so every slide renders at the exact same height regardless of how much text
    // it holds. With the fade effect, Swiper takes each `.swiper-slide` out of
    // normal flow (position: absolute), so only an explicit height here keeps
    // slides from jumping in size as the autoplay cycles between them.
    // Non-compact (homepage) fills the viewport below the sticky header (~80px:
    // 4px gold bar + ~76px logo row — the logo is h-12 now), so the hero always
    // reads as "full page" regardless of screen size, with a floor so very
    // short viewports don't collapse the content.
    $compact ??= false;
    $eyebrow ??= null;
    $heightClass = $compact ? 'h-[220px] sm:h-[260px]' : 'h-[calc(100vh-80px)] min-h-[420px]';
@endphp

@if ($sliders->isNotEmpty())
    <div class="hero-slider group relative w-full overflow-hidden shadow-xl {{ $heightClass }}" data-compact="{{ $compact ? '1' : '0' }}">
        <div class="swiper-wrapper">
            @foreach ($sliders as $slide)
                <div class="swiper-slide {{ $heightClass }}">
                    @if ($compact)
                        {{-- Compact page-banner mode keeps the original text-over-photo
                             treatment — there's no room for a separate text bar at this
                             height. `cover` fills edge-to-edge, no letterboxing. --}}
                        <div class="relative flex h-full items-center justify-center overflow-y-auto px-8 py-10 text-center sm:px-16"
                            style="{{ $slide->image ? "background-color:#0b2545;background-repeat:no-repeat;background-position:center;background-size:contain;background-image:linear-gradient(to bottom, rgba(11,37,69,.45), rgba(11,37,69,.8)), url('".\Illuminate\Support\Facades\Storage::disk('public')->url($slide->image)."');" : 'background:linear-gradient(135deg, #0b2545 0%, #13315c 55%, #071a33 100%);' }}">
                            <div class="mx-auto max-w-2xl text-white">
                                <h1 class="mx-auto text-2xl sm:text-3xl font-bold">{{ $slide->title ?: $fallbackTitle }}</h1>
                                @if ($slide->subtitle)
                                    <p class="mx-auto mt-3 max-w-xl text-white/80">{{ $slide->subtitle }}</p>
                                @elseif ($fallbackSubtitle ?? null)
                                    <p class="mx-auto mt-3 max-w-xl text-white/80">{{ $fallbackSubtitle }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        {{-- Photo and text no longer share one overlaid box: the photo
                             gets a clean, uncluttered section on top (`flex-1`, so it
                             absorbs whatever height the text bar below doesn't use —
                             keeping every slide's *total* height identical for Swiper's
                             fade effect), and the copy sits in a solid-navy bar
                             underneath instead of stacked on top of the image. --}}
                        <div class="flex h-full flex-col">
                            {{-- `background-size: contain` (not `cover`) — `cover` crops
                                 whichever edge doesn't match the box's aspect ratio, which
                                 was cutting people/objects off the top or bottom depending
                                 on the photo. `contain` always shows the whole image,
                                 letterboxed with the navy background color on the sides
                                 that don't fill — that matches the solid-navy text bar
                                 below, so it reads as intentional rather than empty space. --}}
                            <div class="min-h-0 flex-1"
                                style="{{ $slide->image ? "background-color:#0b2545;background-repeat:no-repeat;background-position:center;background-size:contain;background-image:url('".\Illuminate\Support\Facades\Storage::disk('public')->url($slide->image)."');" : 'background:linear-gradient(135deg, #0b2545 0%, #13315c 55%, #071a33 100%);' }}">
                            </div>

                            <div class="bg-brand-navy px-8 py-5 text-center text-white sm:px-16">
                                {{-- Wide enough for the subtitle to sit on one line on
                                     desktop instead of wrapping to two — a shorter text
                                     bar leaves more of the fixed slide height for the
                                     photo above it (that area is `flex-1`). Still wraps
                                     naturally on narrow screens; nothing forces it. --}}
                                <div class="mx-auto max-w-6xl">
                                    {{-- Title and tagline on one line, same size — the tagline
                                         used to sit above as a small uppercase label; now it's
                                         just a gold-colored continuation of the same heading. --}}
                                    <h1 class="mx-auto text-2xl sm:text-3xl font-bold">
                                        {{ $slide->title ?: $fallbackTitle }}
                                        @if ($eyebrow)
                                            <span class="text-brand-gold">— {{ $eyebrow }}</span>
                                        @endif
                                    </h1>
                                    @if ($slide->subtitle)
                                        <p class="mt-2 text-sm text-white/80">{{ $slide->subtitle }}</p>
                                    @elseif ($fallbackSubtitle ?? null)
                                        <p class="mt-2 text-sm text-white/80">{{ $fallbackSubtitle }}</p>
                                    @endif

                                    <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                                        @if ($slide->button_text && $slide->button_url)
                                            <a href="{{ $slide->button_url }}"
                                                class="rounded-md bg-brand-gold px-5 py-2.5 text-sm font-semibold text-brand-navy hover:bg-brand-gold-light">
                                                {{ $slide->button_text }}
                                            </a>
                                        @else
                                            <a href="{{ route('documents.index') }}"
                                                class="rounded-md bg-brand-gold px-5 py-2.5 text-sm font-semibold text-brand-navy hover:bg-brand-gold-light">
                                                Jelajahi Dokumen
                                            </a>
                                        @endif
                                        @auth
                                            @if (auth()->user()->hasAnyRole(array_map(fn ($r) => $r->value, \App\Enums\UserRole::panelRoles())))
                                                <a href="{{ url('/admin') }}"
                                                    class="rounded-md border border-white/30 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10">
                                                    Buka Panel Admin
                                                </a>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($sliders->count() > 1)
            <button type="button" class="hero-slider-prev absolute top-1/2 left-4 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white opacity-0 backdrop-blur transition hover:bg-white/30 group-hover:opacity-100 sm:flex" aria-label="Sebelumnya">&larr;</button>
            <button type="button" class="hero-slider-next absolute top-1/2 right-4 z-10 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/20 text-white opacity-0 backdrop-blur transition hover:bg-white/30 group-hover:opacity-100 sm:flex" aria-label="Berikutnya">&rarr;</button>
            <div class="hero-slider-pagination absolute inset-x-0 bottom-4 z-10 flex justify-center gap-1.5"></div>
        @endif
    </div>

    @vite(['resources/js/hero-slider.js'])
@else
    <div class="flex {{ $heightClass }} w-full items-center justify-center bg-brand-navy px-8 py-10 text-center text-white shadow-xl sm:px-16">
        <div class="mx-auto max-w-6xl">
            <h1 class="mx-auto text-2xl sm:text-3xl font-bold">
                {{ $fallbackTitle }}
                @if (!$compact && $eyebrow)
                    <span class="text-brand-gold">— {{ $eyebrow }}</span>
                @endif
            </h1>
            @if ($fallbackSubtitle ?? null)
                <p class="mx-auto mt-3 max-w-xl text-white/80">{{ $fallbackSubtitle }}</p>
            @endif

            @unless ($compact)
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('documents.index') }}"
                        class="rounded-md bg-brand-gold px-5 py-2.5 text-sm font-semibold text-brand-navy hover:bg-brand-gold-light">
                        Jelajahi Dokumen
                    </a>
                </div>
            @endunless
        </div>
    </div>
@endif
