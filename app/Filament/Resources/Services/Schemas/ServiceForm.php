<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Schemas;

use App\Enums\CategoryKind;
use App\Enums\PriceMode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Três idiomas em separadores, não três colunas.
 *
 * Em colunas, o formulário fica com nove caixas de texto e ninguém percebe
 * qual é qual. Em separadores, escreve-se um idioma de cada vez — que é
 * como uma pessoa realmente escreve.
 */
class ServiceForm
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

                Section::make('Precio y visibilidad')
                    ->columns(3)
                    ->schema([
                        Select::make('price_mode')
                            ->label('Cómo se cobra')
                            ->options(PriceMode::class)
                            ->default(PriceMode::Quote->value)
                            ->required()
                            ->native(false)
                            ->helperText('"A presupuestar" es lo honesto mientras no haya un precio cerrado.'),
                        TextInput::make('base_price')
                            ->label('Precio base')
                            ->prefix('€')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('A 0, la web muestra "a consultar" en vez de un número inventado.'),
                        TextInput::make('setup_minutes')
                            ->label('Montaje (min)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship(
                                'category',
                                'id',
                                fn ($query) => $query->where('kind', CategoryKind::Service->value)
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->preload(),
                        TextInput::make('position')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->helperText('Menor número, más arriba en la web.'),
                        Toggle::make('is_active')
                            ->label('Visible en la web')
                            ->default(true),
                    ]),
            ]);
    }

    private static function languageTab(string $label, string $locale, bool $required = false): Tab
    {
        return Tab::make($label)
            ->schema([
                TextInput::make("name.{$locale}")
                    ->label('Nombre')
                    ->required($required)
                    ->maxLength(120),
                TextInput::make("summary.{$locale}")
                    ->label('Resumen')
                    ->maxLength(200)
                    ->helperText('Una frase. Es lo que se lee en la tarjeta de la web.'),
                Textarea::make("description.{$locale}")
                    ->label('Descripción')
                    ->rows(5),
                TextInput::make("slug.{$locale}")
                    ->label('Dirección en la web')
                    ->maxLength(120)
                    ->helperText('Déjalo vacío y se genera solo. Una vez publicado, cambiarlo rompe los enlaces que ya circulan.'),
            ]);
    }
}
