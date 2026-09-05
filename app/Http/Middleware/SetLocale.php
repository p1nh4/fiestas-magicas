<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locales;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fixa o idioma a partir do primeiro segmento da URL.
 *
 * O idioma vem sempre da URL, nunca da sessão nem de um cookie. Assim cada
 * página tem um endereço único e estável — que é o que os motores de busca
 * precisam, e o que faz com que partilhar um link no WhatsApp mostre ao
 * outro a mesma coisa que tu vês.
 */
final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        if (! Locales::isSupported($locale)) {
            abort(404);
        }

        app()->setLocale($locale);

        // Datas por extenso ("16 de maio de 2027") no idioma certo.
        CarbonImmutable::setLocale($locale);

        // Faz com que route() e url() já incluam o idioma sem ninguém
        // se lembrar de o passar em cada chamada.
        URL::defaults(['locale' => $locale]);

        $response = $next($request);

        $response->headers->set('Content-Language', Locales::hreflang($locale));

        return $response;
    }
}
