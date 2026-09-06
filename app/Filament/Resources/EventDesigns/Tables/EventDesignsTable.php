<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventDesigns\Tables;

use App\Models\EventDesign;
use App\Support\Designs\DesignToQuote;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventDesignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event.starts_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('event.title')
                    ->label('Fiesta')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (EventDesign $record): ?string => $record->event?->client?->name),
                TextColumn::make('theme')
                    ->label('Tema')
                    ->searchable()
                    ->placeholder('sin tema'),
                TextColumn::make('items_count')
                    ->label('Piezas')
                    ->counts('items')
                    ->alignEnd(),
                /*
                | Os passos da montagem que ja estao feitos.
                |
                | E a unica coluna que a Sol olha no proprio dia da festa,
                | com o telemovel na mao e as maos ocupadas.
                */
                TextColumn::make('montaje')
                    ->label('Montaje')
                    ->badge()
                    ->state(function (EventDesign $record): string {
                        $total = $record->checklistTotal();

                        return $total === 0
                            ? 'sin pasos'
                            : $record->checklistDone().' de '.$total;
                    })
                    ->color(fn (EventDesign $record): string => $record->checklistTotal() > 0
                        && $record->checklistDone() === $record->checklistTotal()
                            ? 'success'
                            : 'gray'),
            ])
            ->filters([
                Filter::make('proximos')
                    ->label('Solo fiestas que aún no han pasado')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->whereHas(
                        'event',
                        fn (Builder $q) => $q->where('starts_at', '>=', now()->startOfDay()),
                    )),
            ])
            ->recordActions([
                static::checkAvailability(),
                static::pushToQuote(),
                EditAction::make()->label('Abrir'),
            ])
            ->emptyStateHeading('No hay proyectos')
            ->emptyStateDescription('Un proyecto es la idea de una fiesta antes de que sea presupuesto.');
    }

    /**
     * "O material que pensei levar está livre nessas datas?"
     *
     * Usa exatamente o mesmo cálculo que o trigger usa para recusar, e com
     * as folgas de montagem e limpeza incluídas. Não responde nada que a
     * base de dados não confirme.
     */
    private static function checkAvailability(): Action
    {
        return Action::make('disponibilidad')
            ->label('¿Hay material?')
            ->icon('heroicon-o-magnifying-glass')
            ->color('gray')
            ->action(function (EventDesign $record): void {
                $warnings = app(DesignToQuote::class)->checkAvailability($record);

                if ($warnings === []) {
                    Notification::make()
                        ->success()
                        ->title('Está todo libre')
                        ->body('Para las fechas de la fiesta, con los márgenes de montaje incluidos.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->warning()
                    ->title('Falta material')
                    ->body(implode("\n", $warnings))
                    ->persistent()
                    ->send();
            })
            ->visible(fn (EventDesign $record): bool => $record->items()->exists());
    }

    /**
     * O botão que justifica o módulo existir.
     *
     * Sem ele, a lista escrevia-se aqui e depois outra vez no orçamento —
     * duas vezes o mesmo trabalho, e a segunda com a atenção já gasta.
     */
    private static function pushToQuote(): Action
    {
        return Action::make('presupuestar')
            ->label('Pasar al presupuesto')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Pasar el material al presupuesto')
            ->modalDescription('Se añaden las piezas al presupuesto en borrador de esta fiesta. Si el último ya se envió, se crea la versión siguiente en vez de tocarlo.')
            ->modalSubmitActionLabel('Pasar')
            ->action(function (EventDesign $record): void {
                $result = app(DesignToQuote::class)->push($record);

                Notification::make()
                    ->success()
                    ->title($result['added'].' líneas en el presupuesto v'.$result['quote']->version)
                    ->body($result['warnings'] === []
                        ? 'Revisa los precios antes de enviarlo.'
                        : "Ojo, falta material:\n".implode("\n", $result['warnings']))
                    ->persistent()
                    ->send();
            })
            ->visible(fn (EventDesign $record): bool => $record->items()->exists()
                && $record->event !== null);
    }
}
