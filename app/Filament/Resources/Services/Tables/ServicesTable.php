<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Tables;

use App\Enums\PriceMode;
use App\Models\Service;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Servicio')
                    ->weight('medium')
                    ->description(fn (Service $record): ?string => $record->summary),
                TextColumn::make('price_mode')
                    ->label('Cobro')
                    ->badge(),
                // A 0 mostra-se "a consultar" — e o que o site tambem faz.
                // Um "0,00 EUR" numa lista de precos e sempre um engano.
                TextColumn::make('base_price')
                    ->label('Desde')
                    ->alignEnd()
                    ->state(fn (Service $record): ?string => (float) $record->base_price > 0
                        ? number_format((float) $record->base_price, 2, ',', '.').' €'
                        : null)
                    ->placeholder('a consultar'),
                IconColumn::make('is_active')
                    ->label('En la web')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('price_mode')
                    ->label('Cobro')
                    ->options(PriceMode::class),
                TernaryFilter::make('is_active')
                    ->label('Visible en la web'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->emptyStateHeading('No hay servicios')
            ->emptyStateDescription('Lo que añadas aquí aparece en la web.');
    }
}
