<?php

declare(strict_types=1);

namespace App\Filament\Resources\Items\Schemas;

use App\Enums\CategoryKind;
use App\Models\Item;
use App\Support\Availability\AvailabilityService;
use App\Support\Period;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('La pieza')
                    ->columns(3)
                    ->schema([
                        TextInput::make('sku')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(48)
                            ->helperText('Corto y en mayúsculas: SILLAS-TIFFANY.'),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship(
                                'category',
                                'id',
                                fn ($query) => $query->where('kind', CategoryKind::Item->value)
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->preload(),
                        TextInput::make('stock_qty')
                            ->label('Cuántas tienes')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default(1)
                            /*
                             * O trigger `items_stock_guard` recusa baixar o
                             * stock abaixo do que ja esta reservado, e este
                             * era o unico caminho que nao passava pelo
                             * AvailabilityService — o unico sitio que traduz
                             * o 23514 para linguagem humana. Partiam-se dez
                             * cadeiras, mudava-se 40 para 30, e o que
                             * aparecia era SQLSTATE.
                             */
                            ->rule(fn (?Item $record) => function (string $attribute, $value, Closure $fail) use ($record) {
                                if ($record === null) {
                                    return;
                                }

                                $reservado = app(AvailabilityService::class)->peakDemand(
                                    $record,
                                    new Period(CarbonImmutable::now(), CarbonImmutable::now()->addYears(2)),
                                );

                                if ((int) $value < $reservado) {
                                    $fail("Ahora mismo tienes {$reservado} reservadas para fiestas ya confirmadas. Para bajar de ahí, cancela antes esas reservas.");
                                }
                            })
                            ->helperText('Este número es el que impide reservar de más. No es decorativo.'),
                    ]),

                Tabs::make('Idiomas')
                    ->columnSpanFull()
                    ->tabs([
                        static::languageTab('Español', 'es', required: true),
                        static::languageTab('Galego', 'gl'),
                        static::languageTab('Português', 'pt'),
                    ]),

                Section::make('Alquiler')
                    ->columns(3)
                    ->schema([
                        TextInput::make('price_per_day')
                            ->label('Precio por día')
                            ->prefix('€')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('replacement_value')
                            ->label('Valor de reposición')
                            ->prefix('€')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Lo que cuesta reponerla si vuelve rota.'),
                        Toggle::make('requires_transport')
                            ->label('Necesita furgoneta'),
                        Toggle::make('is_rentable')
                            ->label('Se alquila suelta')
                            ->default(true)
                            ->helperText('Apagado: solo va dentro de un servicio.'),
                        Toggle::make('is_active')
                            ->label('En uso')
                            ->default(true),
                    ]),

                // As folgas sao o que evita prometer a mesma peca a duas
                // festas do mesmo fim de semana. Estao aqui em minutos
                // porque e assim que a base de dados as usa.
                Section::make('Margen de logística')
                    ->description('Tiempo antes y después en el que la pieza no puede estar en otra fiesta: transporte, montaje y limpieza.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('buffer_before_min')
                            ->label('Antes (minutos)')
                            ->numeric()
                            ->minValue(0)
                            ->default(120)
                            ->required(),
                        TextInput::make('buffer_after_min')
                            ->label('Después (minutos)')
                            ->numeric()
                            ->minValue(0)
                            ->default(1440)
                            ->required()
                            ->helperText('1440 = un día entero.'),
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
                Textarea::make("description.{$locale}")
                    ->label('Descripción')
                    ->rows(3),
                TextInput::make("slug.{$locale}")
                    ->label('Dirección en la web')
                    ->maxLength(120)
                    ->helperText('Vacío = se genera solo.'),
            ]);
    }
}
