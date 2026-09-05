@props([
    'variant' => 'rosa',
    'size' => 'md',
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-full font-semibold whitespace-nowrap '
          . 'border transition duration-200 ease-[var(--ease-out-soft)] '
          . 'hover:-translate-y-px active:translate-y-0';

    $variants = [
        'rosa'  => 'bg-rosa text-white border-transparent hover:bg-rosa-deep',
        'wa'    => 'bg-whatsapp text-white border-transparent hover:brightness-110',
        'line'  => 'bg-surface text-ink border-line-2 hover:border-rosa hover:text-rosa',
        'ghost' => 'bg-transparent text-ink border-transparent hover:text-rosa',
    ];

    $sizes = [
        'sm' => 'px-3.5 py-2 text-sm',
        'md' => 'px-[1.15rem] py-[0.68rem] text-[0.94rem]',
        'lg' => 'px-6 py-[0.85rem] text-base',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['rosa']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>{{ $slot }}</button>
@endif
