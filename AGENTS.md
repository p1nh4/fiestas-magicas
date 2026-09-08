# Instruções para agentes

**Lê `docs/contexto.md` por inteiro antes de fazer o que quer que seja.** É
o ponto de entrada do projeto: stack, arquitetura, regras que não se mexem,
estado real da máquina, pendentes e quem deve o quê.

Não instales o Laravel Boost. Não corras `composer require` sem pedir.

## O mínimo, para o caso de leres só isto

- **PostgreSQL 16 e PHP 8.4.** Não é MariaDB, não é MySQL, não é PHP 8.3.
- **`database/schema/schema.sql` é a fonte de verdade.** As migrations, os
  enums e os ficheiros de idioma são gerados por `tools/`. Não os edites à
  mão — edita o gerador e corre-o outra vez.
- **Nenhuma frase visível dentro de uma view.** Tudo em `lang/{es,gl,pt}/`,
  nos três idiomas.
- **O agente não tem shell nesta máquina.** Só lê e escreve ficheiros na
  pasta do Windows; todos os comandos são corridos à mão no WSL. O circuito
  de leitura (`_diag.sh` → `_estado.md`) e o de escrita (patch) estão na
  secção 5 do `docs/contexto.md`.
- **A shell é zsh e não trata `#` como comentário.** Nunca ponhas
  comentários dentro de blocos de comandos.
- **Não assumas.** Verifica no repositório ou pergunta. Um doc pode estar
  desatualizado — se descobrires que está, corrige-o na mesma sessão.
