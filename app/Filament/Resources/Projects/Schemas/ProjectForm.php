<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\EventType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('La fiesta')
                    ->columns(3)
                    ->schema([
                        Select::make('event_id')
                            ->label('Evento')
                            ->relationship('event', 'title')
                            ->searchable()
                            ->preload()
                            ->columnSpan(2)
                            ->helperText('Opcional. Enlazarlo trae el cliente y las fechas.'),
                        Select::make('event_type')
                            ->label('Tipo')
                            ->options(EventType::class)
                            ->required()
                            ->native(false),
                        DatePicker::make('happened_on')
                            ->label('Cuándo fue')
                            ->displayFormat('d/m/Y'),
                        TextInput::make('venue')
                            ->label('Sitio')
                            ->maxLength(200),
                        // A localidade tem de bater CERTO com o nome da zona
                        // para o trabalho aparecer na página desse concelho.
                        TextInput::make('city')
                            ->label('Localidad')
                            ->maxLength(120)
                            ->helperText('Escríbela igual que en Zonas: así el trabajo sale en la página de ese concejo.'),
                        TextInput::make('guests_count')
                            ->label('Invitados')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('position')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_featured')
                            ->label('Destacado en la portada'),
                    ]),

                Tabs::make('Idiomas')
                    ->columnSpanFull()
                    ->tabs([
                        static::languageTab('Español', 'es', required: true),
                        static::languageTab('Galego', 'gl'),
                        static::languageTab('Português', 'pt'),
                    ]),

                /*
                | Consentimento.
                |
                | Publicar a festa de alguém é publicar a casa dela, os
                | convidados dela e, muitas vezes, os filhos dela. O CHECK
                | da base de dados recusa publicar sem data de
                | consentimento — não é uma formalidade que se contorna com
                | pressa numa tarde.
                */
                Section::make('Permiso del cliente')
                    ->description('Sin permiso escrito no se publica. Guarda la fecha en que te lo dio: es la prueba.')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('consent_at')
                            ->label('Nos dio permiso el')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Vacío = no se puede publicar. La base de datos lo rechaza.'),
                        DateTimePicker::make('published_at')
                            ->label('Publicado el')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()),
                        Toggle::make('is_published')
                            ->label('Visible en la web'),
                    ]),
            ]);
    }

    private static function languageTab(string $label, string $locale, bool $required = false): Tab
    {
        return Tab::make($label)
            ->schema([
                TextInput::make("title.{$locale}")
                    ->label('Título')
                    ->required($required)
                    ->maxLength(160),
                Textarea::make("description.{$locale}")
                    ->label('Descripción')
                    ->rows(5)
                    ->helperText('Cuenta qué montasteis y cómo. Es lo que Google lee.'),
                TextInput::make("slug.{$locale}")
                    ->label('Dirección en la web')
                    ->maxLength(160)
                    ->helperText('Vacío = se genera solo.'),
            ]);
    }
}
