<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->minLength(8)
                    ->helperText('Kosongkan jika tidak ingin mengubah password.'),
                Select::make('unit_id')
                    ->label('Unit Kerja')
                    ->options(fn () => Unit::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
                TextInput::make('position')
                    ->label('Jabatan')
                    ->maxLength(255),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Akun Aktif')
                    ->default(true),
            ]);
    }
}
