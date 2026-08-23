<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Filesystem\LocalFilesystemAdapter as IlluminateLocalFilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Flysystem's Local adapter instantiates a Finfo-backed MimeTypeDetector
        // in its constructor unconditionally — not lazily on first use — so on
        // a host without ext-fileinfo, just resolving Storage::disk('documents')
        // (or any 'local' disk) throws "Class finfo not found" before a single
        // byte is written. This app already stores each version's mime type
        // explicitly from a known extension allowlist (DocumentVersionService::
        // ALLOWED_MIME_TYPES) rather than relying on the disk to sniff it, so an
        // extension-only detector — no finfo involved — is a safe full
        // replacement, not just a fallback. Registered as its own driver name
        // (config/filesystems.php disks use 'driver' => 'local_no_finfo') so
        // the stock 'local' driver stays untouched for any disk that does want
        // real content-sniffing.
        //
        // Otherwise mirrors Illuminate\Filesystem\FilesystemManager::createLocalDriver()
        // exactly — same visibility/lock/links config handling, same
        // Illuminate\Filesystem\LocalFilesystemAdapter wrapper (needed for
        // temporaryUrl() on disks with 'serve' => true) — right down to
        // diskName(), which Storage::extend()'s closure isn't handed by
        // Laravel, so filesystems.php stashes it as '_disk_name' on each disk
        // config that opts into this driver.
        Storage::extend('local_no_finfo', function ($app, array $config) {
            $visibility = PortableVisibilityConverter::fromArray(
                $config['permissions'] ?? [],
                $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE,
            );

            $adapter = new LocalFilesystemAdapter(
                $config['root'],
                $visibility,
                $config['lock'] ?? LOCK_EX,
                ($config['links'] ?? null) === 'skip' ? LocalFilesystemAdapter::SKIP_LINKS : LocalFilesystemAdapter::DISALLOW_LINKS,
                new ExtensionMimeTypeDetector(),
            );

            return (new IlluminateLocalFilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config))
                ->diskName($config['_disk_name'] ?? '')
                ->shouldServeSignedUrls($config['serve'] ?? false, fn () => $app['url']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin bypasses every Policy check — matches "Memiliki akses penuh
        // ke seluruh sistem" (poin 1). Keeps individual policies free of
        // repetitive `|| $user->hasRole('Super Admin')` checks.
        Gate::before(fn (User $user, string $ability) => $user->hasRole(UserRole::SuperAdmin->value) ? true : null);

        // Spatie's Role/Permission models live outside App\Models, so Laravel's
        // convention-based policy discovery won't find App\Policies\RolePolicy
        // automatically — it must be registered explicitly.
        Gate::policy(Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(Permission::class, \App\Policies\PermissionPolicy::class);
        Gate::policy(\Spatie\Activitylog\Models\Activity::class, \App\Policies\AuditLogPolicy::class);

        // Poin 19/20 — Login/Logout are the only audit-trail events that don't
        // originate from an Eloquent model, so they're logged manually here.
        Event::listen(function (Login $event) {
            activity('auth')
                ->causedBy($event->user)
                ->withProperties(['ip_address' => request()->ip()])
                ->log('login');
        });

        Event::listen(function (Logout $event) {
            if (! $event->user) {
                return;
            }

            activity('auth')
                ->causedBy($event->user)
                ->withProperties(['ip_address' => request()->ip()])
                ->log('logout');
        });
    }
}
