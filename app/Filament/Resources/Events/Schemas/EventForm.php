<?php

declare(strict_types=1);

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\Locale;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('La fiesta')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull()
                            ->helperText('Para ti, no para el cliente. Ej.: "Comunión · Marta Pérez".'),
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('event_type')
                            ->label('Tipo')
                            ->options(EventType::class)
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->label('Estado')
                            ->options(EventStatus::class)
                            ->default(EventStatus::Draft->value)
                            ->required()
                            ->native(false),
                        Select::make('locale')
                            ->label('Idioma del cliente')
                            ->options(Locale::class)
                            ->default(Locale::Es->value)
                            ->required()
                            ->native(false),
                        TextInput::make('guests_count')
                            ->label('Invitados')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('reference')
                            ->label('Referencia')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Se genera sola: FM-año-número.'),
                    ]),

                Section::make('Horario')
                    ->description('El montaje y el desmontaje son los que bloquean el material, no la hora de la fiesta.')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Empieza la fiesta')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label('Termina la fiesta')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->required()
                            ->after('starts_at'),
                        DateTimePicker::make('setup_starts_at')
                            ->label('Empieza el montaje')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->beforeOrEqual('starts_at'),
                        DateTimePicker::make('teardown_ends_at')
                            ->label('Termina el desmontaje')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->afterOrEqual('ends_at'),
                    ]),

                Section::make('Dónde')
                    ->columns(2)
                    ->schema([
                        TextInput::make('venue_name')
                            ->label('Sitio')
                            ->maxLength(200),
                        TextInput::make('venue_city')
                            ->label('Localidad')
                            ->maxLength(120),
                        TextInput::make('venue_address')
                            ->label('Dirección')
                            ->maxLength(300)
                            ->columnSpanFull(),
                        TextInput::make('distance_km')
                            ->label('Distancia (km)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.1')
                            ->helperText('Para calcular el desplazamiento.'),
                    ]),

                Section::make('Notas')
                    ->columns(2)
                    ->schema([
                        Textarea::make('notes')
                            ->label('Lo que pidió el cliente')
                            ->rows(4),
                        Textarea::make('internal_notes')
                            ->label('Notas internas')
                            ->rows(4)
                            ->helperText('El cliente nunca ve esto.'),
                    ]),

                // Os totais NAO se escrevem a mao: vem do orcamento aceite e
                // dos pagamentos. Editaveis, davam numeros que nao batem com
                // as linhas — e e sempre a linha que tem razao.
                Section::make('Dinero')
                    ->description('Calculado a partir del presupuesto aceptado y de los pagos. No se toca a mano.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_amount')
                            ->label('Total')
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('deposit_amount')
                            ->label('Señal')
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('paid_amount')
                            ->label('Pagado')
                            ->prefix('€')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
