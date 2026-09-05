<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Cabeçalhos de segurança em todas as respostas.
         *
         * Não substituem nada do que o Laravel já faz (CSRF, escape do
         * Blade); fecham o que fica de fora. Sem CSP por agora — uma CSP
         * mal feita parte o site em silêncio, e vale mais pô-la em modo
         * report-only quando o site estiver estável do que às cegas agora.
         */
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->trustProxies(at: '*');   // o site vai atrás da Cloudflare
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
