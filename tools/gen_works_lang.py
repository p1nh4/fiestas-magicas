"""lang/{es,gl,pt}/works.php e services.php — com verificacao de paridade.

O texto FIXO do portefolio e das paginas de servico. O texto proprio de
cada trabalho e de cada servico nao esta aqui: esta na base de dados,
escrito pela Sol, em tres idiomas.

Corre-se da raiz do projeto:

    python3 tools/gen_works_lang.py

Falha, e nao escreve nada, se faltar uma chave num dos idiomas.
"""
import pathlib
import sys

OUT = pathlib.Path('lang')

WORKS = {}

WORKS['es'] = {
    'kicker': 'Fiestas que hemos montado',
    'index': {
        'title': 'Nuestros trabajos',
        'lead': 'Cada fiesta de esta página se montó de verdad, y está aquí porque la familia nos dio permiso para enseñarla.',
        'meta_title': 'Trabajos: fiestas que hemos decorado en Galicia',
        'meta_description': 'Cumpleaños, bautizos, comuniones y bodas decorados en Baiona, Nigrán, Gondomar, Vigo y el norte de Portugal. Fotos de fiestas reales.',
        'filter': 'Filtrar por tipo de celebración',
        'all': 'Todas',
        'empty': 'Todavía no hay trabajos publicados aquí. Los subimos cuando la familia nos da permiso, y no antes.',
        'empty_cta': 'Cuéntanos tu fiesta',
        'cta_title': '¿Te imaginas la tuya así?',
    },
    'show': {
        'meta_description': 'Decoración de :type en :city.',
        'back': 'Todos los trabajos',
        'date_format': 'F \\d\\e Y',
        'guests': ':count invitados',
        'photo_alt': ':title — foto :n',
        'area_line': 'Esta fiesta la montamos en :area.',
        'area_cta': 'Ver lo que hacemos en :area',
        'more': 'Más fiestas',
        'cta_title': 'Cuéntanos la tuya',
    },
}

WORKS['gl'] = {
    'kicker': 'Festas que montamos',
    'index': {
        'title': 'Os nosos traballos',
        'lead': 'Cada festa desta páxina montouse de verdade, e está aquí porque a familia nos deu permiso para amosala.',
        'meta_title': 'Traballos: festas que decoramos en Galicia',
        'meta_description': 'Aniversarios, bautizos, comuñóns e vodas decorados en Baiona, Nigrán, Gondomar, Vigo e o norte de Portugal. Fotos de festas reais.',
        'filter': 'Filtrar por tipo de celebración',
        'all': 'Todas',
        'empty': 'Aínda non hai traballos publicados aquí. Subímolos cando a familia nos dá permiso, e non antes.',
        'empty_cta': 'Cóntanos a túa festa',
        'cta_title': 'Imaxinas a túa así?',
    },
    'show': {
        'meta_description': 'Decoración de :type en :city.',
        'back': 'Todos os traballos',
        'date_format': 'F \\d\\e Y',
        'guests': ':count convidados',
        'photo_alt': ':title — foto :n',
        'area_line': 'Esta festa montámola en :area.',
        'area_cta': 'Ver o que facemos en :area',
        'more': 'Máis festas',
        'cta_title': 'Cóntanos a túa',
    },
}

WORKS['pt'] = {
    'kicker': 'Festas que montámos',
    'index': {
        'title': 'Os nossos trabalhos',
        'lead': 'Cada festa desta página foi montada mesmo, e está aqui porque a família nos deu autorização para a mostrar.',
        'meta_title': 'Trabalhos: festas que decorámos na Galiza',
        'meta_description': 'Aniversários, batizados, comunhões e casamentos decorados em Baiona, Nigrán, Gondomar, Vigo e no norte de Portugal. Fotos de festas reais.',
        'filter': 'Filtrar por tipo de celebração',
        'all': 'Todas',
        'empty': 'Ainda não há trabalhos publicados aqui. Só os pomos quando a família autoriza, nunca antes.',
        'empty_cta': 'Conta-nos a tua festa',
        'cta_title': 'Imaginas a tua assim?',
    },
    'show': {
        'meta_description': 'Decoração de :type em :city.',
        'back': 'Todos os trabalhos',
        'date_format': 'F \\d\\e Y',
        'guests': ':count convidados',
        'photo_alt': ':title — foto :n',
        'area_line': 'Esta festa foi montada em :area.',
        'area_cta': 'Ver o que fazemos em :area',
        'more': 'Mais festas',
        'cta_title': 'Conta-nos a tua',
    },
}

