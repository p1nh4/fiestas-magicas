<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Escrita à mão, e é a exceção que confirma a regra
|--------------------------------------------------------------------------
| O `database/schema/schema.sql` continua a ser a fonte de verdade e já traz
| esta coluna: quem instalar de raiz fica com a tabela certa.
|
| Mas o gerador só sabe produzir `CREATE TABLE` a partir de uma fatia, e a
| fatia `sales` já foi migrada em produção. Uma alteração a uma tabela que
| já existe tem de ser escrita à mão — e a única regra que importa é que o
| resultado bata certo com o schema.sql, senão as instalações novas e as
| antigas divergem em silêncio.
*/
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE events ADD COLUMN IF NOT EXISTS reminder_sent_at timestamptz;
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE events DROP COLUMN IF EXISTS reminder_sent_at;
SQL);
    }
};
