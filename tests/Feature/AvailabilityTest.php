<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Event;
use App\Models\Item;
use App\Support\Availability\AvailabilityService;
use App\Support\Availability\OutOfStockException;
use App\Support\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Estes testes correm contra Postgres a sério, nunca SQLite: o que está a
 * ser testado são triggers plpgsql e tipos de intervalo que o SQLite não tem.
 */
beforeEach(function () {
    $this->service = app(AvailabilityService::class);

    $this->chairs = Item::create([
        'sku' => 'SILLA-TIF',
        'name' => ['es' => 'Silla Tiffany'],
        'slug' => ['es' => 'silla-tiffany', 'gl' => 'cadeira-tiffany', 'pt' => 'cadeira-tiffany'],
        'stock_qty' => 40,
        'price_per_day' => 3.50,
        'buffer_before_min' => 120,
        'buffer_after_min' => 1440,
    ]);

    $client = Client::create(['name' => 'Cliente', 'email' => 'c@example.com']);

    $this->event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-TEST-0001',
        'title' => 'Comunión de prueba',
        'event_type' => 'comunion',
        'starts_at' => now()->addDays(60),
        'ends_at' => now()->addDays(60)->addHours(8),
    ]);
});

function window(int $daysAhead, int $hours = 8): Period
{
    $start = now()->addDays($daysAhead)->toImmutable();

    return new Period($start, $start->addHours($hours));
}

it('parte de todo o stock disponível', function () {
    expect($this->service->availableQuantity($this->chairs, window(60)))->toBe(40);
});

it('desconta o que já está reservado', function () {
    $this->service->reserve($this->chairs, window(60), 30, $this->event);

    expect($this->service->availableQuantity($this->chairs, window(60)))->toBe(10);
});

it('deixa reservar exatamente o que resta', function () {
    $this->service->reserve($this->chairs, window(60), 30, $this->event);
    $this->service->reserve($this->chairs, window(60), 10, $this->event);

    expect($this->service->availableQuantity($this->chairs, window(60)))->toBe(0);
});

it('recusa uma unidade acima do stock, com uma mensagem que se percebe', function () {
    $this->service->reserve($this->chairs, window(60), 40, $this->event);

    expect(fn () => $this->service->reserve($this->chairs, window(60), 1, $this->event))
        ->toThrow(OutOfStockException::class);
});

it('não desconta stock noutras datas', function () {
    $this->service->reserve($this->chairs, window(60), 40, $this->event);

    expect($this->service->availableQuantity($this->chairs, window(120)))->toBe(40);
});

it('liberta o stock quando a reserva é cancelada', function () {
    $reservation = $this->service->reserve($this->chairs, window(60), 40, $this->event);
    $reservation->update(['status' => 'cancelled']);

    expect($this->service->availableQuantity($this->chairs, window(60)))->toBe(40);
});

it('exige um evento ou um motivo de bloqueio', function () {
    expect(fn () => $this->service->reserve($this->chairs, window(60), 1))
        ->toThrow(InvalidArgumentException::class);
});

it('conta os bloqueios de manutenção como stock ocupado', function () {
    $this->service->reserve(
        item: $this->chairs,
        window: window(60),
        quantity: 40,
        blockedReason: 'Cadeiras em limpeza',
    );

    expect($this->service->isAvailable($this->chairs, window(60), 1))->toBeFalse();
});

it('conta a procura mesmo quando a janela começa a meio de uma reserva longa', function () {
    // sem o ponto candidato da própria janela, isto daria zero procura
    $long = new Period(now()->addDays(60)->toImmutable(), now()->addDays(65)->toImmutable());
    $this->service->reserve($this->chairs, $long, 40, $this->event);

    expect($this->service->availableQuantity($this->chairs, window(62)))->toBe(0);
});

it('devolve a disponibilidade dia a dia para o calendário', function () {
    $this->service->reserve($this->chairs, window(60, 8), 25, $this->event);

    $days = $this->service->dailyAvailability(
        $this->chairs,
        now()->addDays(59)->toImmutable(),
        now()->addDays(61)->toImmutable(),
    );

    expect($days)->toHaveCount(3)
        ->and($days[now()->addDays(60)->toDateString()])->toBe(15);
});

it('devolve as reservas de carrinho expiradas ao stock', function () {
    $this->service->reserve(
        item: $this->chairs,
        window: window(60),
        quantity: 40,
        event: $this->event,
        holdUntil: now()->subMinute()->toImmutable(),
    );

    expect($this->service->availableQuantity($this->chairs, window(60)))->toBe(0);

    $released = $this->service->releaseExpiredHolds();

    expect($released)->toBe(1)
        ->and($this->service->availableQuantity($this->chairs, window(60)))->toBe(40);
});
