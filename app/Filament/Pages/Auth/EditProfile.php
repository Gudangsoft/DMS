<?php

namespace App\Filament\Pages\Auth;

use App\Models\Unit;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Poin tambahan — profil admin lengkap: foto profil dengan crop lingkaran ala
 * WhatsApp (imageEditor + circleCropper), jabatan, dan unit kerja, di samping
 * field bawaan Filament (nama/email/password).
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Foto Profil')
                    ->description('Pilih gambar, lalu geser dan perbesar/kecilkan untuk memotong seperti pada WhatsApp.')
                    ->components([
                        FileUpload::make('avatar')
                            ->label(null)
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(4096),
                    ]),

                Section::make('Informasi Akun')
                    ->columns(2)
                    ->components([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        TextInput::make('position')
                            ->label('Jabatan')
                            ->maxLength(255),
                        Select::make('unit_id')
                            ->label('Unit Kerja')
                            ->options(fn () => Unit::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Hubungi Super Admin untuk mengubah unit kerja.'),
                        Placeholder::make('roles_display')
                            ->label('Peran')
                            ->columnSpanFull()
                            ->content(fn () => $this->getUser()->roles->pluck('name')->implode(', ') ?: '-'),
                    ]),

                Section::make('Keamanan')
                    ->description('Kosongkan jika tidak ingin mengubah password.')
                    ->columns(2)
                    ->components([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
