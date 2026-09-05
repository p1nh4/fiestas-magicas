<?php

declare(strict_types=1);

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Items\ItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = ItemResource::class;

    protected function translatableFields(): array
    {
        return ['name', 'slug', 'description'];
    }
}
