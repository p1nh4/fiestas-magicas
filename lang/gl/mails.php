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
    'greeting' => 'Ola :name,',
    'signature' => 'Unha aperta,',
    'signed_by' => ':owner · :business',
    'lead' => [
        'subject' => 'Recibimos a túa petición',
        'preview' => 'Contestámosche en canto vexamos ben as datas.',
        'body' => 'Grazas por escribirnos. Xa temos a túa petición e botámoslle unha ollada en canto poidamos.',
        'summary_title' => 'Isto é o que nos contaches',
        'type' => 'Celebración',
        'date' => 'Data',
        'guests' => 'Convidados',
        'venue' => 'Lugar',
        'no_date' => 'aínda sen data',
        'next' => 'Se mentres tanto che xorde algo, contéstanos a este correo ou escríbenos por WhatsApp: é o máis rápido.',
        'wa' => 'Escribir por WhatsApp',
    ],
    'quote' => [
        'subject' => 'O teu orzamento para :event',
        'preview' => 'Ábreo cando queiras: a ligazón é só túa.',
        'body' => 'Aquí tes o orzamento da túa festa. Ábreo co botón; non fai falta crear ningunha conta.',
        'cta' => 'Ver o orzamento',
        'total' => 'Total',
        'deposit' => 'Sinal para reservar a data',
        'valid' => 'Válido ata o :date',
        'note' => 'Se algo non che encaixa, dinos e preparámosche outra proposta. Non hai ningún compromiso ata que o aceptes.',
        'link_warning' => 'Esta ligazón é persoal. Calquera que a teña pode ver e aceptar o orzamento, así que non a reenvíes a quen non deba.',
    ],
    'deposit' => [
        'subject' => 'Sinal recibido — a data é túa',
        'preview' => 'Xa está reservado. Vémonos na festa.',
        'body' => 'Recibimos o sinal. A data e o material quedan reservados no teu nome.',
        'amount' => 'Sinal recibido',
        'pending' => 'Queda por pagar',
        'when' => 'O resto págase o día do evento.',
        'next' => 'Uns días antes escribímosche para pechar os últimos detalles: horas de montaxe, accesos e quen nos abre a porta.',
    ],
];
