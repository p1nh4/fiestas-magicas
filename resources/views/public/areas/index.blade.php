{{--
    A lista de zonas.

    Duas listas de propósito: as que têm página própria, com ligação, e as
    outras em texto simples. Dizer "também vamos a Tui" é verdade e ajuda
    quem procura; fingir que há uma página sobre Tui, quando ninguém a
    escreveu, é que não.
--}}
<x-layouts.public
    :title="__('areas.index.meta_title')"
    :description="__('areas.index.meta_description')">

    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 md:py-24 lg:px-14">
        <x-section-head
            level="h1"
            :kicker="__('areas.kicker')"
            :title="__('areas.index.title')"
            :lead="__('areas.index.lead')" />

        @if ($published->isNotEmpty())
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($published as $area)
                    <li>
                        <a href="{{ route('areas.show', ['locale' => app()->getLocale(), 'slug' => $area->slug]) }}"
                           class="group flex h-full flex-col gap-1.5 rounded-xl border border-line bg-white/60 p-5 transition hover:border-oro hover:shadow-sm">
                            <span class="text-step-0 font-medium group-hover:text-oro">{{ $area->name }}</span>

                            @if ($area->province)
                                <span class="text-sm text-ink-3">{{ $area->province }}</span>
                            @endif

                            @if ($area->distanceLabel())
                                <span class="mt-auto pt-2 text-sm text-ink-2">
                                    {{ __('areas.show.distance', ['distance' => $area->distanceLabel()]) }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="max-w-[56ch] text-ink-2">{{ __('areas.index.empty') }}</p>
        @endif

        @if ($others->isNotEmpty())
            <div class="mt-10 max-w-[60ch]">
                <p class="text-sm text-ink-3">
                    {{ __('areas.index.more') }}
                    <span class="text-ink-2">{{ $others->pluck('name')->join(', ', ' · ') }}</span>.
                </p>
            </div>
        @endif

        <p class="mt-8 text-ink-2">{{ __('areas.index.ask') }}</p>

        <div class="mt-5">
            <x-btn variant="wa" size="lg" href="https://wa.me/{{ config('business.whatsapp') }}" rel="noopener">
                <x-ico name="whatsapp" class="w-[1.05rem] h-[1.05rem]" />
                {{ __('site.util.whatsapp') }}
            </x-btn>
        </div>
    </section>
</x-layouts.public>
