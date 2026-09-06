<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Os três idiomas do site.
 *
 * A empresa está em Baiona, a 15 km da fronteira portuguesa, e o galego é
 * cooficial na Galiza. Não são três traduções de cortesia: são três
 * mercados reais.
 */
final class Locales
{
    /** Ordem importa: é a ordem do seletor no cabeçalho. */
    public const SUPPORTED = ['es', 'gl', 'pt'];

    public const DEFAULT = 'es';

    /**
     * Onde ficam guardados os endereços alternativos que o controlador
     * calculou.
     *
     * Vive nos atributos do Request e não numa propriedade estática de
     * propósito: uma estática sobrevive ao pedido inteiro e, em fila de
     * trabalhos ou com o Octane, ao pedido seguinte também — foi assim que
     * o `Page::urlFor` passou a devolver a resposta errada até se reiniciar
     * o PHP. Os atributos do Request morrem com o pedido, que é exatamente
     * o tempo de vida disto.
     */
    private const ALTERNATES_KEY = 'seo.alternates';

    /** Códigos completos para hreflang e para o atributo lang do html. */
    private const HREFLANG = [
        'es' => 'es-ES',
        'gl' => 'gl-ES',
        'pt' => 'pt-PT',
    ];

    private const NATIVE_NAME = [
        'es' => 'Español',
        'gl' => 'Galego',
        'pt' => 'Português',
    ];

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::SUPPORTED, true);
    }

    public static function hreflang(string $locale): string
    {
        return self::HREFLANG[$locale] ?? $locale;
    }

    public static function nativeName(string $locale): string
    {
        return self::NATIVE_NAME[$locale] ?? strtoupper($locale);
    }

    /**
     * Idioma a usar quando alguém chega à raiz do site.
     *
     * Lê o Accept-Language do navegador, mas nunca adivinha pelo IP: um
     * português de férias em Baiona continua a querer o site em português.
     * Na dúvida, espanhol.
     */
    public static function negotiate(Request $request): string
    {
        // Nao se usa getPreferredLanguage() do Symfony: ele normaliza os
        // codigos para pt_PT e nunca casa com a nossa lista em pt-PT, o que
        // fazia toda a gente cair em espanhol. Percorrer getLanguages() pela
        // ordem de preferencia e comparar so o prefixo e mais simples e faz
        // o que se espera.
        foreach ($request->getLanguages() as $language) {
            $prefix = strtolower(substr(str_replace('_', '-', $language), 0, 2));

            if (self::isSupported($prefix)) {
                return $prefix;
            }
        }

        return self::DEFAULT;
    }

    /**
     * A mesma página nos outros idiomas, para as tags hreflang e para o
     * seletor. Trocar de idioma tem de manter a pessoa onde ela está.
     *
     * Para as páginas cujo endereço é igual nos três idiomas (a portada, a
     * lista de zonas) basta trocar o prefixo. Para as que têm endereço
     * próprio por idioma — uma peça, um concelho, um trabalho — trocar o
     * prefixo dá uma página que não existe:
     *
     *     /es/alquiler/sillas-tiffany  →  /pt/alquiler/sillas-tiffany   404
     *
     * E um hreflang que aponta para um 404 não é meio caminho andado: o
     * Google desconta o grupo inteiro de anotações, incluindo as que
     * estavam certas. Por isso essas páginas registam os endereços de
     * verdade com `useAlternates()`, e é isso que aqui se devolve.
     *
     * @return array<string, string> locale => url
     */
    public static function alternates(): array
    {
        $registered = request()->attributes->get(self::ALTERNATES_KEY);

        if (is_array($registered) && $registered !== []) {
            return $registered;
        }

        $route = Route::current();

        if ($route === null) {
            return [];
        }

        $name = $route->getName();
        $params = $route->parameters();

        $out = [];
        foreach (self::SUPPORTED as $locale) {
            $out[$locale] = $name !== null
                ? route($name, [...$params, 'locale' => $locale])
                : url('/'.$locale);
        }

        return $out;
    }

    /**
     * Declara os endereços desta página nos outros idiomas.
     *
     * Chama-se no controlador, antes de devolver a vista.
     *
     * @param  array<string, string>  $urls  locale => url
     */
    public static function useAlternates(array $urls): void
    {
        request()->attributes->set(self::ALTERNATES_KEY, $urls);
    }

    /**
     * Os endereços de um registo com slug traduzido, nos idiomas em que ele
     * existe mesmo.
     *
     * Um idioma sem slug fica de fora, e fica de fora de propósito: é mais
     * honesto faltar a anotação do que apontá-la para uma página que dá
     * 404. O seletor de idioma trata dessa falta à sua maneira — manda a
     * pessoa para a portada desse idioma, que existe sempre.
     *
     * @return array<string, string>
     */
    public static function alternatesFor(string $route, object $model, string $field = 'slug'): array
    {
        $out = [];

        foreach (self::SUPPORTED as $locale) {
            $slug = $model->getTranslation($field, $locale, false);

            if (blank($slug)) {
                continue;
            }

            $out[$locale] = route($route, ['locale' => $locale, 'slug' => $slug]);
        }

        return $out;
    }
}
