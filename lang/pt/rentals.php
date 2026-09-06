<?php

declare(strict_types=1);

/*
 * Catalogo de aluguer. Gerado por gen_rentals_lang.py, que falha
 * se faltar uma chave num idioma.
 */

return [
    'kicker' => 'Aluguer',
    'index' => [
        'title' => 'Material que alugamos',
        'lead' => 'Peças que podes alugar à parte, sem contratares a montagem completa.',
        'meta_title' => 'Aluguer de material para festas em Baiona e no Val Miñor',
        'meta_description' => 'Cadeiras, photocall, letras iluminadas e suportes de mesa doce para alugar. Vê se estão livres nas tuas datas.',
        'empty' => 'Ainda não há peças publicadas para aluguer à parte. Escreve-nos e dizemos-te o que temos.',
        'units' => 'unidades',
    ],
    'show' => [
        'meta_title' => ':item para alugar',
        'meta_description' => 'Vê se :item está livre nas tuas datas.',
        'stock' => 'Temos :count no total',
        'per_day' => 'por dia',
        'on_request' => 'a consultar',
        'transport' => 'Precisa de carrinha: levamos e trazemos nós.',
        'check_title' => 'Está livre nas tuas datas?',
        'from' => 'De',
        'to' => 'Até',
        'quantity' => 'Unidades',
        'check' => 'Ver',
        'back' => 'Ver todo o material',
    ],
    'result' => [
        'yes' => 'Sim: ficam :free livres de :from a :to.',
        'no_enough' => 'Só ficam :free e pedes :wanted.',
        'none' => 'Nessas datas não fica nenhuma.',
        'margin' => 'A conta inclui o tempo de transporte e limpeza entre festas, por isso às vezes dá menos do que parece.',
        'not_a_booking' => 'Isto é uma consulta, não uma reserva. O material fica preso quando aceitares o orçamento — não antes.',
        'ask' => 'Pedir orçamento',
    ],
    'errors' => [
        'dates' => 'Não percebemos essas datas. Escolhe outra vez.',
        'order' => 'A data de volta tem de ser depois da de saída.',
        'past' => 'Essa data já passou.',
        'too_long' => 'Experimenta um intervalo mais curto.',
    ],
];
