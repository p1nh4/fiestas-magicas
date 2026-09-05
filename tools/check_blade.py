"""Verificador de Blade sem Laravel.

Nao compila Blade — apanha as tres coisas que mais partem uma view e que
so se descobrem ao abrir a pagina:
  1. diretivas abertas e nunca fechadas (@if sem @endif)
  2. chaves de traducao usadas na view que nao existem nos lang/
  3. componentes <x-...> sem ficheiro correspondente
"""
import re, pathlib, sys, subprocess, json

ROOT = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else '.')
VIEWS = ROOT / 'resources/views'
LANG = ROOT / 'lang'

PAIRS = {
    'if': 'endif', 'foreach': 'endforeach', 'forelse': 'endforelse',
    'for': 'endfor', 'while': 'endwhile', 'switch': 'endswitch',
    'section': 'endsection', 'php': 'endphp', 'push': 'endpush',
    'once': 'endonce', 'error': 'enderror', 'isset': 'endisset',
    'empty': 'endempty', 'auth': 'endauth', 'guest': 'endguest',
    'verbatim': 'endverbatim', 'unless': 'endunless',
}
OPENERS = set(PAIRS)
CLOSERS = set(PAIRS.values())
NEUTRAL = {'else', 'elseif', 'case', 'break', 'default', 'endcase', 'empty'}

problems = []


def strip_comments(src: str) -> str:
    return re.sub(r'\{\{--.*?--\}\}', '', src, flags=re.S)


def check_directives(path: pathlib.Path, src: str) -> None:
    stack = []
    # @php ... @endphp com corpo, e @php(...) de uma linha, sao diferentes
    for m in re.finditer(r'@(\w+)\s*(\()?', src):
        name = m.group(1)
        has_paren = m.group(2) is not None
        line = src[:m.start()].count('\n') + 1

        if name == 'php' and has_paren:
            continue                      # @php($x = 1) nao abre bloco
        if name == 'forelse':
            stack.append(('forelse', line))
            continue
        if name == 'empty' and stack and stack[-1][0] == 'forelse':
            continue                      # @empty do forelse
        if name in OPENERS:
            stack.append((name, line))
        elif name in CLOSERS:
            want = [k for k, v in PAIRS.items() if v == name][0]
            if not stack:
                problems.append(f'{path}:{line}  @{name} sem abertura')
            elif stack[-1][0] != want:
                got, at = stack.pop()
                problems.append(
                    f'{path}:{line}  @{name} fecha @{want}, mas o aberto era @{got} (linha {at})')
            else:
                stack.pop()

    for name, line in stack:
        problems.append(f'{path}:{line}  @{name} aberto e nunca fechado')


def load_lang() -> dict[str, set[str]]:
    """Le os lang/*.php via php -r, para nao reimplementar o parser."""
    out = {}
    for locale_dir in sorted(LANG.iterdir()):
        if not locale_dir.is_dir():
            continue
        keys = set()
        for f in locale_dir.glob('*.php'):
            code = (
                f'$a = require {json.dumps(str(f))};'
                'function flat($a, $p = "") { $o = []; foreach ($a as $k => $v) {'
                '  $x = $p === "" ? $k : "$p.$k";'
                '  if (is_array($v)) { $o = array_merge($o, flat($v, $x)); } else { $o[] = $x; } }'
                '  return $o; }'
                'echo json_encode(flat($a));'
            )
            res = subprocess.run(['php', '-r', code], capture_output=True, text=True)
            if res.returncode != 0:
                problems.append(f'{f}: nao carrega — {res.stderr.strip()[:120]}')
                continue
            keys |= {f'{f.stem}.{k}' for k in json.loads(res.stdout)}
        out[locale_dir.name] = keys
    return out


def check_lang_keys(path: pathlib.Path, src: str, lang: dict[str, set[str]]) -> None:
    # __('site.hero.title') e __("site.process.step{$n}_title")
    for m in re.finditer(r"__\(\s*['\"]([a-z0-9_]+\.[a-z0-9_.]*?)['\"]", src, re.I):
        key = m.group(1)
        line = src[:m.start()].count('\n') + 1
        if '{' in key or '$' in key:
            continue                       # chave dinamica, nao da para validar
        for locale, keys in lang.items():
            if key not in keys:
                problems.append(f'{path}:{line}  __("{key}") nao existe em lang/{locale}')

    # chaves construidas: __("site.process.step{$n}_title")
    for m in re.finditer(r'__\(\s*"([a-z0-9_.]*)\{\$\w+\}([a-z0-9_.]*)"', src, re.I):
        prefix, suffix = m.group(1), m.group(2)
        line = src[:m.start()].count('\n') + 1
        for locale, keys in lang.items():
            if not any(k.startswith(prefix) and k.endswith(suffix) for k in keys):
                problems.append(
                    f'{path}:{line}  nenhuma chave "{prefix}*{suffix}" em lang/{locale}')


def check_components(path: pathlib.Path, src: str, available: set[str]) -> None:
    for m in re.finditer(r'<x-([a-z0-9.\-]+)', src, re.I):
        name = m.group(1)
        line = src[:m.start()].count('\n') + 1
        if name not in available:
            problems.append(f'{path}:{line}  componente <x-{name}> nao encontrado')


def main() -> int:
    files = sorted(VIEWS.rglob('*.blade.php'))
    if not files:
        print('nenhuma view encontrada')
        return 1

    # componentes disponiveis: components/*.blade.php  +  layouts/*.blade.php
    available = set()
    for f in (VIEWS / 'components').glob('**/*.blade.php'):
        rel = f.relative_to(VIEWS / 'components').with_suffix('').with_suffix('')
        available.add(str(rel).replace('/', '.'))
    # Nao ha excecoes: um componente <x-a.b> resolve-se sempre em
    # components/a/b.blade.php. Foi por assumir o contrario que a primeira
    # versao deste verificador deixou passar um layout no sitio errado.

    lang = load_lang()

    for f in files:
        src = strip_comments(f.read_text(encoding='utf-8'))
        rel = f.relative_to(ROOT)
        check_directives(rel, src)
        check_lang_keys(rel, src, lang)
        check_components(rel, src, available)

    print(f'{len(files)} views, {len(available)} componentes, '
          f'{len(lang)} idiomas ({len(next(iter(lang.values())))} chaves cada)')

    if problems:
        print(f'\n{len(problems)} problemas:')
        for p in problems:
            print('  ', p)
        return 1

    print('nenhum problema')
    return 0


if __name__ == '__main__':
    sys.exit(main())
