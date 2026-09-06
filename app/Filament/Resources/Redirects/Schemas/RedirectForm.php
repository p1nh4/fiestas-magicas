<?php

declare(strict_types=1);

namespace App\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('from_path')
                    ->label('Dirección antigua')
                    ->required()
                    ->maxLength(400)
                    ->unique(ignoreRecord: true)
                    ->prefix(config('app.url'))
                    ->helperText('Empieza por /. Ej.: /es/photocall-antiguo'),
                TextInput::make('to_path')
                    ->label('Lleva a')
                    ->required()
                    ->maxLength(400)
                    ->prefix(config('app.url'))
                    ->helperText('Empieza por /. Ej.: /es/alquiler/photocall-floral'),
                Select::make('status_code')
                    ->label('Tipo')
                    ->options([
                        301 => '301 — la mudanza es definitiva',
                        302 => '302 — es temporal',
                    ])
                    ->default(301)
                    ->required()
                    ->native(false)
                    // O 301 e o que transfere a posicao no Google. O 302
                    // diz "isto volta", e o Google mantem a pagina antiga
                    // indexada — util para uma campanha, mau para uma
                    // mudanca a serio.
                    ->helperText('Usa 301 salvo que la página vaya a volver.'),
            ]);
    }
}
