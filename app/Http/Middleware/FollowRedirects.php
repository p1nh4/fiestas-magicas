<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antes de devolver um 404, vê se aquele endereço já existiu.
 *
 * Deliberadamente DEPOIS da resposta e não antes: assim não há uma consulta
 * à base de dados em cada visita ao site, só nas que iriam dar 404 — que
 * são poucas. A alternativa (verificar antes de encaminhar) custava uma
 * consulta a toda a gente para servir uma minoria.
 */
class FollowRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404 || ! $request->isMethod('GET')) {
            return $response;
        }

        $redirect = Redirect::resolve($request->path());

        if ($redirect === null) {
            return $response;
        }

        // Contar os acertos diz quais valem a pena manter e quais já não
        // interessam a ninguém. O incremento é feito pela base de dados:
        // ler e reescrever em PHP perdia contagens com visitas simultâneas.
        $redirect->increment('hits');

        return redirect($redirect->to_path, $redirect->status_code);
    }
}
