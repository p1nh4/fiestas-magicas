@php $b = config('business'); @endphp

{{--
    Barra de contacto antes de tudo o resto. É a convenção do setor — a
    Decoraciones Kat e a Globossol fazem o mesmo — e faz sentido: quem
    procura decoração para uma festa quer falar com alguém, não ler um
    slogan.
--}}
<div class="bg-ink text-ground text-[0.8rem]">
    <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-center gap-x-5 gap-y-1 px-[var(--pad,1.1rem)] py-2 sm:px-6 lg:justify-start lg:px-14">
        <a href="tel:{{ $b['phone'] }}" class="inline-flex items-center gap-1.5 no-underline hover:text-oro-soft">
            <x-ico name="phone" class="w-[0.95rem] h-[0.95rem] shrink-0" />
            {{ $b['phone_display'] }}
        </a>

        <span class="hidden opacity-35 lg:inline" aria-hidden="true">·</span>

        <a href="https://wa.me/{{ $b['whatsapp'] }}" rel="noopener" class="inline-flex items-center gap-1.5 no-underline hover:text-oro-soft">
            <x-ico name="whatsapp" class="w-[0.95rem] h-[0.95rem] shrink-0" />
            {{ __('site.util.whatsapp') }}
        </a>

        <span class="hidden opacity-35 lg:inline" aria-hidden="true">·</span>

        <span class="inline-flex items-center gap-1.5">
            <x-ico name="pin" class="w-[0.95rem] h-[0.95rem] shrink-0" />
            {{ __('site.util.where') }}
        </span>

        @if ($b['instagram'])
            <a href="https://instagram.com/{{ $b['instagram'] }}" rel="noopener"
               class="inline-flex items-center gap-1.5 no-underline hover:text-oro-soft lg:ml-auto">
                <x-ico name="instagram" class="w-[0.95rem] h-[0.95rem] shrink-0" />
                <span class="hidden sm:inline">&#64;{{ $b['instagram'] }}</span>
                <span class="sm:hidden">{{ __('site.util.instagram') }}</span>
            </a>
        @endif
    </div>
</div>
