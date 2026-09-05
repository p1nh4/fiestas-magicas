<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança.
 *
 * Cada um fecha uma porta concreta. Nada aqui é decorativo.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Impede que o site seja embebido num iframe noutro domínio —
        // é assim que se monta um clickjacking sobre um formulário.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Impede o navegador de "adivinhar" que um ficheiro é outra coisa.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Não deixa o endereço completo da nossa página vazar para sites
        // externos; o domínio chega.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Nada no site precisa de câmara, microfone ou localização.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), interest-cohort=()'
        );

        // HSTS só em produção e só sobre HTTPS: ativá-lo em local prende o
        // navegador a https://localhost e depois custa a desfazer.
        if ($request->secure() && app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
