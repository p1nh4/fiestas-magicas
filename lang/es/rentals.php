<?php

declare(strict_types=1);

/*
 * Catalogo de aluguer. Gerado por gen_rentals_lang.py, que falha
 * se faltar uma chave num idioma.
 */

return [
    'kicker' => 'Alquiler',
    'index' => [
        'title' => 'Material que alquilamos',
        'lead' => 'Piezas que puedes alquilar sueltas, sin contratar el montaje completo.',
        'meta_title' => 'Alquiler de material para fiestas en Baiona y el Val Miñor',
        'meta_description' => 'Sillas, photocall, letras iluminadas y soportes de mesa dulce en alquiler. Consulta si están libres en tus fechas.',
        'empty' => 'Todavía no hay piezas publicadas para alquiler suelto. Escríbenos y te decimos qué tenemos.',
        'units' => 'unidades',
    ],
    'show' => [
        'meta_title' => ':item en alquiler',
        'meta_description' => 'Consulta si :item está libre en tus fechas.',
        'stock' => 'Tenemos :count en total',
        'per_day' => 'por día',
        'on_request' => 'a consultar',
        'transport' => 'Necesita furgoneta: lo llevamos y lo recogemos nosotros.',
        'check_title' => '¿Está libre en tus fechas?',
        'from' => 'Desde',
        'to' => 'Hasta',
        'quantity' => 'Unidades',
        'check' => 'Consultar',
        'back' => 'Ver todo el material',
    ],
    'result' => [
        'yes' => 'Sí: quedan :free libres del :from al :to.',
        'no_enough' => 'Solo quedan :free y pides :wanted.',
        'none' => 'En esas fechas no queda ninguna.',
        'margin' => 'La cuenta incluye el tiempo de transporte y limpieza entre fiestas, por eso a veces sale menos de lo que parece.',
        'not_a_booking' => 'Esto es una consulta, no una reserva. El material queda apartado cuando aceptas el presupuesto — no antes.',
        'ask' => 'Pedir presupuesto',
    ],
    'errors' => [
        'dates' => 'No entendemos esas fechas. Vuelve a elegirlas.',
        'order' => 'La fecha de vuelta tiene que ser posterior a la de salida.',
        'past' => 'Esa fecha ya pasó.',
        'too_long' => 'Prueba con un intervalo más corto.',
    ],
];
