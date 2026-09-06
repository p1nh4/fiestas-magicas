<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Availability\AvailabilityService;
use Illuminate\Console\Command;

/**
 * Devolve ao stock as reservas de carrinho que caducaram.
 *
 * Uma reserva em `hold` prende material sem que ninguém tenha aceitado
 * nada. Se ninguém as libertar, o stock vai encolhendo até uma cliente que
 * quer mesmo alugar receber "não há" por causa de carrinhos abandonados
 * há três meses.
 *
 * O `AvailabilityService` já sabia fazer isto e tinha escrito no comentário
 * "corre no scheduler" — só que ninguém o tinha agendado. Este comando é
 * essa metade que faltava.
 */
class ReleaseExpiredHolds extends Command
{
    protected $signature = 'reservations:release-holds';

    protected $description = 'Devuelve al stock las reservas de carrito caducadas';

    public function handle(AvailabilityService $availability): int
    {
        $released = $availability->releaseExpiredHolds();

        // Só se escreve no log quando houve alguma coisa a fazer: este
        // comando corre a cada cinco minutos e um log com 288 linhas por
        // dia a dizer "zero" é um log que ninguém volta a ler.
        if ($released > 0) {
            $this->info("{$released} reservas caducadas devueltas al stock.");
        }

        return self::SUCCESS;
    }
}
