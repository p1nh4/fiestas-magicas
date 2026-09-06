<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\Project;
use App\Models\ServiceArea;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * O portefólio.
 *
 * O backoffice já sabia guardar trabalhos, com título por idioma,
 * consentimento do cliente e endereço próprio — mas nada no site os servia.
 * O campo `slug` existia e não levava a lado nenhum, e a portada mostrava
 * oito títulos sem foto e sem destino.
 *
 * Numa empresa de decoração isto não é um pormenor: quem procura decoração
 * decide pelo que vê. E é também o conteúdo que a concorrência não copia —
 * uma festa real, num sítio real, com fotos que só esta empresa tem.
 */
final class ProjectController extends Controller
{
    /**
     * A galeria, com filtro por tipo de festa.
     *
     * O filtro vai em GET e é validado contra o enum: um `?tipo=` inventado
     * na barra de endereços mostra tudo, em vez de dar erro ou uma lista
     * vazia sem explicação.
     */
    public function index(Request $request): View
    {
        // `$request->string()` e nao `query()`: um `?tipo[]=x` faz o
        // `query()` devolver um array, e o cast para string de um array e
        // um erro fatal. O `string()` devolve vazio nesse caso.
        $type = EventType::tryFrom($request->string('tipo')->value());

        return view('public.projects.index', [
            'projects' => Project::query()
                ->published()
                ->when($type, fn ($q) => $q->where('event_type', $type))
                ->get(),

            'active' => $type,

            // Só aparecem no filtro os tipos que têm mesmo trabalhos
            // publicados. Um separador "Bodas" que abre uma página vazia é
            // pior do que não haver separador nenhum.
            //
            // O `reorder()` não é decoração. O `published()` acrescenta um
            // ORDER BY published_at, e o Postgres recusa um SELECT DISTINCT
            // ordenado por uma coluna que não está na lista de seleção:
            // "for SELECT DISTINCT, ORDER BY expressions must appear in
            // select list". Aqui não se quer ordem nenhuma — quer-se a
            // lista de tipos.
            'types' => Project::query()
                ->published()
                ->reorder()
                ->distinct()
                ->pluck('event_type')
                ->filter()
                ->values(),
        ]);
    }

    public function show(string $locale, string $slug): View
    {
        $project = Project::query()
            ->published()
            ->where("slug->{$locale}", $slug)
            ->first();

        if ($project === null) {
            throw new NotFoundHttpException;
        }

        // O endereço deste trabalho nos outros idiomas. Sem isto, o hreflang
        // apontava para /pt/trabajos/<slug-espanhol>, que não existe.
        Locales::useAlternates(Locales::alternatesFor('projects.show', $project));

        return view('public.projects.show', [
            'project' => $project,

            // A zona onde a festa foi, quando ela tem página. É o que liga
            // o portefólio ao SEO local: quem chega por "photocall en
            // Nigrán" encontra aqui a página de Nigrán, e ao contrário.
            'area' => $project->city === null
                ? null
                : ServiceArea::query()->published()->where('name', $project->city)->first(),

            // Mais trabalhos, para não haver becos sem saída. Do mesmo tipo
            // primeiro, que é o que interessa a quem está a ver.
            'more' => Project::query()
                ->published()
                ->whereKeyNot($project->getKey())
                // `reorder()` outra vez: o `published()` já ordenou por
                // data, e aqui a data é o critério de desempate, não o
                // principal.
                ->reorder()
                ->orderByRaw('CASE WHEN event_type = ? THEN 0 ELSE 1 END', [$project->event_type?->value])
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
