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
        'phone' => 'Teléfono ou WhatsApp',
        'event_type' => 'Tipo de celebración',
        'event_date' => 'Data da festa',
        'guests_count' => 'Nº de convidados',
        'venue' => 'Lugar',
        'message' => 'Cóntanos como a imaxinas',
        'privacy' => 'Lin e acepto a :link',
        'privacy_link' => 'política de privacidade',
        'submit' => 'Enviar solicitude',
    ],
    'placeholders' => [
        'name' => 'María',
        'email' => 'maria@exemplo.com',
        'phone' => '600 000 000',
        'venue' => 'Salón, casa, praia…',
        'guests_count' => '40',
        'message' => 'Cores, temática, se viches algo que che gustou…',
    ],
    'help' => [
        'contact' => 'Cun dos dous abonda.',
        'date' => 'Se aínda non a tes pechada, déixao en branco.',
        'optional' => 'opcional',
    ],
    'attributes' => [
        'name' => 'nome',
        'email' => 'email',
        'phone' => 'teléfono',
        'event_type' => 'tipo de celebración',
        'event_date' => 'data',
        'guests_count' => 'número de convidados',
        'venue' => 'lugar',
        'message' => 'mensaxe',
        'privacy' => 'política de privacidade',
    ],
    'errors' => [
        'contact_required' => 'Déixanos un email ou un teléfono para poder responderche.',
        'phone_format' => 'Ese teléfono non parece válido. Só números, espazos e o prefixo.',
        'privacy' => 'Precisamos do teu permiso para gardar estes datos e responderche.',
        'date_past' => 'Esa data xa pasou. Querías outro ano?',
        'spam' => 'Non puidemos procesar o formulario. Escríbenos por WhatsApp.',
        'title' => 'Revisa estes campos',
    ],
];
