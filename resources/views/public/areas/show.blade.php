{{--
    A página de um concelho.

    O que a torna diferente das outras onze não é o nome trocado no título:
    é o texto que a Sol escreveu (sem ele a base de dados não a deixa
    publicar) e as festas realmente montadas ali. Se um dia isto virar um
    molde preenchido automaticamente, passa a prejudicar o site em vez de
    o ajudar.
--}}
@php
    $meta = [
        'title' => $area->seo['title'] ?? __('areas.show.meta_title', ['area' => $area->name]),
        'description' => $area->seo['description'] ?? __('areas.show.meta_description', ['area' => $area->name]),
    ];
@endphp

<x-layouts.public :title="$meta['title']" :description="$meta['description']">

    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 md:py-24 lg:px-14">
        <x-section-head
            level="h1"
            :kicker="__('areas.kicker')"
            :title="__('areas.show.meta_title', ['area' => $area->name])">

            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-ink-3">
                @if ($area->province)
                    <span>{{ $area->province }}</span>
                @endif
                @if ($area->distanceLabel())
                    <span>{{ __('areas.show.distance', ['distance' => $area->distanceLabel()]) }}</span>
                @endif
                @if ($area->travel_minutes)
                    <span>{{ __('areas.show.travel', ['minutes' => $area->travel_minutes]) }}</span>
                @endif
            </div>
        </x-section-head>

        {{-- O texto próprio. É a razão de a página existir. --}}
        <div class="max-w-[62ch] space-y-4 text-ink-2">
            @foreach (preg_split('/\R{2,}/', trim($area->intro)) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </section>

    @if ($services->isNotEmpty())
        <section class="border-t border-line bg-white/40">
            <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
                <x-section-head :title="__('areas.show.services_title', ['area' => $area->name])" />

                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        @php $url = $service->urlFor(); @endphp
                        <li class="relative rounded-xl border border-line bg-ground p-5 transition hover:border-rosa">
                            <h3 class="text-step-0 font-medium">
                                @if ($url)
                                    <a href="{{ $url }}" class="no-underline after:absolute after:inset-0 hover:text-rosa">{{ $service->name }}</a>
                                @else
                                    {{ $service->name }}
                                @endif
                            </h3>
                            @if ($service->summary)
                                <p class="mt-1.5 text-sm text-ink-2">{{ $service->summary }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($projects->isNotEmpty())
        <section>
            <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
                <x-section-head :title="__('areas.show.projects_title', ['area' => $area->name])" />

                {{-- Isto era uma lista de títulos sem foto e sem
                     destino: a prova de que o trabalho existia, sem o
                     mostrar. Agora é a mesma grelha do portefólio. --}}
                <div class="grid gap-x-5 gap-y-9 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($projects as $project)
                        <x-work-card :project="$project" />
                    @endforeach
                </div>

                <p class="mt-8">
                    <a href="{{ route('projects.index', ['locale' => app()->getLocale()]) }}"
                       class="text-sm underline underline-offset-4 hover:text-rosa">{{ __('areas.show.projects_all') }}</a>
                </p>
            </div>
        </section>
    @endif

    @if ($faqs->isNotEmpty())
        <section class="border-t border-line bg-white/40">
            <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
                <x-section-head :title="__('areas.show.faq_title')" />

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

    <section id="contacto" class="border-t border-line">
        <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
            <x-section-head :title="__('areas.show.cta_title', ['area' => $area->name])" />

            @include('partials.lead-form')

            <p class="mt-8">
                <a href="{{ route('areas.index', ['locale' => app()->getLocale()]) }}"
                   class="text-sm text-ink-3 underline underline-offset-4 hover:text-oro">
                    {{ __('areas.show.back') }}
                </a>
            </p>
        </div>
    </section>
</x-layouts.public>
