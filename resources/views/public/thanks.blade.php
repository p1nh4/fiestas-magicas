<x-layouts.public :title="__('site.thanks.title')">
    <section class="mx-auto flex max-w-[60ch] flex-col items-start gap-5 px-4 py-20 sm:px-6 md:py-28 lg:px-14">
        <p class="kicker">{{ config('business.name') }}</p>
        <h1 class="text-step-3">{{ __('site.thanks.title') }}</h1>
        <hr class="h-0.5 w-10 border-0 bg-oro">
        <p class="text-ink-2">{{ __('site.thanks.lead') }}</p>
        <p class="text-ink-2">{{ __('site.thanks.meanwhile') }}</p>

        <div class="mt-2 flex flex-wrap gap-2.5">
            <x-btn variant="wa" size="lg" href="https://wa.me/{{ config('business.whatsapp') }}" rel="noopener">
                <x-ico name="whatsapp" class="w-[1.05rem] h-[1.05rem]" />
                {{ __('site.util.whatsapp') }}
            </x-btn>
            <x-btn variant="line" size="lg" href="{{ route('home') }}">{{ __('site.thanks.back') }}</x-btn>
        </div>
    </section>
</x-layouts.public>
