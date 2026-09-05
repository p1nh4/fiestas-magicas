@props([
    'kicker' => null,
    'title' => null,
    'lead' => null,
    'level' => 'h2',
])

<div {{ $attributes->merge(['class' => 'flex max-w-[60ch] flex-col gap-2 mb-8 md:mb-10']) }}>
    @if ($kicker)
        <p class="kicker">{{ $kicker }}</p>
    @endif

    @if ($title)
        <{{ $level }} class="text-step-3">{{ $title }}</{{ $level }}>
    @endif

    <hr class="mt-1 h-0.5 w-10 border-0 bg-oro">

    @if ($lead)
        <p class="mt-2 max-w-[56ch] text-ink-2">{{ $lead }}</p>
    @endif

    {{ $slot }}
</div>
