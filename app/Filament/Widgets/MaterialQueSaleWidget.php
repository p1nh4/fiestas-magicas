<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * O que sai da garagem nos próximos sete dias.
 *
 * É a lista para carregar a carrinha, e é a única no painel que responde a
 * uma pergunta física em vez de comercial. Inclui os bloqueios manuais —
 * uma peça partida também "sai" do stock, e é melhor descobri-lo aqui do
 * que na véspera.
 */
class MaterialQueSaleWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Material que sale esta semana')
            ->description('Ya con los márgenes de montaje: la pieza se aparta antes de la hora de la fiesta.')
            ->query(
                Reservation::query()
                    ->with(['item', 'event'])
                    ->where('status', ReservationStatus::Confirmed->value)
                    ->whereRaw("lower(period) between now() and now() + interval '7 days'")
                    ->orderByRaw('lower(period) asc')
                    ->limit(12)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('sale')
                    ->label('Sale')
                    ->state(fn (Reservation $record): string => $record->period->start->format('d/m H:i'))
                    ->description(fn (Reservation $record): string => 'vuelve '.$record->period->end->format('d/m H:i')),
                TextColumn::make('item.name')
                    ->label('Pieza')
                    ->weight('medium'),
                TextColumn::make('quantity')
                    ->label('Uds.')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('porque')
                    ->label('Para')
                    ->state(fn (Reservation $record): string => $record->event?->title
                        ?? $record->blocked_reason
                        ?? '—')
                    ->description(fn (Reservation $record): ?string => $record->event?->venue_city),
            ])
            ->emptyStateHeading('Esta semana no sale nada')
            ->emptyStateDescription('Aquí aparece el material reservado para los próximos siete días.');
    }
}
