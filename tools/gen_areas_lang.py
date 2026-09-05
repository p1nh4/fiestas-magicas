"""lang/{es,gl,pt}/areas.php — com a mesma verificacao de paridade de chaves.

O texto FIXO das paginas de zona (titulos, rotulos, chamadas). O texto
PROPRIO de cada concelho nao esta aqui: esta na base de dados, escrito pela
Sol, e sem ele a zona nao se publica.
"""
import pathlib
import sys

OUT = pathlib.Path('f5/lang')

A = {}

A['es'] = {
    'kicker': 'Dónde trabajamos',
    'index': {
        'title': 'Zonas donde montamos fiestas',
        'lead': 'Salimos de Baiona. El Val Miñor y el Baixo Miño están a un paso, y cruzar a Portugal tampoco es viaje.',
        'meta_title': 'Decoración de fiestas en el Val Miñor, Vigo y el norte de Portugal',
        'meta_description': 'Decoramos cumpleaños, bautizos, comuniones y bodas en Baiona, Nigrán, Gondomar, Vigo y el norte de Portugal.',
        'more': 'También nos desplazamos a',
        'ask': '¿Tu sitio no está en la lista? Pregúntanos igualmente.',
        'empty': 'Estamos preparando esta sección. Mientras tanto, escríbenos y te decimos si llegamos a tu sitio.',
    },
    'show': {
        'meta_title': 'Decoración de fiestas en :area',
        'meta_description': 'Globos, mesas dulces y photocall para cumpleaños, bautizos, comuniones y bodas en :area.',
        'distance': 'A :distance de Baiona',
        'travel': ':minutes minutos de camino',
        'services_title': 'Lo que montamos en :area',
        'projects_title': 'Fiestas que hemos montado en :area',
        'faq_title': 'Preguntas frecuentes',
        'cta_title': 'Cuéntanos tu fiesta en :area',
        'back': 'Ver todas las zonas',
    },
}

A['gl'] = {
    'kicker': 'Onde traballamos',
    'index': {
        'title': 'Zonas onde montamos festas',
        'lead': 'Saímos de Baiona. O Val Miñor e o Baixo Miño están a un paso, e cruzar a Portugal tampouco é viaxe.',
        'meta_title': 'Decoración de festas no Val Miñor, Vigo e o norte de Portugal',
        'meta_description': 'Decoramos aniversarios, bautizos, comuñóns e vodas en Baiona, Nigrán, Gondomar, Vigo e o norte de Portugal.',
        'more': 'Tamén nos desprazamos a',
        'ask': 'O teu sitio non está na lista? Pregúntanos igual.',
        'empty': 'Estamos a preparar esta sección. Mentres tanto, escríbenos e dicímosche se chegamos ao teu sitio.',
    },
    'show': {
        'meta_title': 'Decoración de festas en :area',
        'meta_description': 'Globos, mesas doces e photocall para aniversarios, bautizos, comuñóns e vodas en :area.',
        'distance': 'A :distance de Baiona',
        'travel': ':minutes minutos de camiño',
        'services_title': 'O que montamos en :area',
        'projects_title': 'Festas que montamos en :area',
        'faq_title': 'Preguntas frecuentes',
        'cta_title': 'Cóntanos a túa festa en :area',
        'back': 'Ver todas as zonas',
    },
}

A['pt'] = {
    'kicker': 'Onde trabalhamos',
    'index': {
        'title': 'Zonas onde montamos festas',
        'lead': 'Saímos de Baiona. O Val Miñor e o Baixo Miño ficam à porta, e atravessar para Portugal também não é viagem.',
        'meta_title': 'Decoração de festas no Val Miñor, Vigo e no norte de Portugal',
        'meta_description': 'Decoramos aniversários, batizados, comunhões e casamentos em Baiona, Nigrán, Gondomar, Vigo e no norte de Portugal.',
        'more': 'Também nos deslocamos a',
        'ask': 'O teu local não está na lista? Pergunta na mesma.',
        'empty': 'Estamos a preparar esta secção. Entretanto, escreve-nos e dizemos-te se chegamos ao teu local.',
    },
    'show': {
        'meta_title': 'Decoração de festas em :area',
        'meta_description': 'Balões, mesas doces e photocall para aniversários, batizados, comunhões e casamentos em :area.',
        'distance': 'A :distance de Baiona',
        'travel': ':minutes minutos de caminho',
        'services_title': 'O que montamos em :area',
        'projects_title': 'Festas que montámos em :area',
        'faq_title': 'Perguntas frequentes',
        'cta_title': 'Conta-nos a tua festa em :area',
        'back': 'Ver todas as zonas',
    },
}


def keys(d, p=''):
    out = set()
    for k, v in d.items():
        path = f'{p}{k}'
        out |= keys(v, path + '.') if isinstance(v, dict) else {path}
    return out


ref = keys(A['es'])
problems = []
for loc, tree in A.items():
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


for loc, tree in A.items():
    p = OUT / loc / 'areas.php'
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(
        "<?php\n\ndeclare(strict_types=1);\n\n"
        "/*\n * Texto fixo das paginas de zona. Gerado por gen_areas_lang.py,\n"
        " * que falha se faltar uma chave num idioma.\n *\n"
        " * O texto proprio de cada concelho NAO esta aqui: vive na base de\n"
        " * dados, escrito a mao, e sem ele a zona nao se publica.\n */\n\n"
        f"return [\n{php(tree)}\n];\n", encoding='utf-8')

print(f'areas.php ×3 idiomas ({len(ref)} chaves cada) — nenhuma em falta')
