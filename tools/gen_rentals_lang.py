"""lang/{es,gl,pt}/rentals.php — com a mesma verificacao de paridade de chaves."""
import pathlib
import sys

OUT = pathlib.Path('lang')

R = {}

R['es'] = {
    'kicker': 'Alquiler',
    'index': {
        'title': 'Material que alquilamos',
        'lead': 'Piezas que puedes alquilar sueltas, sin contratar el montaje completo.',
        'meta_title': 'Alquiler de material para fiestas en Baiona y el Val Miñor',
        'meta_description': 'Sillas, photocall, letras iluminadas y soportes de mesa dulce en alquiler. Consulta si están libres en tus fechas.',
        'empty': 'Todavía no hay piezas publicadas para alquiler suelto. Escríbenos y te decimos qué tenemos.',
        'units': 'unidades',
    },
    'show': {
        'meta_title': ':item en alquiler',
        'meta_description': 'Consulta si :item está libre en tus fechas.',
        'stock': 'Tenemos :count en total',
        'per_day': 'por día',
        'on_request': 'a consultar',
        'transport': 'Necesita furgoneta: lo llevamos y lo recogemos nosotros.',
        'check_title': '¿Está libre en tus fechas?',
        'from': 'Desde',
        'to': 'Hasta',
        'quantity': 'Unidades',
        'check': 'Consultar',
        'back': 'Ver todo el material',
    },
    'result': {
        'yes': 'Sí: quedan :free libres del :from al :to.',
        'no_enough': 'Solo quedan :free y pides :wanted.',
        'none': 'En esas fechas no queda ninguna.',
        'margin': 'La cuenta incluye el tiempo de transporte y limpieza entre fiestas, por eso a veces sale menos de lo que parece.',
        'not_a_booking': 'Esto es una consulta, no una reserva. El material queda apartado cuando aceptas el presupuesto — no antes.',
        'ask': 'Pedir presupuesto',
    },
    'errors': {
        'dates': 'No entendemos esas fechas. Vuelve a elegirlas.',
        'order': 'La fecha de vuelta tiene que ser posterior a la de salida.',
        'past': 'Esa fecha ya pasó.',
        'too_long': 'Prueba con un intervalo más corto.',
    },
}

R['gl'] = {
    'kicker': 'Aluguer',
    'index': {
        'title': 'Material que alugamos',
        'lead': 'Pezas que podes alugar soltas, sen contratar a montaxe completa.',
        'meta_title': 'Aluguer de material para festas en Baiona e o Val Miñor',
        'meta_description': 'Cadeiras, photocall, letras iluminadas e soportes de mesa doce en aluguer. Consulta se están libres nas túas datas.',
        'empty': 'Aínda non hai pezas publicadas para aluguer solto. Escríbenos e dicímosche que temos.',
        'units': 'unidades',
    },
    'show': {
        'meta_title': ':item en aluguer',
        'meta_description': 'Consulta se :item está libre nas túas datas.',
        'stock': 'Temos :count en total',
        'per_day': 'por día',
        'on_request': 'a consultar',
        'transport': 'Necesita furgoneta: levámolo e recollémolo nós.',
        'check_title': 'Está libre nas túas datas?',
        'from': 'Desde',
        'to': 'Ata',
        'quantity': 'Unidades',
        'check': 'Consultar',
        'back': 'Ver todo o material',
    },
    'result': {
        'yes': 'Si: quedan :free libres do :from ao :to.',
        'no_enough': 'Só quedan :free e pides :wanted.',
        'none': 'Nesas datas non queda ningunha.',
        'margin': 'A conta inclúe o tempo de transporte e limpeza entre festas, por iso ás veces sae menos do que parece.',
        'not_a_booking': 'Isto é unha consulta, non unha reserva. O material queda apartado cando aceptas o orzamento — non antes.',
        'ask': 'Pedir orzamento',
    },
    'errors': {
        'dates': 'Non entendemos esas datas. Volve escollelas.',
        'order': 'A data de volta ten que ser posterior á de saída.',
        'past': 'Esa data xa pasou.',
        'too_long': 'Proba cun intervalo máis curto.',
    },
}

R['pt'] = {
    'kicker': 'Aluguer',
    'index': {
        'title': 'Material que alugamos',
        'lead': 'Peças que podes alugar à parte, sem contratares a montagem completa.',
        'meta_title': 'Aluguer de material para festas em Baiona e no Val Miñor',
        'meta_description': 'Cadeiras, photocall, letras iluminadas e suportes de mesa doce para alugar. Vê se estão livres nas tuas datas.',
        'empty': 'Ainda não há peças publicadas para aluguer à parte. Escreve-nos e dizemos-te o que temos.',
        'units': 'unidades',
    },
    'show': {
        'meta_title': ':item para alugar',
        'meta_description': 'Vê se :item está livre nas tuas datas.',
        'stock': 'Temos :count no total',
        'per_day': 'por dia',
        'on_request': 'a consultar',
        'transport': 'Precisa de carrinha: levamos e trazemos nós.',
        'check_title': 'Está livre nas tuas datas?',
        'from': 'De',
        'to': 'Até',
        'quantity': 'Unidades',
        'check': 'Ver',
        'back': 'Ver todo o material',
    },
    'result': {
        'yes': 'Sim: ficam :free livres de :from a :to.',
        'no_enough': 'Só ficam :free e pedes :wanted.',
        'none': 'Nessas datas não fica nenhuma.',
        'margin': 'A conta inclui o tempo de transporte e limpeza entre festas, por isso às vezes dá menos do que parece.',
        'not_a_booking': 'Isto é uma consulta, não uma reserva. O material fica preso quando aceitares o orçamento — não antes.',
        'ask': 'Pedir orçamento',
    },
    'errors': {
        'dates': 'Não percebemos essas datas. Escolhe outra vez.',
        'order': 'A data de volta tem de ser depois da de saída.',
        'past': 'Essa data já passou.',
        'too_long': 'Experimenta um intervalo mais curto.',
    },
}


def keys(d, p=''):
    out = set()
    for k, v in d.items():
        path = f'{p}{k}'
        out |= keys(v, path + '.') if isinstance(v, dict) else {path}
    return out


ref = keys(R['es'])
problems = []
for loc, tree in R.items():
    got = keys(tree)
    problems += [f'falta {loc}.{k}' for k in sorted(ref - got)]
    problems += [f'{loc}.{k} a mais' for k in sorted(got - ref)]

if problems:
    print('PROBLEMAS:')
    for p in problems:
        print('  -', p)
    sys.exit(1)


def php(d, indent=1):
    pad = '    ' * indent
    lines = []
    for k, v in d.items():
        if isinstance(v, dict):
            lines.append(f"{pad}'{k}' => [\n{php(v, indent + 1)}\n{pad}],")
        else:
            esc = v.replace('\\', '\\\\').replace("'", "\\'")
            lines.append(f"{pad}'{k}' => '{esc}',")
    return '\n'.join(lines)


for loc, tree in R.items():
    p = OUT / loc / 'rentals.php'
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(
        "<?php\n\ndeclare(strict_types=1);\n\n"
        "/*\n * Catalogo de aluguer. Gerado por gen_rentals_lang.py, que falha\n"
        " * se faltar uma chave num idioma.\n */\n\n"
        f"return [\n{php(tree)}\n];\n", encoding='utf-8')

print(f'rentals.php ×3 idiomas ({len(ref)} chaves cada) — nenhuma em falta')
