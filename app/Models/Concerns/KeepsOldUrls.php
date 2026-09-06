<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Redirect;
use App\Support\Locales;

/**
 * Ao mudar um slug, guarda o endereço antigo a apontar para o novo.
 *
 * Sem isto, mudar "photocall-floral" para "photocall-de-flores" mata todas
 * as ligações que já andam por aí — e ninguém dá por isso, porque quem
 * clica não avisa que apanhou um 404.
 *
 * Fica num trait e não em cada model porque a regra é a mesma em todo o
 * lado, e uma regra copiada quatro vezes acaba por divergir numa delas.
 */
trait KeepsOldUrls
{
    /**
     * Nome da rota pública deste model, ou null se não tiver página própria.
     * A rota tem de aceitar os parâmetros `locale` e `slug`.
     */
    abstract public function publicRouteName(): ?string;

    protected static function bootKeepsOldUrls(): void
    {
        static::updating(function (self $model): void {
            $route = $model->publicRouteName();

            if ($route === null || ! $model->isDirty('slug')) {
                return;
            }

            /*
             * O valor original pode vir das duas maneiras.
             *
             * O spatie regista um cast `array` nos campos traduzidos, por
             * isso o `getOriginal()` costuma devolver JÁ um array. Mas
             * quando o cast ainda não se aplicou — model acabado de vir da
             * base de dados, ou com o cast por outra via — vem o texto
             * jsonb em bruto.
             *
             * A primeira versão assumia texto e fazia `json_decode` de um
             * array: "Array to string conversion", e nenhum
             * redirecionamento era criado.
             */
            $old = $model->getOriginal('slug');

            if (is_string($old)) {
                $old = json_decode($old, true);
            }

            if (! is_array($old)) {
                return;
            }

            $new = $model->getTranslations('slug');

            foreach (Locales::SUPPORTED as $locale) {
                $before = $old[$locale] ?? null;
                $after = $new[$locale] ?? null;

                if (blank($before) || blank($after) || $before === $after) {
                    continue;
                }

                Redirect::remember(
                    from: (string) parse_url(route($route, ['locale' => $locale, 'slug' => $before]), PHP_URL_PATH),
                    to: (string) parse_url(route($route, ['locale' => $locale, 'slug' => $after]), PHP_URL_PATH),
                );
            }
        });
    }
}
