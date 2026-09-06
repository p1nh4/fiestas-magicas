{{--
    Um trabalho.

    A página existe para duas pessoas ao mesmo tempo: quem está a decidir se
    contrata (e quer ver fotos, o sítio, quantas pessoas eram) e o Google
    (que quer texto próprio e uma ligação clara ao concelho).

    O `og:image` só leva a foto real. Enquanto não houver fotos, a partilha
    no WhatsApp mostra a imagem por omissão da marca — nunca uma imagem de
    exemplo a fingir-se de festa montada.
--}}
@php
    $locale = app()->getLocale();
    $photos = $project->photoUrls();

    $description = $project->description
        ?: __('works.show.meta_description', [
            'type' => $project->event_type?->label() ?? '',
            'city' => $project->city ?? config('business.address.locality'),
        ]);
@endphp

<x-layouts.public
    :title="data_get($project->seo, 'title', $project->title)"
    :description="\Illuminate\Support\Str::limit(strip_tags($description), 155)"
    :image="$project->coverUrl()">

    <article>
        <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 md:py-16 lg:px-14">
            <p class="mb-3 text-sm">
                <a href="{{ route('projects.index', ['locale' => $locale]) }}"
                   class="text-ink-3 no-underline hover:text-rosa">&larr; {{ __('works.show.back') }}</a>
            </p>

            <x-section-head level="h1" :kicker="$project->event_type?->label()" :title="$project->title">
                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-ink-3">
                    @if ($project->happened_on)
                        <span>{{ $project->happened_on->translatedFormat(__('works.show.date_format')) }}</span>
                    @endif
                    @if ($project->venue)
                        <span>{{ $project->venue }}</span>
                    @endif
                    @if ($project->city)
                        <span>{{ $project->city }}</span>
                    @endif
                    @if ($project->guests_count)
                        <span>{{ __('works.show.guests', ['count' => $project->guests_count]) }}</span>
                    @endif
                </div>
            </x-section-head>

            @if ($project->description)
                <div class="max-w-[62ch] space-y-4 text-ink-2">
                    @foreach (preg_split('/\R{2,}/', trim($project->description)) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($photos !== [])
            <section class="mx-auto max-w-[1240px] px-4 pb-14 sm:px-6 lg:px-14">
                {{-- A primeira foto ocupa a largura toda: é a que vende. As
                     outras vão a duas colunas. Nada de carrossel — um
                     carrossel esconde as fotos que a pessoa veio ver e
                     obriga a JavaScript para as mostrar. --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($photos as $i => $photo)
                        <figure class="{{ $i === 0 ? 'sm:col-span-2' : '' }} overflow-hidden rounded-xl bg-surface-2">
                            <img src="{{ $photo }}"
                                 alt="{{ __('works.show.photo_alt', ['title' => $project->title, 'n' => $i + 1]) }}"
                                 loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                                 decoding="async"
                                 @if ($i === 0) fetchpriority="high" @endif
                                 class="h-full w-full object-cover">
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($area)
            <section class="border-y border-line bg-oro-wash">
                <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-14">
                    <p class="max-w-[62ch] text-ink-2">
                        {{ __('works.show.area_line', ['area' => $area->name]) }}
                        <a href="{{ route('areas.show', ['locale' => $locale, 'slug' => $area->getTranslation('slug', $locale)]) }}"
                           class="underline underline-offset-4 hover:text-rosa">{{ __('works.show.area_cta', ['area' => $area->name]) }}</a>
                    </p>
                </div>
            </section>
        @endif

        @if ($more->isNotEmpty())
            <section class="mx-auto max-w-[1240px] px-4 py-14 sm:px-6 md:py-20 lg:px-14">
                <x-section-head :title="__('works.show.more')" />

                <div class="grid gap-x-5 gap-y-9 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($more as $other)
                        <x-work-card :project="$other" />
                    @endforeach
                </div>
            </section>
        @endif

        <section id="contacto" class="border-t border-line bg-surface">
            <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
                <x-section-head :title="__('works.show.cta_title')" />
                @include('partials.lead-form')
            </div>
        </section>
    </article>
</x-layouts.public>
