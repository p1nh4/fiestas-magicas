<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificador')
                    ->columns(2)
                    ->schema([
                        TextInput::make('key')
                            ->label('Clave')
                            ->required()
                            ->maxLength(48)
                            ->unique(ignoreRecord: true)
                            // A key e o que o codigo procura. Muda-la parte
                            // o link do rodape e o do formulario.
                            ->disabled(fn (?\App\Models\Page $record): bool => $record !== null)
                            ->helperText('No se cambia una vez creada: el código busca la página por esta clave.'),
                        Toggle::make('is_published')
                            ->label('Visible en la web')
                            ->helperText('Revisa que no quede ningún PENDIENTE antes de publicarla.'),
                    ]),

                Tabs::make('Idiomas')
                    ->columnSpanFull()
                    ->tabs([
                        static::languageTab('Español', 'es', required: true),
                        static::languageTab('Galego', 'gl'),
                        static::languageTab('Português', 'pt'),
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
                TextInput::make("slug.{$locale}")
                    ->label('Dirección en la web')
                    ->maxLength(160)
                    ->helperText('Vacío = se genera del título.'),
                Textarea::make("body.{$locale}")
                    ->label('Texto')
                    ->required($required)
                    ->rows(22)
                    ->helperText('Línea en blanco = párrafo nuevo. "## " al principio de una línea la convierte en título. "- " hace una lista.'),
            ]);
    }
}
