<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('client_key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Toggle::make('is_active')
                            ->default(true),

                        TextInput::make('webhook_secret')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Store this securely. Use the dedicated rotation action for operational secret rotation.'),
                    ])
                    ->columns(2),
            ]);
    }
}
