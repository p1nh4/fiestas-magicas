<?php

declare(strict_types=1);

namespace App\Filament\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Support\Quotes\QuoteBuilder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Por ordem de data: a agenda le-se do que vem primeiro para o
            // que vem depois, nao do mais recentemente criado.
            ->defaultSort('starts_at', 'asc')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (Event $record): ?string => $record->setup_starts_at !== null
                        ? 'montaje '.$record->setup_starts_at->format('d/m H:i')
                        : null),
                TextColumn::make('reference')
                    ->label('Ref.')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Fiesta')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Event $record): ?string => $record->client?->name),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('venue_city')
                    ->label('Dónde')
                    ->searchable()
                    ->placeholder('—')
                    ->description(fn (Event $record): ?string => $record->venue_name),
                TextColumn::make('guests_count')
                    ->label('Inv.')
                    ->numeric()
                    ->placeholder('—')
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('EUR')
                    ->alignEnd()
                    ->sortable(),
                // O que falta receber. É a coluna que a Sol vai olhar mais
                // vezes, por isso é calculada e não escrita à mão.
                TextColumn::make('balance')
                    ->label('Pendiente')
                    ->alignEnd()
                    ->state(fn (Event $record): string => $record->balanceDue())
                    ->money('EUR')
                    ->color(fn (Event $record): string => bccomp($record->balanceDue(), '0.00', 2) > 0 ? 'warning' : 'success'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(EventStatus::class)
                    ->multiple(),
                SelectFilter::make('event_type')
                    ->label('Tipo')
                    ->options(EventType::class)
                    ->multiple(),
                Filter::make('upcoming')
                    ->label('Solo próximas')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '>=', now()->startOfDay())),
                Filter::make('unpaid')
                    ->label('Con dinero pendiente')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('paid_amount', '<', 'total_amount')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('presupuestar')
                    ->label('Presupuesto')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Crear un presupuesto en borrador')
                    ->modalDescription('Se crea vacío y con la versión siguiente. Le añades las líneas y luego lo envías.')
                    ->modalSubmitActionLabel('Crear')
                    ->action(function (Event $record): void {
                        $quote = app(QuoteBuilder::class)->draftFor($record);

                        Notification::make()
                            ->success()
                            ->title('Presupuesto v'.$quote->version.' creado')
                            ->body('Está en Presupuestos, en borrador.')
                            ->send();
                    }),
                EditAction::make()->label('Abrir'),
            ])
            ->emptyStateHeading('No hay eventos')
            ->emptyStateDescription('Se crean al convertir una solicitud, o a mano con el botón de arriba.');
    }
}
