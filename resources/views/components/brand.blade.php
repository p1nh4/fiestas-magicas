@props(['class' => ''])

<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'flex flex-col leading-none no-underline '.$class]) }}>
    <span class="font-script text-[1.75rem] leading-[0.95] text-rosa">{{ config('business.name') }}</span>
    <span class="mt-0.5 font-display text-[0.6rem] tracking-[0.22em] uppercase text-muted whitespace-nowrap sm:text-[0.62rem] sm:tracking-[0.28em]">
        {{ config('business.sub') }}
    </span>
</a>
