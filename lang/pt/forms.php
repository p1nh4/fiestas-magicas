<?php

declare(strict_types=1);

/*
 * Texto do site. Gerado por gen_site_lang.py, que falha se
 * alguma chave faltar num dos três idiomas.
 */

return [
    'labels' => [
        'name' => 'O teu nome',
        'contact' => 'Como te avisamos',
        'email' => 'Email',
        'phone' => 'Telefone ou WhatsApp',
        'event_type' => 'Tipo de celebração',
        'event_date' => 'Data da festa',
        'guests_count' => 'N.º de convidados',
        'venue' => 'Local',
        'message' => 'Conta-nos como a imaginas',
        'privacy' => 'Li e aceito a :link',
        'privacy_link' => 'política de privacidade',
        'submit' => 'Enviar pedido',
    ],
    'placeholders' => [
        'name' => 'Maria',
        'email' => 'maria@exemplo.com',
        'phone' => '910 000 000',
        'venue' => 'Salão, casa, praia…',
        'guests_count' => '40',
        'message' => 'Cores, tema, se viste algo de que gostaste…',
    ],
    'help' => [
        'contact' => 'Basta um dos dois.',
        'date' => 'Se ainda não estiver fechada, deixa em branco.',
        'optional' => 'opcional',
    ],
    'attributes' => [
        'name' => 'nome',
        'email' => 'email',
        'phone' => 'telefone',
        'event_type' => 'tipo de celebração',
        'event_date' => 'data',
        'guests_count' => 'número de convidados',
        'venue' => 'local',
        'message' => 'mensagem',
        'privacy' => 'política de privacidade',
    ],
    'errors' => [
        'contact_required' => 'Deixa-nos um email ou um telefone para podermos responder.',
        'phone_format' => 'Esse telefone não parece válido. Só números, espaços e o indicativo.',
        'privacy' => 'Precisamos da tua autorização para guardar estes dados e responder.',
        'date_past' => 'Essa data já passou. Querias outro ano?',
        'spam' => 'Não foi possível processar o formulário. Escreve-nos por WhatsApp.',
        'required' => 'Este campo é obrigatório.',
        'email_format' => 'Esse email não parece válido. Falta a @ ou o ponto?',
        'title' => 'Revê estes campos',
    ],
];
