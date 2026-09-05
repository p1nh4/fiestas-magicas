<?php

declare(strict_types=1);

/*
 * Texto do orçamento visto pelo cliente. Gerado por
 * gen_quote_lang.py, que falha se faltar uma chave num idioma.
 */

return [
    'kicker' => 'Presupuesto',
    'meta' => [
        'title' => 'Tu presupuesto :number',
    ],
    'for' => 'Para',
    'date' => 'Fecha',
    'venue' => 'Lugar',
    'valid_until' => 'Válido hasta',
    'concept' => 'Concepto',
    'qty' => 'Cant.',
    'unit' => 'Precio',
    'line_total' => 'Total',
    'days' => '{1} 1 día|[2,*] :count días',
    'subtotal' => 'Subtotal',
    'discount' => 'Descuento',
    'tax' => 'IVA (:rate %)',
    'total' => 'Total',
    'deposit' => 'Señal (:pct %)',
    'deposit_for' => 'Señal · :event',
    'accept_explainer' => 'Al aceptar, reservamos la fecha y el material para tu fiesta. Después te pediremos la señal; el resto se paga el día del evento.',
    'accept' => 'Aceptar presupuesto',
    'reject' => 'No me encaja',
    'ask' => 'Preguntar por WhatsApp',
    'accepted_title' => 'Presupuesto aceptado',
    'accepted_body' => 'La fecha y el material quedan reservados a tu nombre.',
    'rejected_body' => 'Has marcado este presupuesto como no válido. Si cambias de idea o quieres otra propuesta, escríbenos.',
    'expired_body' => 'Este presupuesto ya no está disponible. Escríbenos y te preparamos uno nuevo.',
    'pay_explainer' => 'Para dejar la fecha bloqueada falta la señal de :amount.',
    'pay' => 'Pagar :amount',
    'payment_confirmed' => 'Señal recibida. ¡Nos vemos en la fiesta!',
    'all_set' => 'Todo listo. Nos ponemos en contacto contigo unos días antes para cerrar los detalles.',
    'version' => 'Versión :n',
    'errors' => [
        'expired' => 'Este presupuesto ha caducado. Escríbenos y te preparamos uno nuevo.',
        'status' => 'Este presupuesto ya no está a la espera de respuesta.',
        'payment_pending' => 'Todavía no nos consta el pago. Si acabas de pagarlo, espera un momento y vuelve a cargar la página.',
    ],
];
