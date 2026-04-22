<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
                            ->live(onBlur: true)
                            ->unique(ignoreRecord: true, table: 'clients', column: 'name')
                            ->maxLength(255),

                        TextInput::make('client_key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->readOnly()
                            ->suffixAction(
                                Action::make('generateClientKey')
                                    ->label('Generate client key')
                                    ->icon(Heroicon::ArrowPath)
                                    ->action(function (Get $get, Set $set): void {
                                        $name = trim((string) $get('name'));

                                        if ($name === '') {
                                            return;
                                        }

                                        $set('client_key', Client::generateClientKey($name));
                                    })
                            ),

                        Toggle::make('is_active')
                            ->default(true),

                        TextInput::make('webhook_secret')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->suffixAction(
                                Action::make('generateWebhookSecret')
                                    ->label('Generate webhook secret')
                                    ->icon(Heroicon::ArrowPath)
                                    ->action(function (Set $set): void {
                                        $set('webhook_secret', Client::generateWebhookSecret());
                                    })
                            )
                            ->helperText('Store this securely. Use the dedicated rotation action for operational secret rotation.'),
                    ])
                    ->columns(2),
            ]);
    }
}
