<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceAreas\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\ServiceAreas\ServiceAreaResource;
use App\Support\Locales;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditServiceArea extends EditRecord
{
    use EditsTranslations;

    protected static string $resource = ServiceAreaResource::class;

    protected function translatableFields(): array
    {
        return ['slug', 'intro'];
    }

    /**
     * O slug NAO se gera pelo caminho normal: o `name` da zona nao e um
     * campo traduzido, e o preenchimento do trait so funciona entre campos
     * traduzidos. Por isso `slugFields()` devolve vazio.
     *
     * O que ficou por fazer foi o resto: nada gerava o slug, o campo do
     * formulario nao era obrigatorio, e o CHECK so olha para o texto. Uma
     * zona nova publicada sem slug partia a pagina publica das zonas para
     * toda a gente, porque `/es/zonas` monta os links sem guarda.
     */
    protected function slugFields(): array
    {
        return [];
    }

    /** @param  array<string, mixed>  $data */
    private function preencherSlugs(array $data): array
    {
        $base = Str::slug((string) ($data['name'] ?? ''));

        if ($base === '') {
            return $data;
        }

        foreach (Locales::SUPPORTED as $locale) {
            if (blank($data['slug'][$locale] ?? null)) {
                $data['slug'][$locale] = $base;
            }
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return parent::mutateFormDataBeforeSave($this->preencherSlugs($data));
    }
}
