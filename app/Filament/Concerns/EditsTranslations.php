<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\Locales;
use Illuminate\Support\Str;

/**
 * Editar campos traduzidos (jsonb) num formulário do Filament.
 *
 * O problema: com spatie/laravel-translatable, `$service->name` devolve a
 * tradução no idioma ATUAL, não o array. Se o formulário se enchesse com
 * isso, abrir e gravar um serviço apagava as outras duas traduções sem
 * ninguém dar por nada — que é a pior classe de bug que há: silencioso e
 * destrutivo.
 *
 * Solução: ao encher o formulário, mete-se o array completo
 * (`getTranslations`); ao gravar, o array volta inteiro e o spatie
 * reescreve as três línguas.
 *
 * De caminho, os slugs são gerados a partir do nome. Ninguém devia ter de
 * escrever três slugs à mão para pôr um serviço no catálogo.
 */
trait EditsTranslations
{
    /**
     * Campos jsonb traduzidos deste model.
     *
     * @return list<string>
     */
    abstract protected function translatableFields(): array;

    /** Campos cujo slug se gera a partir de outro. Ex.: ['slug' => 'name']. */
    protected function slugFields(): array
    {
        return ['slug' => 'name'];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Na pagina de CRIACAO ainda nao ha registo — o Filament chama este
        // metodo na mesma, para encher o formulario com os valores por
        // omissao. Sem esta guarda, criar um servico rebentava com
        // "call to a member function on null", e so ali: editar funcionava.
        if (! isset($this->record)) {
            return $data;
        }

        foreach ($this->translatableFields() as $field) {
            $data[$field] = $this->record->getTranslations($field);
        }

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->fillSlugs($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->fillSlugs($data);
    }

    /**
     * Um slug vazio preenche-se a partir do nome no mesmo idioma. Um slug
     * já escrito NUNCA se toca: mudá-lo parte os links que já andam por aí.
     */
    private function fillSlugs(array $data): array
    {
        foreach ($this->slugFields() as $slugField => $sourceField) {
            if (! isset($data[$slugField]) || ! is_array($data[$slugField])) {
                $data[$slugField] = [];
            }

            foreach (Locales::SUPPORTED as $locale) {
                $current = $data[$slugField][$locale] ?? null;
                $source = $data[$sourceField][$locale] ?? null;

                if (blank($current) && filled($source)) {
                    $data[$slugField][$locale] = Str::slug((string) $source);
                }
            }
        }

        return $data;
    }
}
