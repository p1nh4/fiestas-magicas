<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EventStatus;
use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * As próximas festas, por ordem de quando acontecem.
 *
 * Inclui os rascunhos de propósito. Uma festa em rascunho a três semanas de
 * distância é exatamente aquilo em que é preciso pensar — se só aparecessem
 * as confirmadas, o painel dava uma falsa sensação de calma.
 */
class ProximasFiestasWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lo que viene')
            ->description('Las próximas ocho, incluidas las que aún están en borrador.')
            ->query(
                Event::query()
                    ->with('client')
                    ->whereNot('status', EventStatus::Cancelled->value)
                    ->where('starts_at', '>=', now()->startOfDay())
                    ->orderBy('starts_at')
                    ->limit(8)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Cuándo')
                    ->dateTime('d/m H:i')
                    ->description(fn (Event $record): string => $record->starts_at->diffForHumans()),
                TextColumn::make('title')
                    ->label('Fiesta')
                    ->weight('medium')
                    ->description(fn (Event $record): ?string => $record->client?->name),
                TextColumn::make('venue_city')
                    ->label('Dónde')
                    ->placeholder('—')
                    ->description(fn (Event $record): ?string => $record->venue_name),
                TextColumn::make('pendiente')
                    ->label('Pendiente')
                    ->alignEnd()
                    ->state(fn (Event $record): string => $record->balanceDue())
                    ->money('EUR')
                    ->color(fn (Event $record): string => bccomp($record->balanceDue(), '0.00', 2) > 0 ? 'warning' : 'success'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('abrir')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-right')
                    ->color('gray')
                    ->url(fn (Event $record): string => EventResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No hay nada en la agenda')
            ->emptyStateDescription('Las fiestas aparecen aquí en cuanto conviertes una solicitud.');
    }
}
