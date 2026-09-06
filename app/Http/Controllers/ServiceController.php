<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceArea;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A página de um serviço.
 *
 * Mesma história do portefólio: o serviço tinha nome, resumo, descrição e
 * endereço por idioma no backoffice, e na portada era um quadrado sem
 * destino. A descrição longa que a Sol escrevesse não aparecia em lado
 * nenhum.
 *
 * Estas páginas são também as que respondem às buscas concretas — "mesa
 * dulce comunión", "arco de globos" — que a portada, por ser sobre tudo,
 * nunca responde bem.
 */
final class ServiceController extends Controller
{
    public function show(string $locale, string $slug): View
    {
        $service = Service::query()
            ->active()
            ->where("slug->{$locale}", $slug)
            ->first();

        if ($service === null) {
            throw new NotFoundHttpException;
        }

        Locales::useAlternates(Locales::alternatesFor('services.show', $service));

        return view('public.services.show', [
            'service' => $service,

            // Trabalhos do mesmo tipo de festa que este serviço, quando o
            // nome bate certo com um tipo. É uma ligação frouxa de
            // propósito: um serviço não é um tipo de evento, e forçar a
            // correspondência daria listas erradas com ar de certas.
            'projects' => Project::query()
                ->published()
                ->limit(3)
                ->get(),

            'areas' => ServiceArea::query()->published()->get(),

            'faqs' => Faq::query()
                ->where('is_published', true)
                ->orderBy('position')
                ->get(),

            'others' => Service::query()
                ->active()
                ->whereKeyNot($service->getKey())
                ->orderBy('position')
                ->get(),
        ]);
    }
}
