@php
    $locale = app()->getLocale();
    $alternates = \App\Support\Locales::alternates();

    /*
        A navegação era feita de âncoras — #alquiler, #contacto — e as
        âncoras só funcionam na portada. Numa página de zona, "Alquiler"
        levava a /es/zonas/nigran#alquiler, ou seja, a lado nenhum.

        Agora: o que tem página própria aponta para a página; o que vive
        numa secção da portada aponta para a portada MAIS a âncora. Estando
        já na portada, o salto continua a ser instantâneo — o navegador vê
        que o endereço é o mesmo e só desce.
    */
    $home = route('home', ['locale' => $locale]);

    $nav = [
        ['label' => __('site.nav.celebrations'), 'href' => $home.'#celebraciones'],
        ['label' => __('site.nav.works'),        'href' => route('projects.index', ['locale' => $locale])],
        ['label' => __('site.nav.rental'),       'href' => route('rentals.index', ['locale' => $locale])],
        ['label' => __('site.nav.areas'),        'href' => route('areas.index', ['locale' => $locale])],
        ['label' => __('site.nav.about'),        'href' => $home.'#nosotras'],
        ['label' => __('site.nav.contact'),      'href' => $home.'#contacto'],
    ];

    $link = 'border-b border-transparent pb-0.5 text-ink-2 no-underline transition hover:border-rosa hover:text-rosa';
@endphp

<header class="sticky top-0 z-50 border-b border-line bg-ground/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-[1240px] items-center gap-3 px-4 py-2.5 sm:px-6 lg:px-14">
        <x-brand class="mr-auto" />

        <nav class="hidden gap-6 text-[0.95rem] lg:flex" aria-label="{{ __('site.nav.main') }}">
            @foreach ($nav as $entry)
                <a href="{{ $entry['href'] }}" class="{{ $link }}">{{ $entry['label'] }}</a>
            @endforeach
        </nav>

        {{--
            Trocar de idioma é uma ligação normal para a mesma página no
            outro idioma. Nada de JavaScript: assim o Google segue as três
            versões e quem partilha o link partilha o idioma certo.

            Quando a página não existe no outro idioma — um trabalho ainda
            sem título em português, por exemplo — o botão leva à portada
            desse idioma em vez de desaparecer. Perder a navegação é pior
            do que aterrar um degrau acima.
        --}}
        <nav class="flex gap-0.5 text-xs" aria-label="{{ __('site.nav.language') }}">
            @foreach (\App\Support\Locales::SUPPORTED as $code)
                @if ($code === $locale)
                    <span class="rounded bg-rosa-wash px-1.5 py-1 font-bold uppercase tracking-wider text-rosa" aria-current="true">{{ $code }}</span>
                @else
                    <a href="{{ $alternates[$code] ?? route('home', ['locale' => $code]) }}"
                       hreflang="{{ \App\Support\Locales::hreflang($code) }}"
                       title="{{ \App\Support\Locales::nativeName($code) }}"
                       class="rounded px-1.5 py-1 font-bold uppercase tracking-wider text-muted no-underline hover:text-rosa">{{ $code }}</a>
                @endif
            @endforeach
        </nav>

        <x-btn variant="wa" size="sm" href="https://wa.me/{{ config('business.whatsapp') }}" rel="noopener">
            <x-ico name="whatsapp" class="w-4 h-4" />
            <span class="hidden sm:inline">{{ __('site.contact.whatsapp_cta') }}</span>
        </x-btn>

        {{--
            Menu de telemóvel. Feito com <details>, que abre e fecha sozinho
            sem uma linha de JavaScript, é focável pelo teclado e funciona
            mesmo que o JS não carregue. A maior parte das visitas vem do
            Instagram, ou seja, de um telemóvel — até aqui essas visitas
            simplesmente não tinham navegação nenhuma.
        --}}
        <details class="relative lg:hidden">
            <summary class="flex cursor-pointer list-none items-center rounded-lg border border-line px-2.5 py-2 text-ink-2 [&::-webkit-details-marker]:hidden"
                     aria-label="{{ __('site.nav.main') }}">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" class="h-4 w-4" aria-hidden="true">
                    <path d="M3 6h14M3 10h14M3 14h14" stroke-linecap="round" />
                </svg>
            </summary>

            <nav class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-56 rounded-xl border border-line bg-ground p-2 shadow-lg"
                 aria-label="{{ __('site.nav.main') }}">
                @foreach ($nav as $entry)
                    <a href="{{ $entry['href'] }}"
                       class="block rounded-lg px-3 py-2 text-[0.95rem] text-ink-2 no-underline hover:bg-rosa-wash hover:text-rosa">{{ $entry['label'] }}</a>
                @endforeach
            </nav>
        </details>
    </div>
</header>
