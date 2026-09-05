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
        'new' => 'Novo',
        'contacted' => 'Contactado',
        'quoted' => 'Orzamentado',
        'won' => 'Gañado',
        'lost' => 'Perdido',
        'spam' => 'Spam',
    ],
    'event_status' => [
        'draft' => 'Borrador',
        'quoted' => 'Orzamentado',
        'confirmed' => 'Confirmado',
        'in_progress' => 'En curso',
        'done' => 'Rematado',
        'cancelled' => 'Cancelado',
    ],
    'quote_status' => [
        'draft' => 'Borrador',
        'sent' => 'Enviado',
        'viewed' => 'Visto',
        'accepted' => 'Aceptado',
        'rejected' => 'Rexeitado',
        'expired' => 'Caducado',
    ],
    'reservation_status' => [
        'hold' => 'Reserva temporal',
        'confirmed' => 'Confirmada',
        'cancelled' => 'Cancelada',
    ],
    'payment_kind' => [
        'deposit' => 'Sinal',
        'balance' => 'Resto',
        'extra' => 'Extra',
        'refund' => 'Devolución',
    ],
    'payment_method' => [
        'card' => 'Tarxeta',
        'bizum' => 'Bizum',
        'transfer' => 'Transferencia',
        'cash' => 'Efectivo',
        'other' => 'Outro',
    ],
    'payment_status' => [
        'pending' => 'Pendente',
        'paid' => 'Pagado',
        'failed' => 'Fallado',
        'refunded' => 'Devolto',
    ],
    'document_type' => [
        'quote' => 'Orzamento',
        'proforma' => 'Proforma',
        'receipt' => 'Recibo',
        'invoice' => 'Factura',
        'credit_note' => 'Abono',
    ],
    'price_mode' => [
        'fixed' => 'Prezo fixo',
        'per_guest' => 'Por convidado',
        'per_hour' => 'Por hora',
        'quote' => 'A orzamentar',
    ],
    'category_kind' => [
        'service' => 'Servizo',
        'item' => 'Material',
    ],
    'locale' => [
        'es' => 'Español',
        'gl' => 'Galego',
        'pt' => 'Portugués',
    ],
    'event_type' => [
        'cumpleanos' => 'Aniversario',
        'cumpleanos_infantil' => 'Aniversario infantil',
        'bautizo' => 'Bautizo',
        'comunion' => 'Comuñón',
        'boda' => 'Voda',
        'baby_shower' => 'Baby shower',
        'empresa' => 'Evento de empresa',
        'otro' => 'Outro',
    ],
];
