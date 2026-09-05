<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotes\Schemas;

use App\Models\Item;
use App\Models\Quote;
use App\Models\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

/**
 * Um orçamento enviado não se edita — cria-se a versão seguinte.
 *
 * Essa regra já está no QuoteBuilder, que atira uma exceção. Aqui repete-se
 * no ecrã (`disabled`) para que a Sol nunca chegue a bater na exceção: uma
 * regra que só aparece como erro depois de escrever tudo é uma regra mal
 * apresentada.
 */
class QuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        $locked = static fn (?Quote $record): bool => $record !== null && ! $record->isEditable();

        return $schema
            ->components([
                Section::make('Presupuesto')
                    ->columns(3)
                    ->schema([
                        Select::make('event_id')
                            ->label('Evento')
                            ->relationship('event', 'title')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->columnSpan(2),
                        TextInput::make('version')
                            ->label('Versión')
                            ->disabled()
                            ->dehydrated(false),
                        DatePicker::make('valid_until')
                            ->label('Válido hasta')
                            ->displayFormat('d/m/Y')
                            ->disabled($locked)
                            ->helperText('Pasada esta fecha el cliente ya no puede aceptarlo.'),
                        TextInput::make('tax_rate')
                            ->label('IVA %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->disabled($locked),
                        TextInput::make('deposit_pct')
                            ->label('Señal %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->disabled($locked)
                            ->helperText('Lo que se paga por adelantado para bloquear la fecha.'),
                    ]),

                Section::make('Líneas')
                    ->description('Elige del catálogo y el precio se rellena solo. Puedes cambiarlo: lo que quede escrito aquí es lo que verá el cliente, aunque el catálogo cambie mañana.')
                    ->schema([
                        // Seis colunas, tres filas certinhas: catalogo em cima,
                        // descricao no meio a toda a largura, numeros em baixo.
                        // A primeira versao tinha 12 colunas e campos que
                        // somavam 22 — rebentavam para a linha seguinte
                        // espremidos e nao se lia nada.
                        Repeater::make('lines')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->addActionLabel('Añadir línea')
                            ->disabled($locked)
                            ->collapsible()
                            ->cloneable()
                            ->columns(6)
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->schema([
                                Select::make('service_id')
                                    ->label('Servicio')
                                    ->options(fn (): array => Service::query()
                                        ->where('is_active', true)
                                        ->orderBy('position')
                                        ->get()
                                        ->mapWithKeys(fn (Service $s): array => [$s->getKey() => $s->name])
                                        ->all())
                                    ->searchable()
                                    ->live()
                                    ->columnSpan(3)
                                    ->helperText('Del catálogo de servicios.')
                                    // Escolher do catálogo preenche descrição e
                                    // preço, mas não os prende: a partir daqui
                                    // são texto e número desta linha.
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        $service = $state !== null ? Service::find($state) : null;
                                        if ($service !== null) {
                                            $set('item_id', null);
                                            $set('description', (string) $service->name);
                                            $set('unit_price', (string) $service->base_price);
                                            $set('days', 1);
                                        }
                                    }),
                                Select::make('item_id')
                                    ->label('Material')
                                    ->options(fn (): array => Item::query()
                                        ->where('is_active', true)
                                        ->orderBy('sku')
                                        ->get()
                                        ->mapWithKeys(fn (Item $i): array => [$i->getKey() => $i->name])
                                        ->all())
                                    ->searchable()
                                    ->live()
                                    ->columnSpan(3)
                                    ->helperText('O una pieza de alquiler.')
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        $item = $state !== null ? Item::find($state) : null;
                                        if ($item !== null) {
                                            $set('service_id', null);
                                            $set('description', (string) $item->name);
                                            $set('unit_price', (string) $item->price_per_day);
                                        }
                                    }),
                                TextInput::make('description')
                                    ->label('Concepto')
                                    ->required()
                                    ->maxLength(300)
                                    ->columnSpanFull()
                                    ->helperText('Es lo que lee el cliente. Puedes escribirlo a mano sin elegir nada arriba.'),
                                TextInput::make('quantity')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step('0.01')
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('days')
                                    ->label('Días')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('unit_price')
                                    ->label('Precio')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(2),
                                // Só para ver. O total real é recalculado em
                                // bcmath ao gravar — ver EditQuote.
                                TextInput::make('line_total')
                                    ->label('Total línea')
                                    ->prefix('€')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Totales')
                    ->description('Se recalculan al guardar. No se escriben a mano.')
                    ->columns(4)
                    ->schema([
                        TextInput::make('discount_amount')
                            ->label('Descuento')
                            ->prefix('€')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled($locked),
                        TextInput::make('subtotal')->label('Subtotal')->prefix('€')->disabled()->dehydrated(false),
                        TextInput::make('tax_amount')->label('IVA')->prefix('€')->disabled()->dehydrated(false),
                        TextInput::make('total')->label('Total')->prefix('€')->disabled()->dehydrated(false),
                    ]),

                Section::make('Nota para el cliente')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes.es')
                            ->label('Nota')
                            ->rows(3)
                            ->disabled($locked)
                            ->helperText('Aparece al final del presupuesto que ve el cliente.'),
                    ]),
            ]);
    }
}
