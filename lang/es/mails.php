<?php

declare(strict_types=1);

/*
 * Emails que o cliente recebe. Gerado por gen_mails_lang.py,
 * que falha se faltar uma chave num idioma.
 *
 * O aviso interno para a Sol nao esta aqui: vai sempre em espanhol,
 * porque e ela que o le.
 */

return [
    'greeting' => 'Hola :name,',
    'signature' => 'Un abrazo,',
    'signed_by' => ':owner · :business',
    'lead' => [
        'subject' => 'Hemos recibido tu petición',
        'preview' => 'Te contestamos en cuanto veamos bien las fechas.',
        'body' => 'Gracias por escribirnos. Ya tenemos tu petición y le echamos un vistazo en cuanto podamos.',
        'summary_title' => 'Esto es lo que nos has contado',
        'type' => 'Celebración',
        'date' => 'Fecha',
        'guests' => 'Invitados',
        'venue' => 'Lugar',
        'no_date' => 'todavía sin fecha',
        'next' => 'Si mientras tanto te surge algo, contéstanos a este correo o escríbenos por WhatsApp: es lo más rápido.',
        'wa' => 'Escribir por WhatsApp',
    ],
    'quote' => [
        'subject' => 'Tu presupuesto para :event',
        'preview' => 'Ábrelo cuando quieras: el enlace es solo tuyo.',
        'body' => 'Aquí tienes el presupuesto de tu fiesta. Ábrelo con el botón; no hace falta crear ninguna cuenta.',
        'cta' => 'Ver el presupuesto',
        'total' => 'Total',
        'deposit' => 'Señal para reservar la fecha',
        'valid' => 'Válido hasta el :date',
        'note' => 'Si algo no te encaja, dínoslo y te preparamos otra propuesta. No hay ningún compromiso hasta que lo aceptes.',
        'link_warning' => 'Este enlace es personal. Cualquiera que lo tenga puede ver y aceptar el presupuesto, así que no lo reenvíes a quien no deba.',
    ],
    'deposit' => [
        'subject' => 'Señal recibida — la fecha es tuya',
        'preview' => 'Ya está reservado. Nos vemos en la fiesta.',
        'body' => 'Hemos recibido la señal. La fecha y el material quedan reservados a tu nombre.',
        'amount' => 'Señal recibida',
        'pending' => 'Queda por pagar',
        'when' => 'El resto se paga el día del evento.',
        'next' => 'Unos días antes te escribimos para cerrar los últimos detalles: horas de montaje, accesos y quién nos abre la puerta.',
    ],
];
