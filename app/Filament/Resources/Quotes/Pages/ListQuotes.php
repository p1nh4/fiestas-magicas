<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    /**
     * Sem botao de criar. Um orcamento nasce sempre de um evento — e a
     * partir de la que se cria, com a versao certa e com o evento ligado.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
