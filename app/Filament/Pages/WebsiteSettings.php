<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Poin tambahan — "Pengaturan Web": identitas situs, logo, kontak, dan tautan
 * sosial media, disimpan lewat key-value store Setting (bukan model Eloquent
 * biasa, karena tidak ada banyak baris/relasi — cukup satu form global).
 */
class WebsiteSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Pengaturan Web';

    protected static ?string $title = 'Pengaturan Web';

    protected string $view = 'filament.pages.website-settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(Setting::query()->pluck('value', 'key')->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Pengaturan')
                    ->tabs([
                        Tab::make('Identitas Situs')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('site_name')
                                            ->label('Nama Situs')
                                            ->required()
                                            ->columnSpanFull(),
                                        TextInput::make('site_tagline')
                                            ->label('Tagline')
                                            ->columnSpanFull(),
                                        FileUpload::make('site_logo')
                                            ->label('Logo')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings'),
                                        FileUpload::make('site_favicon')
                                            ->label('Favicon')
                                            ->image()
                                            ->disk('public')
                                            ->directory('settings'),
                                        TextInput::make('footer_text')
                                            ->label('Teks Footer')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Kontak')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('contact_email')
                                            ->label('Email')
                                            ->email(),
                                        TextInput::make('contact_phone')
                                            ->label('Telepon'),
                                        TextInput::make('contact_address')
                                            ->label('Alamat')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Warna Tema')
                            ->schema([
                                Section::make()
                                    ->description('Mengatur warna latar header (navbar), body (latar halaman), dan footer di website publik. Kosongkan untuk memakai warna bawaan (navy & emas).')
                                    ->columns(2)
                                    ->components([
                                        ColorPicker::make('theme_header_color')
                                            ->label('Warna Header')
                                            ->default('#0b2545'),
                                        ColorPicker::make('theme_body_color')
                                            ->label('Warna Body (Latar Halaman)')
                                            ->default('#f9fafb'),
                                        ColorPicker::make('theme_footer_color')
                                            ->label('Warna Footer')
                                            ->default('#ffffff'),
                                        ColorPicker::make('theme_accent_color')
                                            ->label('Warna Aksen (Tombol & Sorotan)')
                                            ->default('#d4af37')
                                            ->helperText('Dipakai untuk tombol utama, link aktif, dan sorotan di seluruh halaman.'),
                                    ]),
                            ]),
                        Tab::make('Media Sosial')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('social_facebook')
                                            ->label('Facebook')
                                            ->url()
                                            ->prefixIcon('heroicon-o-globe-alt'),
                                        TextInput::make('social_instagram')
                                            ->label('Instagram')
                                            ->url()
                                            ->prefixIcon('heroicon-o-globe-alt'),
                                        TextInput::make('social_twitter')
                                            ->label('Twitter / X')
                                            ->url()
                                            ->prefixIcon('heroicon-o-globe-alt'),
                                        TextInput::make('social_youtube')
                                            ->label('YouTube')
                                            ->url()
                                            ->prefixIcon('heroicon-o-globe-alt'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Pengaturan berhasil disimpan')
            ->success()
            ->send();
    }
}
