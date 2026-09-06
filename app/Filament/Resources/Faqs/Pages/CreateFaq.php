<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = FaqResource::class;

    protected function translatableFields(): array
    {
        return ['question', 'answer'];
    }

    /** Uma pergunta nao tem endereco proprio: nao ha slug para gerar. */
    protected function slugFields(): array
    {
        return [];
    }
}
