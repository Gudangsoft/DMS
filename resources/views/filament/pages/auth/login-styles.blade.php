{{-- Scoped to App\Filament\Pages\Auth\Login only (see AdminPanelProvider's
     BODY_END render hook scope) — restyles the stock Filament simple-page
     chrome to match the public site's navy/gold branding without touching the
     form schema, captcha, or authenticate() logic. --}}
<script>
    // This branded login screen is always light — Filament's dark: utility
    // classes (field labels, helper text, etc.) would otherwise turn
    // unreadable against the white card forced below. Livewire re-applies
    // the 'dark' class on <html> at more than one point during its own
    // hydration/navigate lifecycle (not just the inline loadDarkMode() call
    // in <head>), at times this script can't reliably run after just by
    // ordering render hooks — so instead of a one-shot removal, this watches
    // the class attribute for the rest of the page's life and strips 'dark'
    // the instant anything re-adds it. Only affects the DOM for this page;
    // the stored 'theme' preference is untouched, so the rest of the panel
    // keeps the user's choice after they sign in.
    const stripDarkClass = () => {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
        }
    };
    stripDarkClass();
    new MutationObserver(stripDarkClass).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
    document.addEventListener('livewire:navigated', stripDarkClass);
</script>
<style>
    .fi-simple-layout {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background: linear-gradient(180deg, #13315c 0%, #0b2545 55%, #071a33 100%) !important;
    }

    .fi-simple-layout::before,
    .fi-simple-layout::after {
        content: '';
        position: absolute;
        width: 22rem;
        height: 22rem;
        border-radius: 9999px;
        background: rgba(212, 175, 55, 0.16);
        filter: blur(90px);
        pointer-events: none;
    }

    .fi-simple-layout::before {
        top: -6rem;
        right: -6rem;
    }

    .fi-simple-layout::after {
        bottom: -6rem;
        left: -6rem;
    }

    .fi-simple-main-ctn,
    .fi-simple-page,
    .fi-simple-page-content {
        position: relative;
    }

    .fi-simple-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .fi-logo {
        border-radius: 0.75rem;
    }

    /* Only the plain-text fallback (no logo uploaded via Pengaturan Web) gets the
       gold badge treatment — a div wrapping an uploaded <img> keeps its own look
       from AdminPanelProvider::brandLogo() untouched, just with a subtle ring. */
    .fi-logo:has(img) {
        box-shadow: 0 0 0 1px rgba(11, 37, 69, 0.08), 0 10px 20px -10px rgba(11, 37, 69, 0.35) !important;
    }

    .fi-logo:not(:has(img)) {
        background: linear-gradient(135deg, #d4af37, #e8c766) !important;
        color: #0b2545 !important;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 0.85rem;
        box-shadow: 0 10px 20px -10px rgba(212, 175, 55, 0.6) !important;
    }

    /* The header (logo/heading/subheading) renders INSIDE .fi-simple-main, i.e.
       on the white card — not on the navy page background — so text stays dark. */
    .fi-simple-header-heading {
        color: #0b2545 !important;
        font-size: 1.375rem !important;
        font-weight: 700 !important;
        margin-top: 0.25rem;
    }

    .fi-simple-header-subheading {
        color: #64748b !important;
        font-size: 0.875rem !important;
    }

    .fi-simple-main {
        position: relative;
        overflow: hidden;
        background: #fff !important;
        border-radius: 1.25rem !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
        border: none !important;
        animation: fi-login-card-in 0.5s ease-out;
    }

    .fi-simple-main::before {
        content: '';
        display: block;
        height: 0.375rem;
        background: linear-gradient(90deg, #d4af37, #e8c766, #d4af37);
    }

    html.dark .fi-simple-main {
        background: #fff !important;
    }

    html.dark .fi-simple-header-heading {
        color: #0b2545 !important;
    }

    html.dark .fi-simple-header-subheading {
        color: #64748b !important;
    }

    .fi-simple-main input[type='email'],
    .fi-simple-main input[type='password'],
    .fi-simple-main input[type='number'] {
        border-radius: 0.65rem !important;
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
    }

    .fi-simple-main button[type='submit'] {
        border-radius: 0.65rem !important;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .fi-simple-main button[type='submit']:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px -8px rgba(11, 37, 69, 0.5);
    }

    @keyframes fi-login-card-in {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
