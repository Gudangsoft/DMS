<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
