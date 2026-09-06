<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Idiomas')
                    ->columnSpanFull()
                    ->tabs([
                        static::languageTab('Español', 'es', required: true),
                        static::languageTab('Galego', 'gl'),
                        static::languageTab('Português', 'pt'),
                    ]),

                Section::make('Visibilidad')
                    ->columns(2)
                    ->schema([
                        TextInput::make('position')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_published')
                            ->label('Visible en la web')
                            ->default(true)
                            ->helperText('Si la respuesta todavía no es cierta, déjala apagada.'),
                    ]),
            ]);
    }

    private static function languageTab(string $label, string $locale, bool $required = false): Tab
    {
        return Tab::make($label)
            ->schema([
                TextInput::make("question.{$locale}")
                    ->label('Pregunta')
                    ->required($required)
                    ->maxLength(200),
                Textarea::make("answer.{$locale}")
                    ->label('Respuesta')
                    ->required($required)
                    ->rows(4),
            ]);
    }
}
