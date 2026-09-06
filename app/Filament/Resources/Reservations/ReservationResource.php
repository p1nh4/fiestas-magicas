<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reservations;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\Tables\ReservationsTable;
use App\Models\Reservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Reservas: que peça está ocupada, quando, e por causa de quê.
 *
 * Não há formulário de criação nem de edição, e é de propósito. Uma reserva
 * nasce de um orçamento aceite — nunca à mão — porque é a aceitação que a
 * torna verdadeira. A única exceção é o bloqueio manual (uma peça partida,
 * uma que foi para limpeza), e esse tem botão próprio.
 *
 * Editar uma reserva à mão seria a forma mais rápida de contornar o trigger
 * que impede o overbooking. Não vale a pena dar essa hipótese a ninguém.
 */
class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Trabajo';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'reserva';

    protected static ?string $pluralModelLabel = 'reservas';

    protected static ?string $navigationLabel = 'Material reservado';

    /**
     * Por ordem de quando a peça sai.
     *
     * `lower(period)` e não `created_at`: numa agenda interessa o que vem
     * primeiro, não o que foi escrito primeiro. O `period` é um tstzrange,
     * por isso a ordenação tem de ser em SQL — não há coluna por onde
     * ordenar.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['event', 'item'])->orderByRaw('lower(period) asc');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReservations::route('/'),
        ];
    }

    /** Quantas peças saem nos próximos sete dias. */
    public static function getNavigationBadge(): ?string
    {
        $soon = Reservation::query()
            ->where('status', ReservationStatus::Confirmed->value)
            ->whereRaw('lower(period) between now() and now() + interval \'7 days\'')
            ->count();

        return $soon > 0 ? (string) $soon : null;
    }
}
