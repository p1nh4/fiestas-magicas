<?php

declare(strict_types=1);

use App\Support\Monitoring\SchedulerHeartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Monitorização
|--------------------------------------------------------------------------
| O `/up` só vale o que verifica. Estes testes existem para que ninguém
| volte a aligeirá-lo sem dar por isso: um `/up` que responde 200 com o
| Postgres em baixo, ou com o agendador morto há três dias, é um alarme
| desligado com aspeto de alarme ligado.
*/

beforeEach(function () {
    Cache::forget(config('monitor.heartbeat_key'));
});

it('o up responde quando esta tudo de pe', function () {
    config(['monitor.require_scheduler' => false]);

    $this->get('/up')->assertOk();
});

it('o up acusa o agendador que nunca deu sinal', function () {
    config(['monitor.require_scheduler' => true]);

    $this->get('/up')->assertStatus(503);
});

it('o up fica verde quando o agendador acabou de dar sinal', function () {
    config(['monitor.require_scheduler' => true]);

    $this->artisan('monitor:heartbeat')->assertSuccessful();

    $this->get('/up')->assertOk();
});

it('o up acusa um sinal velho de mais', function () {
    config([
        'monitor.require_scheduler' => true,
        'monitor.scheduler_tolerance_minutes' => 15,
    ]);

    $this->artisan('monitor:heartbeat')->assertSuccessful();

    $this->travel(16)->minutes();

    $this->get('/up')->assertStatus(503);
});

/*
 * A tolerância é sobre a marca, não sobre a cache: a entrada dura um dia
 * inteiro de propósito. Se expirasse com a tolerância, "o agendador morreu"
 * e "a cache esvaziou-se" davam o mesmo resultado, e são coisas diferentes.
 */
it('o sinal continua legivel depois de passar a tolerancia', function () {
    $this->artisan('monitor:heartbeat')->assertSuccessful();

    $this->travel(30)->minutes();

    $heartbeat = app(SchedulerHeartbeat::class);

    expect($heartbeat->lastBeat())->not->toBeNull()
        ->and($heartbeat->isAlive())->toBeFalse();
});

it('o up ignora o agendador fora de producao', function () {
    config(['monitor.require_scheduler' => false]);

    expect(app(SchedulerHeartbeat::class)->isAlive())->toBeFalse();

    $this->get('/up')->assertOk();
});

it('o sinal de vida esta agendado', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event) => $event->command ?? '')
        ->implode(' ');

    expect($commands)->toContain('monitor:heartbeat');
});
