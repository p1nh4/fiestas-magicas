<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = ProjectResource::class;

    protected function translatableFields(): array
    {
        return ['title', 'slug', 'description'];
    }

    /** O slug gera-se a partir do título. */
    protected function slugFields(): array
    {
        return ['slug' => 'title'];
    }
}
