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
     * @return array<string, string>  locale => url
     */
    public static function alternates(): array
    {
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
}
