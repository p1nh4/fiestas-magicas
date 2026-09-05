<?php

declare(strict_types=1);

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = ServiceResource::class;

    protected function translatableFields(): array
    {
        return ['name', 'slug', 'summary', 'description'];
    }
}
