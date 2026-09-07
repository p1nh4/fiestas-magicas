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

        // CSP a bloquear de vez. Passou primeiro por Report-Only: testado o
        // site público e o /admin (Livewire + Alpine incluídos) com consola
        // aberta, zero violações reais — só um pedido externo de avatar
        // (ui-avatars.com) que o 'img-src https:' já cobre. Por isso os
        // valores ficam largos onde o admin precisa ('unsafe-inline' e
        // 'unsafe-eval' para Livewire/Alpine); apertar mais isso é trabalho
        // para depois, com nonces, não bloquear às cegas agora.
        //
        // Em local o Vite injeta o cliente de hot-reload (script, CSS e o
        // websocket do HMR) a partir de 127.0.0.1:5173 — outra origem, que
        // 'self' não cobre. Sem isto o `npm run dev` fica sem estilo nenhum.
        // Só entra fora de produção: em produção os assets já vêm
        // compilados do próprio domínio, o Vite dev server nem corre.
        $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'];
        $connectSrc = ["'self'"];

        if (! app()->isProduction()) {
            $viteDev = ['http://localhost:5173', 'http://127.0.0.1:5173'];

            $scriptSrc = [...$scriptSrc, ...$viteDev];
            $styleSrc = [...$styleSrc, ...$viteDev];
            $connectSrc = [...$connectSrc, ...$viteDev, 'ws://localhost:5173', 'ws://127.0.0.1:5173'];
        }

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                'script-src '.implode(' ', $scriptSrc),
                'style-src '.implode(' ', $styleSrc),
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data: https:",
                'connect-src '.implode(' ', $connectSrc),
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ])
        );

        return $response;
    }
}
