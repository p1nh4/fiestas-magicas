@php
    $free = is_array($result) && ! isset($result['error']);
@endphp

<x-layouts.public
    :title="__('rentals.show.meta_title', ['item' => $item->name])"
    :description="__('rentals.show.meta_description', ['item' => $item->name])">

    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 md:py-24 lg:px-14">
        <x-section-head
            level="h1"
            :kicker="__('rentals.kicker')"
            :title="$item->name">

            <p class="mt-2 text-ink-2">
                {{ __('rentals.show.stock', ['count' => $item->stock_qty]) }}
                @if ((float) $item->price_per_day > 0)
                    · {{ number_format((float) $item->price_per_day, 2, ',', '.') }} €
                    {{ __('rentals.show.per_day') }}
                @else
                    · {{ __('rentals.show.on_request') }}
                @endif
            </p>
        </x-section-head>

        @if ($item->description)
            <p class="mb-8 max-w-[62ch] text-ink-2">{{ $item->description }}</p>
        @endif

        @if ($item->requires_transport)
            <p class="mb-8 max-w-[62ch] rounded-lg bg-oro-wash px-4 py-3 text-sm text-ink-2">
                {{ __('rentals.show.transport') }}
            </p>
        @endif

        {{--
            GET e não POST de propósito: assim o resultado fica na barra de
            endereços e a pessoa pode guardar o link ou mandá-lo a alguém.
            Não há nada pessoal aqui — só duas datas.
        --}}
        <h2 class="text-step-1">{{ __('rentals.show.check_title') }}</h2>

        <form method="GET" class="mt-4 grid gap-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
            <div class="grid gap-1.5">
                <label for="desde" class="text-sm text-ink-2">{{ __('rentals.show.from') }}</label>
                <input type="date" id="desde" name="desde" value="{{ $query['desde'] }}"
                       min="{{ now()->toDateString() }}"
                       class="rounded-lg border border-line bg-white px-3 py-2">
            </div>

            <div class="grid gap-1.5">
                <label for="hasta" class="text-sm text-ink-2">{{ __('rentals.show.to') }}</label>
                <input type="date" id="hasta" name="hasta" value="{{ $query['hasta'] }}"
                       min="{{ now()->toDateString() }}"
                       class="rounded-lg border border-line bg-white px-3 py-2">
            </div>

            <div class="grid gap-1.5">
                <label for="cantidad" class="text-sm text-ink-2">{{ __('rentals.show.quantity') }}</label>
                <input type="number" id="cantidad" name="cantidad" value="{{ $query['cantidad'] }}"
                       min="1" max="{{ $item->stock_qty }}"
                       class="w-24 rounded-lg border border-line bg-white px-3 py-2">
            </div>

            <x-btn type="submit" size="lg">{{ __('rentals.show.check') }}</x-btn>
        </form>

        @if (is_array($result))
            <div class="mt-6 max-w-[62ch] rounded-xl border px-4 py-4
                        {{ $free && $result['enough'] ? 'border-oro-soft bg-oro-wash' : 'border-rosa-soft bg-rosa-wash' }}">

                @if (isset($result['error']))
                    <p class="text-ink">{{ $result['error'] }}</p>
                @elseif ($result['enough'])
                    <p class="font-medium text-ink">
                        {{ __('rentals.result.yes', [
                            'free' => $result['free'],
                            'from' => $result['from']->format('d/m/Y'),
                            'to' => $result['to']->format('d/m/Y'),
                        ]) }}
                    </p>
                @elseif ($result['free'] === 0)
                    <p class="font-medium text-ink">{{ __('rentals.result.none') }}</p>
                @else
                    <p class="font-medium text-ink">
                        {{ __('rentals.result.no_enough', [
                            'free' => $result['free'],
                            'wanted' => $result['wanted'],
                        ]) }}
                    </p>
                @endif

                @unless (isset($result['error']))
                    {{-- Porque é que às vezes dá menos do que a pessoa espera:
                         a conta inclui as folgas de transporte e limpeza. Dizê-lo
                         evita a chamada a perguntar. --}}
                    <p class="mt-2 text-sm text-ink-2">{{ __('rentals.result.margin') }}</p>

                    {{-- A frase mais importante desta página. Uma pessoa que
                         saia daqui convencida de que a peça ficou guardada, e
                         depois descubra que não, fica pior do que se nunca
                         tivesse consultado. --}}
                    <p class="mt-2 text-sm font-medium text-ink-2">{{ __('rentals.result.not_a_booking') }}</p>
                @endunless
            </div>
        @endif

        <div class="mt-8 flex flex-wrap gap-2.5">
            <x-btn variant="line" size="lg" href="{{ route('home', ['locale' => app()->getLocale()]) }}#contacto">
                {{ __('rentals.result.ask') }}
            </x-btn>
            <x-btn variant="wa" size="lg" href="https://wa.me/{{ config('business.whatsapp') }}" rel="noopener">
                <x-ico name="whatsapp" class="w-[1.05rem] h-[1.05rem]" />
                {{ __('site.util.whatsapp') }}
            </x-btn>
        </div>

        <p class="mt-8">
            <a href="{{ route('rentals.index', ['locale' => app()->getLocale()]) }}"
               class="text-sm text-ink-3 underline underline-offset-4 hover:text-oro">
                {{ __('rentals.show.back') }}
            </a>
        </p>
    </section>
</x-layouts.public>
