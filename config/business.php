<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dados da empresa
|--------------------------------------------------------------------------
| Um sítio só. O telefone aparece na barra de topo, no rodapé, no botão
| fixo do telemóvel, no JSON-LD e nos emails — se estiver escrito em seis
| sítios, um dia mudam cinco.
|
| Tudo o que aqui está é real e verificado no perfil da empresa.
| O que não sabemos fica a null e as views escondem-no.
*/

return [
    'name' => 'DecorArte',
    'sub' => 'Fiestas Mágicas',
    'owner' => 'Sol Fernández',

    'phone' => env('BUSINESS_PHONE', '+34684291843'),
    'phone_display' => '684 291 843',
    'whatsapp' => env('BUSINESS_WHATSAPP', '34684291843'),
    'email' => env('BUSINESS_EMAIL'),

    'instagram' => env('BUSINESS_INSTAGRAM', 'fiestas_magicas_en_galicia'),
    'facebook' => env('BUSINESS_FACEBOOK'),

    'address' => [
        'locality' => env('BUSINESS_CITY', 'Sabarís, Baiona'),
        'region' => 'Pontevedra',
        'postal_code' => env('BUSINESS_POSTAL_CODE', '36393'),
        'country' => 'ES',
        // Morada da rua ainda por confirmar com a Sol. Fica a null de
        // propósito: meia morada num JSON-LD é pior do que morada nenhuma.
        'street' => env('BUSINESS_STREET'),
    ],

    // Concelhos onde a empresa se desloca. Alimenta o areaServed do
    // JSON-LD, que é o que faz aparecer nas buscas "decoración de globos
    // en <sítio>".
    'service_areas' => [
        'Baiona', 'Nigrán', 'Gondomar', 'Vigo', 'Tui', 'A Guarda',
        'O Porriño', 'Ponteareas', 'Pontevedra', 'Viana do Castelo', 'Valença',
    ],

    // Horário por confirmar. Enquanto for null, o site mostra
    // "por confirmar" em vez de inventar um horário.
    'opening_hours' => null,

    'quote' => [
        'deposit_pct' => (int) env('QUOTE_DEPOSIT_PCT', 30),
        'valid_days' => (int) env('QUOTE_VALID_DAYS', 15),
        'vat_rate' => (float) env('VAT_RATE', 21),
    ],
];
