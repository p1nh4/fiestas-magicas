@props([
    'name',
    'alt',
    'class' => 'w-full h-full object-cover',
    'loading' => 'lazy',
    'width' => 800,
    'height' => null,
])

{{--
    <picture> com WebP e JPG. O WebP pesa cerca de metade e é suportado em
    tudo o que interessa; o JPG fica como rede de segurança.

    Estas são imagens de exemplo. Ao substituí-las pelas fotos reais da
    Sol, basta manter os nomes: public/img/muestras/<nome>-800.webp e .jpg
--}}
<picture>
    <source srcset="{{ asset("img/muestras/{$name}-800.webp") }}" type="image/webp">
    <img
        src="{{ asset("img/muestras/{$name}-800.jpg") }}"
        alt="{{ $alt }}"
        loading="{{ $loading }}"
        decoding="async"
        @if($loading === 'eager') fetchpriority="high" @endif
        width="{{ $width }}"
        @if($height) height="{{ $height }}" @endif
        {{ $attributes->merge(['class' => $class]) }}
    >
</picture>
