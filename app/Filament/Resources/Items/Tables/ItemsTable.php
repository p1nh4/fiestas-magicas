<?php

declare(strict_types=1);

namespace App\Filament\Resources\Items\Tables;

use App\Models\Item;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Pieza')
                    ->weight('medium'),
                TextColumn::make('stock_qty')
                    ->label('Unidades')
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->color(fn (Item $record): string => $record->stock_qty > 0 ? 'gray' : 'danger'),
                TextColumn::make('price_per_day')
                    ->label('Por día')
                    ->alignEnd()
                    ->state(fn (Item $record): ?string => (float) $record->price_per_day > 0
                        ? number_format((float) $record->price_per_day, 2, ',', '.').' €'
                        : null)
                    ->placeholder('a consultar'),
                TextColumn::make('buffer_after_min')
                    ->label('Margen')
                    ->state(fn (Item $record): string => $record->buffer_before_min.' / '.$record->buffer_after_min.' min')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('requires_transport')
                    ->label('Furgoneta')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_rentable')
                    ->label('Suelta')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('En uso')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('En uso'),
                TernaryFilter::make('is_rentable')->label('Se alquila suelta'),
                TernaryFilter::make('requires_transport')->label('Necesita furgoneta'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->emptyStateHeading('No hay material')
            ->emptyStateDescription('Aquí van las piezas físicas: sillas, photocalls, letras, soportes…');
    }
}
