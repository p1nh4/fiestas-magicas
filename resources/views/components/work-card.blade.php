@props(['project', 'eager' => false])

@php
    $url = $project->urlFor();
    $cover = $project->coverUrl();
@endphp

{{--
    Um trabalho na grelha.

    Se ainda não há foto real, entra a imagem de exemplo — mas a de exemplo
    nunca sai daqui para uma etiqueta `og:image`: no WhatsApp de alguém
    passaria por foto de uma festa que ninguém montou.

    Sem endereço (trabalho sem título neste idioma) o cartão continua a
    aparecer, apenas sem ligação. Um `href="#"` seria pior: promete um
    clique que não leva a lado nenhum.
--}}
{{-- `relative` porque o título tem um ::after que cobre o cartão inteiro:
     assim o clique apanha a foto toda e não só as letras, sem envolver
     tudo numa <a> (o que faria o leitor de ecrã ler a imagem e o texto
     como um único link comprido). --}}
<article {{ $attributes->merge(['class' => 'group relative']) }}>
    <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-surface-2">
        @if ($cover)
            <img src="{{ $cover }}"
                 alt="{{ $project->title }}"
                 loading="{{ $eager ? 'eager' : 'lazy' }}"
                 decoding="async"
                 @if ($eager) fetchpriority="high" @endif
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <x-photo
                :name="data_get($project->seo, 'image', 'mesa-dulce')"
                :alt="$project->title"
                :loading="$eager ? 'eager' : 'lazy'"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
        @endif

        @if (count($project->photoUrls()) > 1)
            <span class="absolute bottom-2 right-2 rounded-full bg-ink/70 px-2 py-0.5 text-[0.7rem] font-medium text-ground">
                {{ count($project->photoUrls()) }}
            </span>
        @endif
    </div>

    <h3 class="mt-3 text-step-0 font-medium leading-snug">
        @if ($url)
            <a href="{{ $url }}" class="no-underline after:absolute after:inset-0 hover:text-rosa">{{ $project->title }}</a>
        @else
            {{ $project->title }}
        @endif
    </h3>

    <p class="mt-0.5 text-sm text-ink-3">
        {{ $project->event_type?->label() }}@if ($project->city) · {{ $project->city }} @endif
    </p>
</article>
