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

    // Para onde vão os avisos internos de pedido novo. Vazio = não se
    // envia aviso nenhum. Nunca se adivinha um endereço: um email mandado
    // para o sítio errado faz pior do que email nenhum — dá a sensação de
    // que a Sol foi avisada quando não foi.
    'alert_email' => env('BUSINESS_ALERT_EMAIL'),

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

    // Eventos. O prefixo entra na referencia que o cliente ve no orcamento
    // e um dia na fatura — mudar isto a meio do ano parte a numeracao.
    'event' => [
        'reference_prefix' => env('BUSINESS_REF_PREFIX', 'FM'),
        // Horario por omissao quando o pedido so traz a data. A Sol corrige
        // no evento; isto e so para nao ter de escrever tudo de raiz.
        'default_start_hour' => (int) env('EVENT_DEFAULT_START_HOUR', 12),
        'default_hours' => (int) env('EVENT_DEFAULT_HOURS', 6),
        // Quantos dias antes se manda o lembrete. Cinco dá tempo para
        // resolver um problema de acessos sem ser ainda tão cedo que a
        // pessoa se esqueça de responder.
        'reminder_days' => (int) env('EVENT_REMINDER_DAYS', 5),
    ],

    'quote' => [
        'deposit_pct' => (int) env('QUOTE_DEPOSIT_PCT', 30),
        'valid_days' => (int) env('QUOTE_VALID_DAYS', 15),
        'vat_rate' => (float) env('VAT_RATE', 21),
    ],
];
