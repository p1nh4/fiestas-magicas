<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| O que corre sozinho
|--------------------------------------------------------------------------
| Nada disto acontece se o `schedule:run` não estiver a ser chamado a cada
| minuto. Em produção é o `fiestas-scheduler.timer` do systemd (ver
| deploy/); em desenvolvimento, `php artisan schedule:work` num terminal.
|
| Se o agendador não correr, não há erro nenhum — as coisas simplesmente
| não acontecem, que é a pior maneira de falhar. Daí o comando de
| verificação no fim deste ficheiro.
*/

/**
 * Um comando agendado que falha não diz nada a ninguém: o `schedule:run`
 * engole a saída e segue. Fica pelo menos escrito no log, com nível
 * `critical`, para o dia em que houver alguém a ler os logs — e para o
 * `journalctl -u fiestas-scheduler` do servidor.
 */
$aoFalhar = function (string $comando): callable {
    return function () use ($comando): void {
        Log::critical("El comando programado [{$comando}] ha fallado.");
    };
};

/*
| Reservas de carrinho caducadas.
|
| Uma reserva em `hold` prende material sem que ninguém tenha aceitado nada.
| Sem isto, o stock ia encolhendo até uma cliente que quer mesmo alugar
| receber "não há" por causa de carrinhos abandonados há meses.
*/
Schedule::command('reservations:release-holds')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure($aoFalhar('reservations:release-holds'));

/*
| Orçamentos caducados.
|
| De madrugada, quando ninguém está a trabalhar: assim a lista da manhã já
| está limpa e a Sol vê só o que continua vivo.
*/
Schedule::command('quotes:expire')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->onFailure($aoFalhar('quotes:expire'));

/*
| Lembrete dos dias antes da festa.
|
| Às dez da manhã, e não de madrugada: um email que chega às 3h aparece no
| telemóvel a meio da noite e é lido com a cara de quem foi acordado.
*/
Schedule::command('events:remind')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->onFailure($aoFalhar('events:remind'));

/*
| Pagamentos por confirmar.
|
| Só se dava um sinal por pago quando a pessoa voltava da passarela ao
| site. Quem paga no telemóvel e fecha o separador nunca volta: o dinheiro
| entrava e o evento continuava a dizer "pendente de cobro".
|
| De hora a hora chega. Não é um webhook — um webhook precisa de uma URL
| pública sempre de pé, e enquanto isto vive num portátil atrás de um
| túnel, essa URL não existe metade do tempo. Quando houver servidor, o
| webhook entra por cima sem mexer aqui: acabam os dois no mesmo
| `SettlePayment`.
*/
Schedule::command('payments:reconcile')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure($aoFalhar('payments:reconcile'));

/*
| O sinal de vida do próprio agendador.
|
| Este é o comando de verificação que o cabeçalho promete. Não faz nada
| para o negócio: escreve a hora e sai. Se parar de escrever, é porque o
| `schedule:run` deixou de ser chamado, e o `/up` passa a responder 503 —
| que é o que faz o serviço de fora tocar o alarme.
|
| Sem `withoutOverlapping`: leva milissegundos, e um lock preso aqui
| calaria exatamente o sinal que existe para não se calar.
*/
Schedule::command('monitor:heartbeat')
    ->everyFiveMinutes();
