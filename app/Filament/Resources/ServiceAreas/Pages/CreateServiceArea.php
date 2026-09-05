<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceAreas\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\ServiceAreas\ServiceAreaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceArea extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = ServiceAreaResource::class;

    protected function translatableFields(): array
    {
        return ['slug', 'intro'];
    }

    /** O slug gera-se a partir do nome, que aqui não é um campo traduzido. */
    protected function slugFields(): array
    {
        return [];
    }
}
