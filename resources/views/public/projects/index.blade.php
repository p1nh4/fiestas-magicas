{{--
    A galeria de trabalhos.

    Sem paginação de propósito: são festas de uma empresa pequena, dezenas
    e não milhares, e uma grelha inteira que se percorre a rolar vende
    melhor do que três de cada vez com botões pelo meio. Se um dia forem
    centenas, muda-se — mas então já haverá com que decidir.
--}}
@php
    $locale = app()->getLocale();
@endphp

<x-layouts.public
    :title="__('works.index.meta_title')"
    :description="__('works.index.meta_description')">

    <section class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 md:py-20 lg:px-14">
        <x-section-head
            level="h1"
            :kicker="__('works.kicker')"
            :title="__('works.index.title')">
            <p class="mt-3 max-w-[62ch] text-ink-2">{{ __('works.index.lead') }}</p>
        </x-section-head>

        @if ($types->count() > 1)
            {{-- Filtro por tipo de festa. Ligações normais, com o tipo na
                 barra de endereços: pode guardar-se, partilhar-se, e o
                 botão "voltar" faz o que se espera. --}}
            <nav class="mb-8 flex flex-wrap gap-2" aria-label="{{ __('works.index.filter') }}">
                <a href="{{ route('projects.index', ['locale' => $locale]) }}"
                   @if (! $active) aria-current="page" @endif
                   class="rounded-full border px-3.5 py-1.5 text-sm no-underline transition {{ $active ? 'border-line text-ink-2 hover:border-rosa hover:text-rosa' : 'border-rosa bg-rosa-wash text-rosa' }}">
                    {{ __('works.index.all') }}
                </a>

                @foreach ($types as $type)
                    <a href="{{ route('projects.index', ['locale' => $locale, 'tipo' => $type->value]) }}"
                       @if ($active === $type) aria-current="page" @endif
                       class="rounded-full border px-3.5 py-1.5 text-sm no-underline transition {{ $active === $type ? 'border-rosa bg-rosa-wash text-rosa' : 'border-line text-ink-2 hover:border-rosa hover:text-rosa' }}">
                        {{ $type->label() }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($projects->isEmpty())
            {{-- Nada de grelhas fantasma com "próximamente". Diz-se o que
                 há — nada, para já — e dá-se o caminho seguinte. --}}
            <p class="max-w-[62ch] rounded-xl border border-line bg-surface p-6 text-ink-2">
                {{ __('works.index.empty') }}
                <a href="{{ route('home', ['locale' => $locale]) }}#contacto"
                   class="underline underline-offset-4 hover:text-rosa">{{ __('works.index.empty_cta') }}</a>
            </p>
        @else
            <div class="grid gap-x-5 gap-y-9 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $i => $project)
                    {{-- As três primeiras carregam já; as outras esperam
                         por chegarem à vista. Sem isto, uma galeria de
                         quarenta fotos gasta os dados da pessoa antes de
                         ela ter visto a primeira. --}}
                    @php $eager = $i < 3; @endphp
                    <x-work-card :project="$project" :eager="$eager" />
                @endforeach
            </div>
        @endif
    </section>

    <section id="contacto" class="border-t border-line bg-surface">
        <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 md:py-20 lg:px-14">
            <x-section-head :title="__('works.index.cta_title')" />
            @include('partials.lead-form')
        </div>
    </section>
</x-layouts.public>
