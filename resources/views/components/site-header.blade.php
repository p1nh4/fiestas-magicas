@php $alternates = \App\Support\Locales::alternates(); @endphp

<header class="sticky top-0 z-50 border-b border-line bg-ground/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-[1240px] items-center gap-3 px-4 py-2.5 sm:px-6 lg:px-14">
        <x-brand class="mr-auto" />

        <nav class="hidden gap-6 text-[0.95rem] lg:flex" aria-label="{{ __('site.nav.main') }}">
            <a href="#celebraciones" class="border-b border-transparent pb-0.5 text-ink-2 no-underline transition hover:border-rosa hover:text-rosa">{{ __('site.nav.celebrations') }}</a>
            <a href="#nosotras"      class="border-b border-transparent pb-0.5 text-ink-2 no-underline transition hover:border-rosa hover:text-rosa">{{ __('site.nav.about') }}</a>
            <a href="#como"          class="border-b border-transparent pb-0.5 text-ink-2 no-underline transition hover:border-rosa hover:text-rosa">{{ __('site.nav.process') }}</a>
            <a href="#alquiler"      class="border-b border-transparent pb-0.5 text-ink-2 no-underline transition hover:border-rosa hover:text-rosa">{{ __('site.nav.rental') }}</a>
            <a href="#contacto"      class="border-b border-transparent pb-0.5 text-ink-2 no-underline transition hover:border-rosa hover:text-rosa">{{ __('site.nav.contact') }}</a>
        </nav>

        {{--
            Trocar de idioma é uma ligação normal para a mesma página no
            outro idioma. Nada de JavaScript: assim o Google segue as três
            versões e quem partilha o link partilha o idioma certo.
        --}}
        <nav class="flex gap-0.5 text-xs" aria-label="{{ __('site.nav.language') }}">
            @foreach ($alternates as $code => $url)
                @if ($code === app()->getLocale())
                    <span class="rounded px-1.5 py-1 font-bold uppercase tracking-wider text-rosa bg-rosa-wash" aria-current="true">{{ $code }}</span>
                @else
                    <a href="{{ $url }}" hreflang="{{ \App\Support\Locales::hreflang($code) }}"
                       title="{{ \App\Support\Locales::nativeName($code) }}"
                       class="rounded px-1.5 py-1 font-bold uppercase tracking-wider text-muted no-underline hover:text-rosa">{{ $code }}</a>
                @endif
            @endforeach
        </nav>

        <x-btn variant="wa" size="sm" href="https://wa.me/{{ config('business.whatsapp') }}" rel="noopener">
            <x-ico name="whatsapp" class="w-4 h-4" />
            <span class="hidden sm:inline">{{ __('site.contact.whatsapp_cta') }}</span>
        </x-btn>
    </div>
</header>
