<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventDesigns\Pages;

use App\Filament\Resources\EventDesigns\EventDesignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventDesigns extends ListRecords
{
    protected static string $resource = EventDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo proyecto')];
    }
}
