"""Poe a coluna `photos` na tabela projects dentro do schema.sql.

O schema.sql e a fonte de verdade do projeto. A migracao que acrescenta a
coluna e escrita a mao (a fatia `content` ja foi aplicada, e o gerador so
sabe escrever CREATE TABLE), mas se o schema.sql nao souber da coluna, a
proxima base de dados criada de raiz nasce sem ela — e o portefolio deixa
de ter fotos sem ninguem perceber porque.

Corre-se uma vez:

    python3 tools/patch_schema_projects_photos.py

E idempotente: correr duas vezes nao faz nada da segunda.
"""
import pathlib
import re
import sys

SCHEMA = pathlib.Path('database/schema/schema.sql')

COLUNA = "    photos          jsonb NOT NULL DEFAULT '[]'::jsonb,\n"
RESTRICAO = "    CONSTRAINT projects_photos_chk CHECK (jsonb_typeof(photos) = 'array'),\n"

if not SCHEMA.exists():
    sys.exit(f'nao encontro {SCHEMA} — corre isto na raiz do projeto')

texto = SCHEMA.read_text(encoding='utf-8')

if 'projects_photos_chk' in texto:
    print('ja la esta — nada a fazer')
    sys.exit(0)

# Apanha o bloco CREATE TABLE projects (...) inteiro, sem tocar em mais nada.
padrao = re.compile(r'(CREATE TABLE projects \(\n)(.*?)(^\);)', re.S | re.M)
encontrado = padrao.search(texto)

if encontrado is None:
    sys.exit('nao encontro o CREATE TABLE projects no schema.sql')

corpo = encontrado.group(2)

# A coluna vai logo a seguir ao `seo`, que e onde estao as outras coisas de
# apresentacao; a restricao vai no fim, junto das outras.
if '    seo             jsonb' not in corpo:
    sys.exit('a tabela projects nao tem a coluna seo onde eu esperava — ve isso a mao')

linhas = corpo.splitlines(keepends=True)
saida = []
for linha in linhas:
    saida.append(linha)
    if linha.startswith('    seo             jsonb'):
        saida.append(COLUNA)

corpo_novo = ''.join(saida).rstrip('\n')

# A ultima linha do corpo nao tem virgula. Poe-se-lhe uma e acrescenta-se a
# restricao a seguir.
corpo_novo = corpo_novo + ',\n' + RESTRICAO.rstrip(',\n') + '\n'

texto_novo = texto[:encontrado.start(2)] + corpo_novo + texto[encontrado.start(3):]

SCHEMA.write_text(texto_novo, encoding='utf-8')
print('schema.sql: projects.photos acrescentada')
