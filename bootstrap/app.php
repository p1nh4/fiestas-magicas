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

        /*
         * Antes de devolver um 404, ver se aquele endereço já existiu.
         *
         * Global e não no grupo `web`: um link antigo pode nem ter prefixo
         * de idioma (/photocall-antiguo) e, nesse caso, nenhuma rota chega
         * a corresponder — o grupo `web` nunca correria.
         *
         * Corre DEPOIS da resposta e só quando ela é 404, portanto uma
         * visita normal não paga nenhuma consulta à base de dados.
         */
        $middleware->append(\App\Http\Middleware\FollowRedirects::class);

        /*
         * Quem decide o IP do visitante.
         *
         * Aqui estava `at: '*'`, que é confiar no `X-Forwarded-For` venha
         * ele de onde vier. Com a Cloudflare à frente isso é um buraco: ela
         * ACRESCENTA o IP real a esse cabeçalho mas não apaga o que já lá
         * estiver, portanto qualquer pessoa podia mandar
         * `X-Forwarded-For: 9.9.9.9` e escolher o IP que o Laravel via.
         *
         * O que isso partia, em concreto: o `throttle:6,1` do formulário
         * deixava de travar ninguém (muda-se o cabeçalho a cada pedido), e
         * o `Lead::hashIp()` passava a guardar hashes escolhidos por quem
         * envia — a deteção de spam por origem deixava de valer nada.
         *
         * A escolha certa vive uma camada abaixo: o nginx reescreve o
         * REMOTE_ADDR a partir do `CF-Connecting-IP` — que a Cloudflare
         * SUBSTITUI, e por isso não se forja — e só quando a ligação vem
         * mesmo de um intervalo dela (ver deploy/update-cloudflare-ips.sh).
         *
         * Confia-se só no nginx local. Como ele já entrega o IP verdadeiro
         * em REMOTE_ADDR, o endereço que o PHP vê não é 127.0.0.1, não
         * consta desta lista, e os cabeçalhos X-Forwarded-* são
         * simplesmente ignorados — que é exatamente o que se quer.
         *
         * O HTTPS não depende disto: o nginx termina o TLS e o
         * fastcgi_params já marca a ligação como segura.
         */
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
