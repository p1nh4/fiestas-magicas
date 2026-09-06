<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventDesigns\Pages;

use App\Filament\Resources\EventDesigns\EventDesignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventDesign extends CreateRecord
{
    protected static string $resource = EventDesignResource::class;
}
