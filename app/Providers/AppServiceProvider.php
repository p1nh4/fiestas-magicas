<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Monitoring\SchedulerHeartbeat;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registarVerificacaoDeSaude();
    }

    /**
     * O que o `/up` verifica de facto.
     *
     * Por omissão o `/up` responde 200 só por a aplicação ter arrancado.
     * Com o Postgres em baixo, todas as páginas do site dão erro e o `/up`
     * continua a dizer que está tudo bem — o serviço que bate à porta de
     * madrugada nunca toca o alarme. Um monitor que não distingue "de pé"
     * de "a funcionar" é pior do que monitor nenhum: dá a sensação de que
     * alguém está a olhar.
     *
     * Quem lança daqui é o `DiagnosingHealth`. A excepção é HTTP, portanto
     * o Laravel devolve 503 sozinho — "temporariamente indisponível", que
     * é a verdade, e não um 500 que mandava procurar um bug.
     */
    private function registarVerificacaoDeSaude(): void
    {
        Event::listen(function (DiagnosingHealth $event): void {
            try {
                DB::connection()->getPdo();
                DB::select('select 1');
            } catch (Throwable $e) {
                throw new ServiceUnavailableHttpException(
                    null,
                    'La base de datos no responde.',
                    $e,
                );
            }

            /*
             * O agendador entra na conta só onde há agendador a sério. Em
             * desenvolvimento ninguém tem o `schedule:work` ligado, e um
             * `/up` sempre vermelho na máquina de quem programa ensina a
             * ignorar o `/up`.
             */
            if (! config('monitor.require_scheduler')) {
                return;
            }

            $heartbeat = app(SchedulerHeartbeat::class);

            if (! $heartbeat->isAlive()) {
                $ultimo = $heartbeat->lastBeat()?->diffForHumans() ?? 'nunca';

                throw new ServiceUnavailableHttpException(
                    null,
                    "El programador de tareas no da señales (ultima: {$ultimo}).",
                );
            }
        });
    }
}
