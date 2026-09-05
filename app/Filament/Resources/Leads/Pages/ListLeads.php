<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    /**
     * Sem botao de criar. Um lead e, por definicao, um pedido que veio do
     * site. Um pedido que chega por telefone e um cliente e um evento — se
     * se pudesse escrever leads a mao, os numeros de marketing deixavam de
     * querer dizer alguma coisa.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
