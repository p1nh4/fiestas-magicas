<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    /**
     * Os botoes vivem na tabela (headerActions) e nao aqui, porque precisam
     * do contexto da tabela. Sem botao de criar: uma reserva nasce de um
     * orcamento aceite, nao a mao.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