SERVICES = {}

SERVICES['es'] = {
    'kicker': 'Lo que montamos',
    'show': {
        'meta_title': ':service para fiestas en Galicia',
        'meta_description': 'Montamos :service para cumpleaños, bautizos, comuniones y bodas en el Val Miñor, Vigo y el norte de Portugal.',
        'back': 'Todo lo que montamos',
        'from': 'Desde :price',
        'on_request': 'Precio a consultar',
        'works_title': 'Fiestas que hemos montado',
        'works_all': 'Ver todos los trabajos',
        'areas_title': 'Dónde montamos :service',
        'faq_title': 'Preguntas frecuentes',
        'others_title': 'También montamos',
        'cta_title': 'Pide presupuesto de :service',
    },
}

SERVICES['gl'] = {
    'kicker': 'O que montamos',
    'show': {
        'meta_title': ':service para festas en Galicia',
        'meta_description': 'Montamos :service para aniversarios, bautizos, comuñóns e vodas no Val Miñor, Vigo e o norte de Portugal.',
        'back': 'Todo o que montamos',
        'from': 'Desde :price',
        'on_request': 'Prezo a consultar',
        'works_title': 'Festas que montamos',
        'works_all': 'Ver todos os traballos',
        'areas_title': 'Onde montamos :service',
        'faq_title': 'Preguntas frecuentes',
        'others_title': 'Tamén montamos',
        'cta_title': 'Pide orzamento de :service',
    },
}

SERVICES['pt'] = {
    'kicker': 'O que montamos',
    'show': {
        'meta_title': ':service para festas na Galiza',
        'meta_description': 'Montamos :service para aniversários, batizados, comunhões e casamentos no Val Miñor, Vigo e no norte de Portugal.',
        'back': 'Tudo o que montamos',
        'from': 'A partir de :price',
        'on_request': 'Preço sob consulta',
        'works_title': 'Festas que montámos',
        'works_all': 'Ver todos os trabalhos',
        'areas_title': 'Onde montamos :service',
        'faq_title': 'Perguntas frequentes',
        'others_title': 'Também montamos',
        'cta_title': 'Pede orçamento de :service',
    },
}


def keys(d, prefix=''):
    out = set()
    for k, v in d.items():
        path = f'{prefix}{k}'
        out |= keys(v, path + '.') if isinstance(v, dict) else {path}
    return out


def check(name, trees):
    ref = keys(trees['es'])
    problems = []
    for locale, tree in trees.items():
        got = keys(tree)
        problems += [f'{name}: falta {locale}.{k}' for k in sorted(ref - got)]
        problems += [f'{name}: {locale}.{k} a mais' for k in sorted(got - ref)]
    return problems, len(ref)


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


problems = []
for name, trees in (('works', WORKS), ('services', SERVICES)):
    p, _ = check(name, trees)
    problems += p

if problems:
    print('PROBLEMAS DE TRADUÇÃO:')
    for p in problems:
        print('  -', p)
    sys.exit(1)

for name, trees in (('works', WORKS), ('services', SERVICES)):
    total = len(keys(trees['es']))
    for locale, tree in trees.items():
        path = OUT / locale / f'{name}.php'
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(
            "<?php\n\ndeclare(strict_types=1);\n\n"
            "/*\n * Gerado por gen_works_lang.py, que falha se alguma chave\n"
            " * faltar num dos três idiomas.\n */\n\n"
            f"return [\n{php(tree)}\n];\n",
            encoding='utf-8')
    print(f'  {name}.php  ×3 idiomas  ({total} chaves cada)')

print('\nnenhuma chave em falta')
