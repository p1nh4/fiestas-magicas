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
        'quoted' => 'Orçamentado',
        'won' => 'Ganho',
        'lost' => 'Perdido',
        'spam' => 'Spam',
    ],
    'event_status' => [
        'draft' => 'Rascunho',
        'quoted' => 'Orçamentado',
        'confirmed' => 'Confirmado',
        'in_progress' => 'Em curso',
        'done' => 'Concluído',
        'cancelled' => 'Cancelado',
    ],
    'quote_status' => [
        'draft' => 'Rascunho',
        'sent' => 'Enviado',
        'viewed' => 'Visto',
        'accepted' => 'Aceite',
        'rejected' => 'Recusado',
        'expired' => 'Expirado',
    ],
    'reservation_status' => [
        'hold' => 'Reserva temporária',
        'confirmed' => 'Confirmada',
        'cancelled' => 'Cancelada',
    ],
    'payment_kind' => [
        'deposit' => 'Sinal',
        'balance' => 'Restante',
        'extra' => 'Extra',
        'refund' => 'Reembolso',
    ],
    'payment_method' => [
        'card' => 'Cartão',
        'bizum' => 'Bizum',
        'transfer' => 'Transferência',
        'cash' => 'Numerário',
        'other' => 'Outro',
    ],
    'payment_status' => [
        'pending' => 'Pendente',
        'paid' => 'Pago',
        'failed' => 'Falhou',
        'refunded' => 'Reembolsado',
    ],
    'document_type' => [
        'quote' => 'Orçamento',
        'proforma' => 'Proforma',
        'receipt' => 'Recibo',
        'invoice' => 'Fatura',
        'credit_note' => 'Nota de crédito',
    ],
    'price_mode' => [
        'fixed' => 'Preço fixo',
        'per_guest' => 'Por convidado',
        'per_hour' => 'Por hora',
        'quote' => 'Sob orçamento',
    ],
    'category_kind' => [
        'service' => 'Serviço',
        'item' => 'Material',
    ],
    'locale' => [
        'es' => 'Espanhol',
        'gl' => 'Galego',
        'pt' => 'Português',
    ],
    'event_type' => [
        'cumpleanos' => 'Aniversário',
        'cumpleanos_infantil' => 'Aniversário infantil',
        'bautizo' => 'Batizado',
        'comunion' => 'Comunhão',
        'boda' => 'Casamento',
        'baby_shower' => 'Chá de bebé',
        'empresa' => 'Evento de empresa',
        'otro' => 'Outro',
    ],
];
