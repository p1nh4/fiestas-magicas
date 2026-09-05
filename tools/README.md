# Geradores

Estes scripts produzem código a partir de `database/schema/schema.sql`, que é
a fonte de verdade do modelo de dados. Correm-se depois de mexer no esquema:

```bash
cd <raiz do projeto>
python3 tools/gen_migrations.py   # database/migrations/*.php
python3 tools/gen_enums.py        # app/Enums/*.php
python3 tools/gen_lang.py         # lang/{es,gl,pt}/enums.php  (falha se faltar tradução)
```

Escrevem para `build/`. Copia de lá para o sítio depois de veres o diff — é
de propósito que não escrevem por cima sem tu olhares.

Ordem de trabalho ao alterar o modelo:

1. editar `database/schema/schema.sql`
2. `psql ... -f database/schema/tests.sql` → tem de dar 29/29
3. correr os geradores
4. rever o diff e copiar
