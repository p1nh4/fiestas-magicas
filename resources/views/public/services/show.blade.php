{{--
    A página de um serviço.

    O preço só aparece se for verdade. Um serviço a zero mostra "a
    consultar" — é a mesma regra do resto do site, e existe porque um preço
    inventado numa página indexada é uma promessa que alguém vai cobrar.
--}}
@php
    $locale = app()->getLocale();
    $price = (float) $service->base_price;
@endphp

<x-layouts.public
    :title="data_get($service->seo, 'title', __('services.show.meta_title', ['service' => $service->name]))"
    :description="$service->summary ?: __('services.show.meta_description', ['service' => $service->name])">

    <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 md:py-20 lg:px-14">
        <p class="mb-3 text-sm">
            <a href="{{ route('home', ['locale' => $locale]) }}#celebraciones"
               class="text-ink-3 no-underline hover:text-rosa">&larr; {{ __('services.show.back') }}</a>
        </p>

        <x-section-head level="h1" :kicker="__('services.kicker')" :title="$service->name">
            @if ($service->summary)
                <p class="mt-3 max-w-[62ch] text-ink-2">{{ $service->summary }}</p>
            @endif

            <p class="mt-4 text-step-1 font-medium text-oro">
                @if ($price > 0)
                    {{ __('services.show.from', ['price' => number_format($price, 0, ',', '.').' €']) }}
                @else
                    {{ __('services.show.on_request') }}
                @endif
            </p>
        </x-section-head>

        @if ($service->description)
            <div class="max-w-[62ch] space-y-4 text-ink-2">
                @foreach (preg_split('/\R{2,}/', trim($service->description)) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        @endif
    </section>

    @if ($projects->isNotEmpty())
        <section class="border-t border-line">
            <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-6 md:py-20 lg:px-14">
                <x-section-head :title="__('services.show.works_title')" />

                <div class="grid gap-x-5 gap-y-9 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-work-card :project="$project" />
                    @endforeach
                </div>

                <p class="mt-8">
                    <a href="{{ route('projects.index', ['locale' => $locale]) }}"
                       class="text-sm underline underline-offset-4 hover:text-rosa">{{ __('services.show.works_all') }}</a>
                </p>
            </div>
        </section>
    @endif

    @if ($areas->isNotEmpty())
        <section class="border-t border-line bg-oro-wash">
            <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-14">
                <x-section-head :title="__('services.show.areas_title', ['service' => $service->name])" />

                <ul class="flex flex-wrap gap-2">
                    @foreach ($areas as $area)
                        <li>
                            <a href="{{ route('areas.show', ['locale' => $locale, 'slug' => $area->getTranslation('slug', $locale)]) }}"
                               class="inline-block rounded-full border border-line bg-ground px-3.5 py-1.5 text-sm no-underline transition hover:border-rosa hover:text-rosa">
                                {{ $area->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($faqs->isNotEmpty())
        <section class="border-t border-line">
            <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
                <x-section-head :title="__('services.show.faq_title')" />

                <dl class="max-w-[62ch] space-y-6">
                    @foreach ($faqs as $faq)
                        <div>
                            <dt class="font-medium">{{ $faq->question }}</dt>
                            <dd class="mt-1 text-ink-2">{{ $faq->answer }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    @endif

    @if ($others->isNotEmpty())
        <section class="border-t border-line bg-surface">
            <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-14">
                <x-section-head :title="__('services.show.others_title')" />

                <ul class="flex flex-wrap gap-2">
                    @foreach ($others as $other)
                        @php $url = $other->urlFor($locale); @endphp
                        <li>
                            @if ($url)
                                <a href="{{ $url }}"
                                   class="inline-block rounded-full border border-line bg-ground px-3.5 py-1.5 text-sm no-underline transition hover:border-rosa hover:text-rosa">{{ $other->name }}</a>
                            @else
                                <span class="inline-block rounded-full border border-line px-3.5 py-1.5 text-sm text-ink-3">{{ $other->name }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section id="contacto" class="border-t border-line">
        <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
            <x-section-head :title="__('services.show.cta_title', ['service' => $service->name])" />
            @include('partials.lead-form')
        </div>
    </section>
</x-layouts.public>
