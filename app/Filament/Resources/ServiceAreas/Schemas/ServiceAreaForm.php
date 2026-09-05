<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceAreas\Schemas;

use App\Models\ServiceArea;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ServiceAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('El sitio')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Localidad')
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true),
                        TextInput::make('province')
                            ->label('Provincia o distrito')
                            ->maxLength(120),
                        TextInput::make('country')
                            ->label('País')
                            ->required()
                            ->default('ES')
                            ->maxLength(2)
                            ->helperText('ES o PT.'),
                        TextInput::make('distance_km')
                            ->label('Distancia desde Baiona (km)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.1'),
                        TextInput::make('travel_minutes')
                            ->label('Minutos de camino')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('position')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('La página')
                    ->description('Escribe algo que solo sea verdad en esta localidad: dónde has montado allí, qué salones o iglesias conoces, cómo es llegar. Es lo único que hace que la página valga la pena.')
                    ->schema([
                        Tabs::make('Idiomas')
                            ->columnSpanFull()
                            ->tabs([
                                static::languageTab('Español', 'es', required: true),
                                static::languageTab('Galego', 'gl'),
                                static::languageTab('Português', 'pt'),
                            ]),
                    ]),

                Section::make('Publicar')
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Página visible en la web')
                            // O aviso e o mesmo numero do CHECK na base de
                            // dados. Explicar antes vale mais do que a
                            // mensagem de erro do Postgres depois — mas e o
                            // Postgres que decide, e ainda bem.
                            ->helperText(
                                'Hacen falta al menos '.ServiceArea::MIN_INTRO.' caracteres de texto en español. '.
                                'La base de datos rechaza publicarla sin ellos, y con razón: once páginas iguales '.
                                'con el nombre cambiado hacen que Google baje el sitio entero.'
                            ),
                    ]),
            ]);
    }

    private static function languageTab(string $label, string $locale, bool $required = false): Tab
    {
        return Tab::make($label)
            ->schema([
                Textarea::make("intro.{$locale}")
                    ->label('Texto de la página')
                    ->rows(10)
                    ->required($required)
                    ->helperText('Separa los párrafos con una línea en blanco.'),
                TextInput::make("slug.{$locale}")
                    ->label('Dirección en la web')
                    ->maxLength(120)
                    ->helperText('Ej.: nigran. Una vez publicada, cambiarla rompe los enlaces que ya circulan.'),
            ]);
    }
}
