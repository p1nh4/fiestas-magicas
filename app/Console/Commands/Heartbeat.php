<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Monitoring\SchedulerHeartbeat;
use Illuminate\Console\Command;

/**
 * Escreve o sinal de vida do agendador.
 *
 * Não faz nada de útil para o negócio, e é essa a graça: se este comando
 * deixar de correr, é porque o `schedule:run` deixou de ser chamado — não
 * porque um orçamento tinha um estado esquisito. Um comando que só serve
 * para provar que o cron está vivo é um comando que responde a uma
 * pergunta só.
 */
class Heartbeat extends Command
{
    protected $signature = 'monitor:heartbeat';

    protected $description = 'Deja constancia de que el programador de tareas sigue vivo';

    public function handle(SchedulerHeartbeat $heartbeat): int
    {
        $heartbeat->beat();

        return self::SUCCESS;
    }
}
