@props([
    'title' => null,
    'description' => null,
    'image' => null,
])

@php
    $business = config('business');
    $title = $title ?: __('site.meta.home_title');
    $description = $description ?: __('site.meta.home_description');
    $image = $image ?: asset('img/muestras/hero-arco-globos-800.jpg');
    $alternates = \App\Support\Locales::alternates();
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ url()->current() }}">

{{--
    hreflang: diz ao Google que estas três páginas são a mesma coisa em
    idiomas diferentes, e não conteúdo duplicado. Sem isto, as versões em
    galego e português competem com a espanhola em vez de se somarem.
--}}
@foreach ($alternates as $locale => $url)
    <link rel="alternate" hreflang="{{ \App\Support\Locales::hreflang($locale) }}" href="{{ $url }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $alternates[\App\Support\Locales::DEFAULT] ?? url('/') }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $business['name'] }} · {{ $business['sub'] }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:locale" content="{{ str_replace('-', '_', \App\Support\Locales::hreflang(app()->getLocale())) }}">
@foreach ($alternates as $locale => $url)
    @if ($locale !== app()->getLocale())
        <meta property="og:locale:alternate" content="{{ str_replace('-', '_', \App\Support\Locales::hreflang($locale)) }}">
    @endif
@endforeach

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">

{{--
    JSON-LD LocalBusiness. É isto que faz a empresa aparecer no painel
    lateral do Google e nas buscas do tipo "decoración de globos en Baiona".
    Só entram campos que sabemos serem verdade — nada de horários nem de
    avaliações inventadas.
--}}
@php
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $business['name'].' · '.$business['sub'],
        'description' => $description,
        'url' => route('home'),
        'image' => $image,
        'telephone' => $business['phone'],
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $business['address']['street'],
            'addressLocality' => $business['address']['locality'],
            'addressRegion' => $business['address']['region'],
            'postalCode' => $business['address']['postal_code'],
            'addressCountry' => $business['address']['country'],
        ]),
        'areaServed' => array_map(
            fn (string $place) => ['@type' => 'City', 'name' => $place],
            $business['service_areas'],
        ),
        'sameAs' => array_values(array_filter([
            $business['instagram'] ? 'https://instagram.com/'.$business['instagram'] : null,
            $business['facebook'],
        ])),
        'knowsLanguage' => ['es-ES', 'gl-ES', 'pt-PT'],
    ];

    if ($business['opening_hours']) {
        $ld['openingHours'] = $business['opening_hours'];
    }
@endphp

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
