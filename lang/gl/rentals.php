<?php

declare(strict_types=1);

/*
 * Catalogo de aluguer. Gerado por gen_rentals_lang.py, que falha
 * se faltar uma chave num idioma.
 */

return [
    'kicker' => 'Aluguer',
    'index' => [
        'title' => 'Material que alugamos',
        'lead' => 'Pezas que podes alugar soltas, sen contratar a montaxe completa.',
        'meta_title' => 'Aluguer de material para festas en Baiona e o Val Miñor',
        'meta_description' => 'Cadeiras, photocall, letras iluminadas e soportes de mesa doce en aluguer. Consulta se están libres nas túas datas.',
        'empty' => 'Aínda non hai pezas publicadas para aluguer solto. Escríbenos e dicímosche que temos.',
        'units' => 'unidades',
    ],
    'show' => [
        'meta_title' => ':item en aluguer',
        'meta_description' => 'Consulta se :item está libre nas túas datas.',
        'stock' => 'Temos :count en total',
        'per_day' => 'por día',
        'on_request' => 'a consultar',
        'transport' => 'Necesita furgoneta: levámolo e recollémolo nós.',
        'check_title' => 'Está libre nas túas datas?',
        'from' => 'Desde',
        'to' => 'Ata',
        'quantity' => 'Unidades',
        'check' => 'Consultar',
        'back' => 'Ver todo o material',
    ],
    'result' => [
        'yes' => 'Si: quedan :free libres do :from ao :to.',
        'no_enough' => 'Só quedan :free e pides :wanted.',
        'none' => 'Nesas datas non queda ningunha.',
        'margin' => 'A conta inclúe o tempo de transporte e limpeza entre festas, por iso ás veces sae menos do que parece.',
        'not_a_booking' => 'Isto é unha consulta, non unha reserva. O material queda apartado cando aceptas o orzamento — non antes.',
        'ask' => 'Pedir orzamento',
    ],
    'errors' => [
        'dates' => 'Non entendemos esas datas. Volve escollelas.',
        'order' => 'A data de volta ten que ser posterior á de saída.',
        'past' => 'Esa data xa pasou.',
        'too_long' => 'Proba cun intervalo máis curto.',
    ],
];
