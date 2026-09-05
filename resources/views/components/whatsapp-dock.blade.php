@php $b = config('business'); @endphp

{{--
    Barra fixa só no telemóvel. Neste setor quase toda a gente contacta por
    WhatsApp — o formulário é para quem prefere escrever com calma. Some no
    desktop, onde não faz falta e só tapava conteúdo.
--}}
<div class="fixed inset-x-0 bottom-0 z-50 flex gap-2 border-t border-line bg-ground/95 px-3 py-2 backdrop-blur-md lg:hidden"
     style="padding-bottom: calc(0.5rem + env(safe-area-inset-bottom))">
    <x-btn variant="wa" href="https://wa.me/{{ $b['whatsapp'] }}" rel="noopener" class="flex-1">
        <x-ico name="whatsapp" class="w-4 h-4" />
        {{ __('site.util.whatsapp') }}
    </x-btn>
    <x-btn variant="line" href="tel:{{ $b['phone'] }}" class="flex-1">
        <x-ico name="phone" class="w-4 h-4" />
        {{ __('site.util.phone') }}
    </x-btn>
</div>

{{-- espaço para a barra fixa não tapar o rodapé --}}
<div class="h-[4.2rem] lg:hidden" aria-hidden="true"></div>
