<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventDesigns\Pages;

use App\Filament\Resources\EventDesigns\EventDesignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventDesign extends EditRecord
{
    protected static string $resource = EventDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Borrar proyecto')];
    }
}
