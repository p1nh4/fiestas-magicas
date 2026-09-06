<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventDesigns\Schemas;

use App\Models\Item;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EventDesignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('La fiesta')
                    ->columns(2)
                    ->schema([
                        // Um evento só pode ter um projeto — o UNIQUE da base
                        // de dados garante-o. Aqui o `unique` avisa antes,
                        // para o erro não vir do Postgres.
                        Select::make('event_id')
                            ->label('Evento')
                            ->relationship('event', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Cada fiesta tiene un proyecto, no dos.'),
                        TextInput::make('theme')
                            ->label('Tema')
                            ->maxLength(120)
                            ->datalist(['Sirenas', 'Fútbol', 'Bosque encantado', 'Safari', 'Boho', 'Espacio'])
                            ->helperText('Como se lo cuentas a la clienta.'),
                    ]),

                Section::make('La idea')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Cómo lo imaginas')
                            ->rows(6)
                            ->columnSpanFull(),

                        // Cores como repeater e não como texto livre: uma
                        // paleta escrita à mão vira "rosa palo, dorado" e
                        // deixa de servir para nada.
                        Repeater::make('palette')
                            ->label('Paleta')
                            ->addActionLabel('Añadir color')
                            ->simple(
                                ColorPicker::make('color')->required(),
                            )
                            ->columnSpanFull(),

                        Repeater::make('inspiration')
                            ->label('Referencias')
                            ->addActionLabel('Añadir enlace')
                            ->simple(
                                TextInput::make('url')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://instagram.com/p/...'),
                            )
                            ->columnSpanFull(),

                        FileUpload::make('photos')
                            ->label('Fotos de referencia')
                            ->multiple()
                            ->image()
                            ->reorderable()
                            ->disk('public')
                            ->directory('proyectos')
                            ->maxSize(8192)
                            ->columnSpanFull()
                            ->helperText('Lo que le enseñas a la clienta para que se imagine la fiesta.'),
                    ]),

                Section::make('Material previsto')
                    ->description('Lo que piensas llevar. Todavía no reserva nada: el material se aparta cuando la clienta acepta el presupuesto.')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->addActionLabel('Añadir pieza')
                            ->columns(6)
                            ->itemLabel(fn (array $state): ?string => filled($state['item_id'] ?? null)
                                ? Item::find($state['item_id'])?->name
                                : null)
                            ->schema([
                                Select::make('item_id')
                                    ->label('Pieza')
                                    ->options(fn (): array => Item::query()
                                        ->where('is_active', true)
                                        ->orderBy('sku')
                                        ->get()
                                        ->mapWithKeys(fn (Item $i): array => [
                                            $i->getKey() => $i->name.' ('.$i->stock_qty.')',
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    // A mesma peça duas vezes no mesmo
                                    // projeto é sempre um engano: o que se
                                    // queria era mudar a quantidade.
                                    ->distinct()
                                    ->columnSpan(3),
                                TextInput::make('quantity')
                                    ->label('Uds.')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('notes')
                                    ->label('Nota')
                                    ->maxLength(200)
                                    ->columnSpan(2)
                                    ->helperText('Si la escribes, es lo que verá la clienta en el presupuesto.'),
                            ]),
                    ]),

                Section::make('Montaje')
                    ->description('Los pasos del día. Marcarlos aquí es para ti, no para la clienta.')
                    ->collapsed()
                    ->schema([
                        Repeater::make('checklist')
                            ->hiddenLabel()
                            ->addActionLabel('Añadir paso')
                            ->columns(6)
                            ->itemLabel(fn (array $state): ?string => $state['step'] ?? null)
                            ->schema([
                                TextInput::make('step')
                                    ->label('Paso')
                                    ->required()
                                    ->maxLength(200)
                                    ->columnSpan(5),
                                Checkbox::make('done')
                                    ->label('Hecho')
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}
