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

        // CSP em modo Report-Only: nunca bloqueia nada, só avisa na consola
        // do navegador (DevTools > Console/Issues) o que a política teria
        // recusado. É o passo intermédio que faltava — o painel /admin usa
        // Livewire e Alpine, que precisam de 'unsafe-inline'/'unsafe-eval'
        // para correr, e uma CSP a bloquear de vez sem isso testado primeiro
        // parte o backoffice em silêncio. Corre em todos os pedidos (não só
        // produção) para já dar sinal em local; o passo seguinte é abrir o
        // site e o /admin, ver a consola, apertar o que sobrar sem uso real,
        // e só então trocar para 'Content-Security-Policy' a sério.
        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data: https:",
                "connect-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ])
        );

        return $response;
    }
}
