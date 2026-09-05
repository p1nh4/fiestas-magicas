<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients\Schemas;

use App\Enums\ClientKind;
use App\Enums\Locale;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quién es')
                    ->columns(2)
                    ->schema([
                        Select::make('kind')
                            ->label('Tipo')
                            ->options(ClientKind::class)
                            ->default(ClientKind::Person->value)
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(160),
                        TextInput::make('legal_name')
                            ->label('Razón social')
                            ->maxLength(200)
                            ->visible(fn (Get $get): bool => $get('kind') === ClientKind::Company->value),
                        TextInput::make('tax_id')
                            ->label('NIF / CIF / NIF-PT')
                            ->maxLength(32),
                        Select::make('locale')
                            ->label('Idioma')
                            ->options(Locale::class)
                            ->default(Locale::Es->value)
                            ->required()
                            ->native(false)
                            ->helperText('En este idioma se le escribe y se le manda el presupuesto.'),
                        TextInput::make('source')
                            ->label('Cómo nos conoció')
                            ->maxLength(24)
                            ->datalist(['instagram', 'facebook', 'web', 'boca_a_boca', 'feria'])
                            ->placeholder('instagram, boca_a_boca…'),
                    ]),

                Section::make('Cómo contactarle')
                    ->description('Hace falta al menos un email o un teléfono.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(180)
                            ->requiredWithout('phone'),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(32)
                            ->requiredWithout('email'),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->maxLength(32),
                    ]),

                Section::make('Dirección')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        TextInput::make('address_line')
                            ->label('Calle')
                            ->maxLength(200)
                            ->columnSpanFull(),
                        TextInput::make('postal_code')->label('CP')->maxLength(16),
                        TextInput::make('city')->label('Localidad')->maxLength(120),
                        TextInput::make('province')->label('Provincia')->maxLength(120),
                        TextInput::make('country')
                            ->label('País')
                            ->default('ES')
                            ->maxLength(2)
                            ->helperText('ES, PT…'),
                    ]),

                Section::make('Notas y permisos')
                    ->columns(2)
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas internas')
                            ->rows(3)
                            ->columnSpanFull(),
                        // RGPD: o consentimento para marketing tem de ter DATA.
                        // Um "sim/não" sozinho não prova nada numa inspeção; a
                        // data prova. Por isso o campo é a data e não um botão:
                        // vazio = não consentiu.
                        DateTimePicker::make('marketing_opt_in_at')
                            ->label('Acepta novedades desde')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Rellénalo solo si te ha dado permiso. Vacío = no se le manda publicidad.'),
                    ]),
            ]);
    }
}
