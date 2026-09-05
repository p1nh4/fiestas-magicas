<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceAreas\Tables;

use App\Models\ServiceArea;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ServiceAreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Localidad')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (ServiceArea $record): ?string => $record->province),
                TextColumn::make('country')
                    ->label('País')
                    ->badge()
                    ->color(fn (ServiceArea $record): string => $record->isPortugal() ? 'info' : 'gray'),
                TextColumn::make('distance_km')
                    ->label('Desde Baiona')
                    ->state(fn (ServiceArea $record): ?string => $record->distanceLabel())
                    ->placeholder('—')
                    ->alignEnd()
                    ->sortable(),
                /*
                | A coluna que faz o trabalho todo.
                |
                | Em vez de deixar a Sol carregar em "publicar" e levar com um
                | erro do Postgres que ninguem percebe, diz-lhe em cada linha
                | quanto texto falta. A regra e a mesma dos dois lados — ha um
                | teste que verifica que o numero aqui e o numero do CHECK.
                */
                TextColumn::make('estado_texto')
                    ->label('Texto')
                    ->badge()
                    ->state(function (ServiceArea $record): string {
                        $written = mb_strlen(trim((string) $record->getTranslation('intro', 'es', false)));

                        if ($written === 0) {
                            return 'sin escribir';
                        }

                        if ($written < ServiceArea::MIN_INTRO) {
                            return 'faltan '.(ServiceArea::MIN_INTRO - $written).' car.';
                        }

                        return 'listo';
                    })
                    ->color(fn (string $state): string => $state === 'listo' ? 'success' : 'warning'),
                IconColumn::make('is_published')
                    ->label('En la web')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Visible en la web'),
                SelectFilter::make('country')
                    ->label('País')
                    ->options(['ES' => 'España', 'PT' => 'Portugal']),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (ServiceArea $record): string => route('areas.show', [
                        'locale' => 'es',
                        'slug' => $record->getTranslation('slug', 'es', false),
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn (ServiceArea $record): bool => $record->is_published
                        && filled($record->getTranslation('slug', 'es', false))),
                EditAction::make()->label('Editar'),
            ])
            ->emptyStateHeading('No hay zonas')
            ->emptyStateDescription('Se siembran con el ServiceAreaSeeder y luego se escriben una a una.');
    }
}
