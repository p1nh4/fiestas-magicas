@php $b = config('business'); @endphp

<footer class="border-t border-line py-10 text-[0.9rem] text-muted">
    <div class="mx-auto max-w-[1240px] px-4 sm:px-6 lg:px-14">
        <div class="grid gap-8 md:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <x-brand class="mb-3" />
                <p class="max-w-[34ch]">{{ __('site.brand.tagline') }}</p>
            </div>

            <div>
                <h4 class="mb-2 text-[0.74rem] font-bold uppercase tracking-[0.16em] text-ink">{{ __('site.footer.celebrations') }}</h4>
                <ul class="grid gap-1 list-none p-0 m-0">
                    @foreach (['cumpleanos', 'bautizo', 'comunion', 'boda'] as $type)
                        <li><a href="#celebraciones" class="no-underline hover:text-rosa">{{ \App\Enums\EventType::from($type)->label() }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4 class="mb-2 text-[0.74rem] font-bold uppercase tracking-[0.16em] text-ink">{{ __('site.footer.contact') }}</h4>
                <ul class="grid gap-1 list-none p-0 m-0">
                    <li><a href="tel:{{ $b['phone'] }}" class="no-underline hover:text-rosa">{{ $b['phone_display'] }}</a></li>
                    <li><a href="https://wa.me/{{ $b['whatsapp'] }}" rel="noopener" class="no-underline hover:text-rosa">{{ __('site.util.whatsapp') }}</a></li>
                    @if ($b['instagram'])
                        <li><a href="https://instagram.com/{{ $b['instagram'] }}" rel="noopener" class="no-underline hover:text-rosa">{{ __('site.util.instagram') }}</a></li>
                    @endif
                    <li>{{ $b['address']['locality'] }} {{ $b['address']['postal_code'] }}</li>
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap justify-between gap-x-6 gap-y-2 border-t border-line pt-4 text-[0.82rem]">
            <span>&copy; {{ date('Y') }} {{ $b['name'] }} · {{ $b['sub'] }}</span>
            {{--
                Cada uma vira ligacao SO quando a pagina existe e esta
                publicada. Um link morto num rodape legal e pior do que
                texto simples: promete uma pagina que nao ha.
            --}}
            <span class="flex flex-wrap gap-x-1.5">
                @foreach ([
                    \App\Models\Page::LEGAL => __('site.footer.legal'),
                    \App\Models\Page::PRIVACY => __('site.footer.privacy'),
                    \App\Models\Page::COOKIES => __('site.footer.cookies'),
                ] as $key => $label)
                    @php $url = \App\Models\Page::urlFor($key); @endphp

                    @if (! $loop->first)<span aria-hidden="true">·</span>@endif

                    @if ($url)
                        <a href="{{ $url }}" class="underline underline-offset-2 hover:text-oro">{{ $label }}</a>
                    @else
                        <span>{{ $label }}</span>
                    @endif
                @endforeach
            </span>
        </div>
    </div>
</footer>
