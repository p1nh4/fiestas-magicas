<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reservations\Tables;

use App\Enums\ReservationStatus;
use App\Models\Item;
use App\Models\Reservation;
use App\Support\Availability\AvailabilityService;
use App\Support\Availability\OutOfStockException;
use App\Support\Period;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale')
                    ->label('Sale')
                    ->state(fn (Reservation $record): string => $record->period->start->format('d/m/Y H:i'))
                    ->description(fn (Reservation $record): string => 'vuelve '.$record->period->end->format('d/m H:i')),
                TextColumn::make('item.name')
                    ->label('Pieza')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('quantity')
                    ->label('Uds.')
                    ->numeric()
                    ->alignEnd(),
                /*
                | Uma reserva ou pertence a uma festa, ou é um bloqueio
                | manual com motivo. O CHECK da base de dados garante que é
                | sempre uma das duas — nunca nenhuma. Aqui mostra-se a que
                | for, na mesma coluna, porque é sempre a resposta à mesma
                | pergunta: "porque é que esta peça está ocupada?".
                */
                TextColumn::make('porque')
                    ->label('Motivo')
                    ->state(fn (Reservation $record): string => $record->event?->title
                        ?? $record->blocked_reason
                        ?? '—')
                    ->description(fn (Reservation $record): ?string => $record->event?->reference)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('blocked_reason', 'ilike', "%{$search}%")
                        ->orWhereHas('event', fn (Builder $q) => $q->where('title', 'ilike', "%{$search}%"))),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('item')
                    ->label('Pieza')
                    ->relationship('item', 'sku')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ReservationStatus::class),
                Filter::make('proximas')
                    ->label('Solo las que aún no han salido')
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->whereRaw('upper(period) >= now()')),
                Filter::make('bloqueos')
                    ->label('Solo bloqueos manuales')
                    ->query(fn (Builder $query): Builder => $query->whereNull('event_id')),
            ])
            ->headerActions([
                static::availabilityCheck(),
                static::manualBlock(),
            ])
            ->recordActions([
                /*
                | Cancelar e não apagar.
                |
                | O índice que sustenta o controlo de stock ignora as
                | canceladas, portanto cancelar liberta as unidades na
                | mesma. A diferença é que fica lá o rasto: numa discussão
                | sobre porque é que faltaram cadeiras num sábado, uma linha
                | apagada não ajuda ninguém.
                */
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Liberar esta reserva')
                    ->modalDescription('Las unidades vuelven a estar disponibles para esas fechas. La reserva no se borra: queda marcada como cancelada.')
                    ->action(function (Reservation $record): void {
                        $record->update(['status' => ReservationStatus::Cancelled]);

                        Notification::make()->success()->title('Reserva cancelada')->send();
                    })
                    ->visible(fn (Reservation $record): bool => $record->status !== ReservationStatus::Cancelled),
            ])
            ->emptyStateHeading('No hay material reservado')
            ->emptyStateDescription('Las reservas se crean solas cuando un cliente acepta un presupuesto.');
    }

    /**
     * "Tenho 40 cadeiras livres a 12 de maio?"
     *
     * É a pergunta que a Sol faz ao telefone, com o cliente à espera. Não
     * responde nada que a base de dados não confirme: usa o mesmo cálculo
     * que o trigger usa para recusar.
     */
    private static function availabilityCheck(): Action
    {
        return Action::make('disponibilidad')
            ->label('¿Está libre?')
            ->icon('heroicon-o-magnifying-glass')
            ->color('primary')
            ->modalHeading('Comprobar disponibilidad')
            ->modalDescription('Cuenta las unidades libres en ese intervalo, ya con los márgenes de montaje y limpieza de la pieza.')
            ->modalSubmitActionLabel('Comprobar')
            ->schema(fn (): array => static::windowFields())
            ->action(function (array $data): void {
                $item = Item::find($data['item_id']);

                if ($item === null) {
                    return;
                }

                $service = app(AvailabilityService::class);

                $usage = new Period(
                    CarbonImmutable::parse($data['starts_at']),
                    CarbonImmutable::parse($data['ends_at']),
                );

                // A janela realmente bloqueada é maior do que a festa: leva
                // as folgas de transporte, montagem e limpeza da peça.
                $window = $service->blockedWindow($item, $usage);
                $free = $service->availableQuantity($item, $window);
                $wanted = (int) ($data['quantity'] ?? 1);

                $margin = $item->buffer_before_min > 0 || $item->buffer_after_min > 0
                    ? ' (bloqueada de '.$window->start->format('d/m H:i').' a '.$window->end->format('d/m H:i').', con márgenes)'
                    : '';

                if ($free >= $wanted) {
                    Notification::make()
                        ->success()
                        ->title("Sí: quedan {$free} de {$item->stock_qty}")
                        ->body($item->name.$margin)
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->warning()
                    ->title($free === 0 ? 'No queda ninguna' : "Solo quedan {$free} de {$wanted}")
                    ->body($item->name.$margin)
                    ->persistent()
                    ->send();
            });
    }

    /** Bloquear uma peça sem festa: partida, em limpeza, emprestada. */
    private static function manualBlock(): Action
    {
        return Action::make('bloquear')
            ->label('Bloquear pieza')
            ->icon('heroicon-o-lock-closed')
            ->color('gray')
            ->modalHeading('Bloquear una pieza')
            ->modalDescription('Para cuando una pieza no está disponible por algo que no es una fiesta: rota, en limpieza, prestada.')
            ->modalSubmitActionLabel('Bloquear')
            ->schema(fn (): array => [
                ...static::windowFields(withQuantity: true),
                TextInput::make('blocked_reason')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(160)
                    ->helperText('Lo verás tú en la lista. Ej.: "photocall roto, en reparación".'),
            ])
            ->action(function (array $data): void {
                $item = Item::find($data['item_id']);

                if ($item === null) {
                    return;
                }

                try {
                    app(AvailabilityService::class)->reserve(
                        item: $item,
                        window: new Period(
                            CarbonImmutable::parse($data['starts_at']),
                            CarbonImmutable::parse($data['ends_at']),
                        ),
                        quantity: (int) ($data['quantity'] ?? 1),
                        blockedReason: $data['blocked_reason'],
                    );

                    Notification::make()->success()->title('Pieza bloqueada')->send();
                } catch (OutOfStockException $e) {
                    // O trigger recusou: já não havia unidades livres. A
                    // mensagem vem do serviço, que sabe quantas restam.
                    Notification::make()
                        ->danger()
                        ->title('No se puede bloquear')
                        ->body($e->forHumans())
                        ->persistent()
                        ->send();
                }
            });
    }

    /**
     * Peça + intervalo. Partilhado pelos dois modais, para as duas
     * perguntas se fazerem exatamente da mesma maneira.
     *
     * @return array<int, mixed>
     */
    public static function windowFields(bool $withQuantity = true): array
    {
        $fields = [
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
                ->required(),
            DateTimePicker::make('starts_at')
                ->label('Desde')
                ->seconds(false)
                ->displayFormat('d/m/Y H:i')
                ->required(),
            DateTimePicker::make('ends_at')
                ->label('Hasta')
                ->seconds(false)
                ->displayFormat('d/m/Y H:i')
                ->required()
                ->after('starts_at'),
        ];

        if ($withQuantity) {
            $fields[] = TextInput::make('quantity')
                ->label('Unidades')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required();
        }

        return $fields;
    }
}
