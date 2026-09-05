<?php

declare(strict_types=1);

/*
 * Texto do site. Gerado por gen_site_lang.py, que falha se
 * alguma chave faltar num dos três idiomas.
 */

return [
    'labels' => [
        'name' => 'Tu nombre',
        'contact' => 'Cómo te avisamos',
        'email' => 'Email',
        'phone' => 'Teléfono o WhatsApp',
        'event_type' => 'Tipo de celebración',
        'event_date' => 'Fecha de la fiesta',
        'guests_count' => 'Nº de invitados',
        'venue' => 'Lugar',
        'message' => 'Cuéntanos cómo la imaginas',
        'privacy' => 'He leído y acepto la :link',
        'privacy_link' => 'política de privacidad',
        'submit' => 'Enviar solicitud',
    ],
    'placeholders' => [
        'name' => 'María',
        'email' => 'maria@ejemplo.com',
        'phone' => '600 000 000',
        'venue' => 'Salón, casa, playa…',
        'guests_count' => '40',
        'message' => 'Colores, temática, si has visto algo que te gustó…',
    ],
    'help' => [
        'contact' => 'Con uno de los dos basta.',
        'date' => 'Si aún no la tienes cerrada, déjalo en blanco.',
        'optional' => 'opcional',
    ],
    'attributes' => [
        'name' => 'nombre',
        'email' => 'email',
        'phone' => 'teléfono',
        'event_type' => 'tipo de celebración',
        'event_date' => 'fecha',
        'guests_count' => 'número de invitados',
        'venue' => 'lugar',
        'message' => 'mensaje',
        'privacy' => 'política de privacidad',
    ],
    'errors' => [
        'contact_required' => 'Déjanos un email o un teléfono para poder responderte.',
        'phone_format' => 'Ese teléfono no parece válido. Solo números, espacios y el prefijo.',
        'privacy' => 'Necesitamos tu permiso para guardar estos datos y responderte.',
        'date_past' => 'Esa fecha ya pasó. ¿Querías otro año?',
        'spam' => 'No hemos podido procesar el formulario. Escríbenos por WhatsApp.',
        'title' => 'Revisa estos campos',
    ],
];
