<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\EventType;
use App\Enums\LeadStatus;
use App\Enums\Locale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * O que o cliente escreveu não se edita.
 *
 * Quase tudo aqui está em modo leitura de propósito. Se a Sol corrigir a
 * data que a pessoa pediu, deixa de haver forma de saber o que a pessoa
 * pediu. A correção faz-se no evento, que é o sítio onde as coisas mudam.
 *
 * Editável só o que é trabalho interno: o estado e o motivo de perda.
 */
class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lo que pidió')
                    ->description('Tal como llegó del formulario. No se edita.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->disabled(),
                        Select::make('locale')
                            ->label('Idioma')
                            ->options(Locale::class)
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->disabled(),
                        Select::make('event_type')
                            ->label('Tipo de celebración')
                            ->options(EventType::class)
                            ->disabled(),
                        DatePicker::make('event_date')
                            ->label('Fecha deseada')
                            ->displayFormat('d/m/Y')
                            ->disabled(),
                        TextInput::make('guests_count')
                            ->label('Invitados')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('venue')
                            ->label('Lugar')
                            ->disabled(),
                        TextInput::make('budget_hint')
                            ->label('Presupuesto orientativo')
                            ->disabled(),
                        Textarea::make('message')
                            ->label('Mensaje')
                            ->rows(4)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('Seguimiento')
                    ->description('Esto sí es tuyo: en qué punto está la solicitud.')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options(LeadStatus::class)
                            ->required()
                            ->native(false),
                        TextInput::make('lost_reason')
                            ->label('Motivo (si se ha perdido)')
                            ->maxLength(120)
                            ->helperText('Saber por qué se pierden es lo que permite perder menos.'),
                        Select::make('client_id')
                            ->label('Cliente asociado')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->helperText('Se rellena solo al convertir la solicitud.'),
                    ]),

                // O rasto de marketing. Fechado por omissão porque no dia a
                // dia não interessa; ao fim do mês, é o que diz se vale a
                // pena continuar a pagar anúncios.
                Section::make('De dónde vino')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        TextInput::make('utm_source')->label('Fuente')->disabled(),
                        TextInput::make('utm_medium')->label('Medio')->disabled(),
                        TextInput::make('utm_campaign')->label('Campaña')->disabled(),
                        TextInput::make('landing_path')
                            ->label('Página de entrada')
                            ->disabled()
                            ->columnSpanFull(),
                        TextInput::make('referrer')
                            ->label('Enlace de origen')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
