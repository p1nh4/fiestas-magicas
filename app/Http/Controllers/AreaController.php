<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceArea;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AreaController extends Controller
{
    /** A lista de zonas. Mostra todas — mesmo as que ainda não têm página. */
    public function index(): View
    {
        return view('public.areas.index', [
            // Publicadas primeiro, porque são as que levam a algum lado.
            'published' => ServiceArea::query()->published()->get(),

            // As outras aparecem como texto, sem ligação. Dizer "também
            // vamos a Tui" é verdade e é útil a quem procura; fingir que
            // há uma página sobre Tui é que não.
            'others' => ServiceArea::query()
                ->where('is_published', false)
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        $area = ServiceArea::query()
            ->published()
            // O endereço é diferente em cada idioma; procura-se pelo do
            // idioma em que a pessoa está.
            ->where("slug->{$locale}", $slug)
            ->first();

        if ($area === null) {
            // 404 e não redirecionamento para a lista: uma zona por publicar
            // não tem página, e responder 200 com outra coisa qualquer é a
            // receita para o Google indexar lixo.
            throw new NotFoundHttpException();
        }

        return view('public.areas.show', [
            'area' => $area,
            'services' => Service::query()->active()->orderBy('position')->get(),

            // Trabalhos feitos naquele concelho, quando existem. É o que
            // torna a página diferente das outras — e é conteúdo real, com
            // consentimento do cliente, não texto de encher.
            'projects' => Project::query()
                ->published()
                ->where('city', $area->name)
                ->limit(6)
                ->get(),

            'faqs' => Faq::query()
                ->where('is_published', true)
                ->orderBy('position')
                ->get(),
        ]);
    }
}
