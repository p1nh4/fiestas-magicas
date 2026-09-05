<?php

declare(strict_types=1);

/*
 * Etiquetas dos enums. Gerado por gen_lang.py e verificado contra os valores do schema — nenhum valor pode ficar sem tradução.
 */

return [
    'client_kind' => [
        'person' => 'Particular',
        'company' => 'Empresa',
    ],
    'lead_status' => [
        'new' => 'Nuevo',
        'contacted' => 'Contactado',
        'quoted' => 'Presupuestado',
        'won' => 'Ganado',
        'lost' => 'Perdido',
        'spam' => 'Spam',
    ],
    'event_status' => [
        'draft' => 'Borrador',
        'quoted' => 'Presupuestado',
        'confirmed' => 'Confirmado',
        'in_progress' => 'En curso',
        'done' => 'Finalizado',
        'cancelled' => 'Cancelado',
    ],
    'quote_status' => [
        'draft' => 'Borrador',
        'sent' => 'Enviado',
        'viewed' => 'Visto',
        'accepted' => 'Aceptado',
        'rejected' => 'Rechazado',
        'expired' => 'Caducado',
    ],
    'reservation_status' => [
        'hold' => 'Reserva temporal',
        'confirmed' => 'Confirmada',
        'cancelled' => 'Cancelada',
    ],
    'payment_kind' => [
        'deposit' => 'Señal',
        'balance' => 'Resto',
        'extra' => 'Extra',
        'refund' => 'Devolución',
    ],
    'payment_method' => [
        'card' => 'Tarjeta',
        'bizum' => 'Bizum',
        'transfer' => 'Transferencia',
        'cash' => 'Efectivo',
        'other' => 'Otro',
    ],
    'payment_status' => [
        'pending' => 'Pendiente',
        'paid' => 'Pagado',
        'failed' => 'Fallido',
        'refunded' => 'Devuelto',
    ],
    'document_type' => [
        'quote' => 'Presupuesto',
        'proforma' => 'Proforma',
        'receipt' => 'Recibo',
        'invoice' => 'Factura',
        'credit_note' => 'Abono',
    ],
    'price_mode' => [
        'fixed' => 'Precio fijo',
        'per_guest' => 'Por invitado',
        'per_hour' => 'Por hora',
        'quote' => 'A presupuestar',
    ],
    'category_kind' => [
        'service' => 'Servicio',
        'item' => 'Material',
    ],
    'locale' => [
        'es' => 'Español',
        'gl' => 'Galego',
        'pt' => 'Português',
    ],
    'event_type' => [
        'cumpleanos' => 'Cumpleaños',
        'cumpleanos_infantil' => 'Cumpleaños infantil',
        'bautizo' => 'Bautizo',
        'comunion' => 'Comunión',
        'boda' => 'Boda',
        'baby_shower' => 'Baby shower',
        'empresa' => 'Evento de empresa',
        'otro' => 'Otro',
    ],
];
