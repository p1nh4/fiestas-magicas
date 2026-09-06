<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Monitorização
|--------------------------------------------------------------------------
| Ninguém está a olhar para isto às três da manhã. O que substitui essa
| pessoa é um serviço de fora a bater no `/up` de minuto a minuto — e o
| `/up` só serve para isso se disser a verdade.
|
| Por omissão o Laravel devolve 200 no `/up` só por a aplicação ter
| arrancado. Com o Postgres em baixo, o site dá erro em todas as páginas e
| o `/up` continua a dizer que está tudo bem: o alarme nunca toca. Daí as
| duas verificações do `App\Providers\AppServiceProvider`.
*/

return [
    /*
     * O agendador entra na verificação do `/up`?
     *
     * Só em produção. Em desenvolvimento ninguém tem o `schedule:work`
     * ligado, e um `/up` a dar 503 na máquina de quem programa ensina a
     * ignorar o `/up` — que é exatamente o hábito que isto quer evitar.
     */
    'require_scheduler' => (bool) env('MONITOR_REQUIRE_SCHEDULER', false),

    /*
     * Quanto tempo sem sinal do agendador antes de dar o `/up` por caído.
     *
     * O `monitor:heartbeat` bate de cinco em cinco minutos. Quinze dá
     * margem para duas falhas seguidas — um `withoutOverlapping` a segurar,
     * a máquina a arrancar — sem esperar tanto que a festa de sábado passe
     * sem lembrete antes de alguém dar por isso.
     */
    'scheduler_tolerance_minutes' => (int) env('MONITOR_SCHEDULER_TOLERANCE', 15),

    /*
     * Onde fica a marca do último sinal. Cache e não base de dados própria:
     * é um valor só, sem histórico e sem consultas — uma tabela para isto
     * seria uma tabela para manter de graça. O `CACHE_STORE` é `database`,
     * portanto a marca sobrevive a um reinício, que é o que interessa.
     */
    'heartbeat_key' => 'monitor:scheduler-heartbeat',
];
