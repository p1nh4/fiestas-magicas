<x-layouts.public
    :title="__('rentals.index.meta_title')"
    :description="__('rentals.index.meta_description')">

    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 md:py-24 lg:px-14">
        <x-section-head
            level="h1"
            :kicker="__('rentals.kicker')"
            :title="__('rentals.index.title')"
            :lead="__('rentals.index.lead')" />

        @if ($items->isNotEmpty())
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ route('rentals.show', ['locale' => app()->getLocale(), 'slug' => $item->slug]) }}"
                           class="group flex h-full flex-col gap-1.5 rounded-xl border border-line bg-white/60 p-5 transition hover:border-oro hover:shadow-sm">
                            <span class="text-step-0 font-medium group-hover:text-oro">{{ $item->name }}</span>

                            <span class="text-sm text-ink-3">
                                {{ $item->stock_qty }} {{ __('rentals.index.units') }}
                            </span>

                            {{-- Preço a zero mostra "a consultar". O site nunca
                                 inventa um número que ninguém disse. --}}
                            <span class="mt-auto pt-2 text-sm text-ink-2">
                                @if ((float) $item->price_per_day > 0)
                                    {{ number_format((float) $item->price_per_day, 2, ',', '.') }} €
                                    <span class="text-ink-3">{{ __('rentals.show.per_day') }}</span>
                                @else
                                    {{ __('rentals.show.on_request') }}
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="max-w-[56ch] text-ink-2">{{ __('rentals.index.empty') }}</p>
        @endif
    </section>
</x-layouts.public>
