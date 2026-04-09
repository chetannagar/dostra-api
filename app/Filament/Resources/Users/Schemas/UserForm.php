<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(8),

                        Select::make('role')
                            ->required()
                            ->options([
                                'super_user' => 'Super User',
                                'admin' => 'Admin',
                                'client' => 'Client',
                            ])
                            ->native(false)
                            ->live(),

                        CheckboxList::make('clients')
                            ->relationship('clients', 'name')
                            ->columns(2)
                            ->visible(fn (Get $get): bool => $get('role') === 'client'),
                    ])
                    ->columns(2),
            ]);
    }
}
