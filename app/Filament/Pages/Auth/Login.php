<?php

namespace App\Filament\Pages\Auth;

use App\Support\MathCaptcha;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Poin tambahan — captcha matematika sederhana ("berapa hasil dari 4 + 7?")
 * di panel admin, sama seperti login publik. Angka disimpan di properti
 * Livewire (bukan cuma session) supaya tampilan soal ikut ter-refresh saat
 * jawaban salah, tanpa perlu reload halaman penuh.
 */
class Login extends BaseLogin
{
    /**
     * @var array{a: int, b: int}
     */
    public array $mathCaptcha = ['a' => 0, 'b' => 0];

    public function mount(): void
    {
        parent::mount();

        $this->regenerateCaptcha();
    }

    protected function regenerateCaptcha(): void
    {
        $this->mathCaptcha = MathCaptcha::generate('admin_login');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getCaptchaFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getCaptchaFormComponent(): Component
    {
        return TextInput::make('captcha')
            ->label(fn () => "Berapa hasil dari {$this->mathCaptcha['a']} + {$this->mathCaptcha['b']}?")
            ->numeric()
            ->required()
            ->autocomplete('off');
    }

    public function authenticate(): ?LoginResponse
    {
        if (! MathCaptcha::verify('admin_login', $this->form->getState()['captcha'] ?? null)) {
            $this->regenerateCaptcha();

            throw ValidationException::withMessages([
                'data.captcha' => 'Jawaban perhitungan salah. Silakan coba lagi.',
            ]);
        }

        try {
            return parent::authenticate();
        } finally {
            // Wrong credentials still consume the challenge — give a fresh
            // one either way (success, failure, or multi-factor step) so a
            // stale answer can never be resubmitted. `finally` runs even
            // when parent::authenticate() throws.
            $this->regenerateCaptcha();
        }
    }
}
