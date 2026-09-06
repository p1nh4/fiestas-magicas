<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * As fotos do portefólio.
 *
 * Escrita à mão, e não gerada, porque a fatia `content` já foi aplicada: o
 * gerador escreve CREATE TABLE, e uma tabela que já existe não se volta a
 * criar. O `schema.sql` fica com a coluna na mesma — quem manda continua a
 * ser ele — e é o `tools/patch_schema_projects_photos.py` que a lá põe.
 *
 * Sem esta coluna o portefólio era uma lista de títulos. Numa empresa de
 * decoração, a foto não ilustra o trabalho: é o trabalho.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE projects ADD COLUMN IF NOT EXISTS photos jsonb NOT NULL DEFAULT '[]'::jsonb");

        // A restrição existe porque o resto do código faz `array_map` por
        // cima disto. Um objeto ou um número aqui dentro não daria um erro
        // à entrada — daria uma página em branco semanas depois.
        DB::statement(<<<'SQL'
            ALTER TABLE projects
            DROP CONSTRAINT IF EXISTS projects_photos_chk
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE projects
            ADD CONSTRAINT projects_photos_chk
            CHECK (jsonb_typeof(photos) = 'array')
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE projects DROP CONSTRAINT IF EXISTS projects_photos_chk');
        DB::statement('ALTER TABLE projects DROP COLUMN IF EXISTS photos');
    }
};
