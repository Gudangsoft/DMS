<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\MathCaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view. A fresh captcha challenge is generated on every
     * visit — including the redirect-back-with-errors after a failed
     * attempt, since that's a normal GET to this same action.
     */
    public function create(): View
    {
        return view('auth.login', [
            'captcha' => MathCaptcha::generate('login'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi Super Admin.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPathFor($user));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Arahkan user ke dashboard sesuai role (poin 4 spesifikasi): role back-office
     * (Super Admin, Admin Dokumen, Staff/Uploader, Reviewer/Approver) masuk ke panel
     * Filament; Viewer/User tetap di beranda frontend publik.
     */
    protected function redirectPathFor(mixed $user): string
    {
        $panelRoleNames = array_map(fn (UserRole $role) => $role->value, UserRole::panelRoles());

        return $user->hasAnyRole($panelRoleNames) ? '/admin' : '/';
    }
}
