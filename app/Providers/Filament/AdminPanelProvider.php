<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // Closures so branding is read fresh from the Setting store on every
            // request instead of being baked in once at boot — same source of
            // truth as the public site's header/footer (poin: "Pengaturan Web"
            // berlaku di seluruh sistem, bukan hanya frontend publik).
            ->brandName(fn () => Setting::get('site_name', 'DMS Kasih Bangsa'))
            // Filament's <x-filament::logo> only ever renders ONE of
            // {image, brand name text} — as soon as brandLogo() resolves to
            // something, the brand name text is dropped entirely. To show the
            // logo AND the name side by side, brandLogo() itself has to return
            // the combined markup (it accepts Htmlable, not just an image URL).
            ->brandLogo(function () {
                $logo = Setting::get('site_logo');
                $name = e(Setting::get('site_name', 'DMS Kasih Bangsa'));

                if (! $logo) {
                    return null;
                }

                $url = Storage::disk('public')->url($logo);

                return new HtmlString(
                    '<span style="display:flex;align-items:center;gap:.65rem;height:100%;">'
                    .'<img src="'.e($url).'" alt="'.$name.'" style="height:100%;width:auto;border-radius:.5rem;object-fit:cover;">'
                    .'<span style="font-weight:700;font-size:1.0625rem;line-height:1;white-space:nowrap;">'.$name.'</span>'
                    .'</span>'
                );
            })
            ->brandLogoHeight('2.75rem')
            ->favicon(function () {
                $favicon = Setting::get('site_favicon');

                return $favicon ? Storage::disk('public')->url($favicon) : asset('favicon.ico');
            })
            ->login(Login::class)
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->databaseNotifications()
            // Panel-wide default: every resource's Create/Edit form goes back to
            // the list after saving, instead of Filament's default of staying on
            // the same form — matches how admins actually expect this to behave,
            // and covers every resource without needing a per-page override.
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->colors([
                'primary' => Color::hex('#0B2545'),
                'gray' => Color::Slate,
                'warning' => Color::hex('#D4AF37'),
            ])
            // A single "Kembali" button registered here applies to every resource's
            // list/create/edit/view page panel-wide, instead of adding a header
            // action to each resource's page class one by one.
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
                fn () => view('filament.partials.back-button'),
            )
            ->navigationGroups([
                'Document Management',
                'Approval',
                'Master Data',
                'Report',
                'User Management',
                'System',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
