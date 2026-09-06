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
    'greeting' => 'Olá :name,',
    'signature' => 'Um abraço,',
    'signed_by' => ':owner · :business',
    'lead' => [
        'subject' => 'Recebemos o teu pedido',
        'preview' => 'Respondemos assim que virmos bem as datas.',
        'body' => 'Obrigada por nos escreveres. Já temos o teu pedido e vamos vê-lo assim que pudermos.',
        'summary_title' => 'Isto foi o que nos contaste',
        'type' => 'Celebração',
        'date' => 'Data',
        'guests' => 'Convidados',
        'venue' => 'Local',
        'no_date' => 'ainda sem data',
        'next' => 'Se entretanto te surgir alguma coisa, responde a este email ou escreve por WhatsApp: é o mais rápido.',
        'wa' => 'Escrever por WhatsApp',
    ],
    'quote' => [
        'subject' => 'O teu orçamento para :event',
        'preview' => 'Abre quando quiseres: a ligação é só tua.',
        'body' => 'Aqui tens o orçamento da tua festa. Abre no botão; não é preciso criar conta nenhuma.',
        'cta' => 'Ver o orçamento',
        'total' => 'Total',
        'deposit' => 'Sinal para reservar a data',
        'valid' => 'Válido até :date',
        'note' => 'Se alguma coisa não te servir, diz-nos e preparamos outra proposta. Não há compromisso nenhum até aceitares.',
        'link_warning' => 'Esta ligação é pessoal. Quem a tiver pode ver e aceitar o orçamento, por isso não a reencaminhes a quem não deve.',
    ],
    'deposit' => [
        'subject' => 'Sinal recebido — a data é tua',
        'preview' => 'Está reservado. Até à festa.',
        'body' => 'Recebemos o sinal. A data e o material ficam reservados em teu nome.',
        'amount' => 'Sinal recebido',
        'pending' => 'Falta pagar',
        'when' => 'O resto paga-se no dia do evento.',
        'next' => 'Uns dias antes escrevemos para fechar os últimos pormenores: horas de montagem, acessos e quem nos abre a porta.',
    ],
];
