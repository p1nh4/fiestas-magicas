<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\QuoteStatus;
use App\Enums\ReservationStatus;
use App\Mail\EventReminderMail;
use App\Models\Client;
use App\Models\Event;
use App\Models\Item;
use App\Models\Reservation;
use App\Support\Availability\AvailabilityService;
use App\Support\Period;
use App\Support\Quotes\QuoteBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function umEvento(array $overrides = []): Event
{
    $client = Client::create($overrides['client'] ?? [
        'name' => 'Marta',
        'email' => 'marta@example.com',
        'locale' => 'pt',
    ]);
    unset($overrides['client']);

    return Event::create($overrides + [
        'client_id' => $client->id,
        'reference' => 'FM-SCHED-'.random_int(1000, 9999),
        'title' => 'Comunión de Uxía',
        'event_type' => 'comunion',
        'status' => EventStatus::Confirmed,
        'locale' => 'pt',
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(6),
        'venue_city' => 'Nigrán',
    ]);
}

// ------------------------------------------------- reservas de carrinho

it('devolve ao stock as reservas de carrinho caducadas', function () {
    $item = Item::create([
        'sku' => 'SILLA', 'name' => ['es' => 'Silla'], 'slug' => ['es' => 'silla'],
        'stock_qty' => 10,
    ]);

    $event = umEvento();

    app(AvailabilityService::class)->reserve(
        item: $item,
        window: new Period(CarbonImmutable::now()->addMonth(), CarbonImmutable::now()->addMonth()->addDay()),
        quantity: 10,
        event: $event,
        holdUntil: CarbonImmutable::now()->subMinute(),
    );

    expect(Reservation::where('status', ReservationStatus::Hold->value)->count())->toBe(1);

    $this->artisan('reservations:release-holds')->assertSuccessful();

    expect(Reservation::where('status', ReservationStatus::Cancelled->value)->count())->toBe(1);
});

it('nao mexe numa reserva de carrinho que ainda esta viva', function () {
    $item = Item::create([
        'sku' => 'SILLA', 'name' => ['es' => 'Silla'], 'slug' => ['es' => 'silla'],
        'stock_qty' => 10,
    ]);

    app(AvailabilityService::class)->reserve(
        item: $item,
        window: new Period(CarbonImmutable::now()->addMonth(), CarbonImmutable::now()->addMonth()->addDay()),
        quantity: 1,
        event: umEvento(),
        holdUntil: CarbonImmutable::now()->addMinutes(20),
    );

    $this->artisan('reservations:release-holds')->assertSuccessful();

    expect(Reservation::where('status', ReservationStatus::Hold->value)->count())->toBe(1);
});

// ---------------------------------------------------------- orçamentos

it('caduca os orcamentos cuja validade passou', function () {
    $quote = app(QuoteBuilder::class)->draftFor(umEvento());
    $quote->update([
        'status' => QuoteStatus::Sent,
        'sent_at' => now()->subMonth(),
        'valid_until' => now()->subDays(3)->toDateString(),
    ]);

    $this->artisan('quotes:expire')->assertSuccessful();

    expect($quote->fresh()->status)->toBe(QuoteStatus::Expired);
});

/*
| O `valid_until` é uma DATA, não um instante: um orçamento válido até hoje
| ainda vale o dia todo. Se este teste falhar, alguém trocou o `<` por um
| `<=` e passou a caducar orçamentos com horas de antecedência.
*/
it('nao caduca um orcamento que ainda vale hoje', function () {
    $quote = app(QuoteBuilder::class)->draftFor(umEvento());
    $quote->update([
        'status' => QuoteStatus::Sent,
        'valid_until' => now()->toDateString(),
    ]);

    $this->artisan('quotes:expire')->assertSuccessful();

    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent);
});

it('nao toca num orcamento ja aceite, mesmo com a data passada', function () {
    $quote = app(QuoteBuilder::class)->draftFor(umEvento());
    $quote->update([
        'status' => QuoteStatus::Accepted,
        'accepted_at' => now()->subMonth(),
        'valid_until' => now()->subMonth()->toDateString(),
    ]);

    $this->artisan('quotes:expire')->assertSuccessful();

    expect($quote->fresh()->status)->toBe(QuoteStatus::Accepted);
});

// ------------------------------------------------------------ lembrete

it('manda o lembrete das festas proximas', function () {
    Mail::fake();
    umEvento(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(6)]);

    $this->artisan('events:remind')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, fn ($m) => $m->locale === 'pt');
});

/*
| A razão de existir do `reminder_sent_at`: o comando corre todos os dias e
| a janela dura cinco, portanto sem a marca a cliente recebia o mesmo email
| cinco vezes seguidas.
*/
it('nunca manda o lembrete duas vezes', function () {
    Mail::fake();
    umEvento();

    $this->artisan('events:remind')->assertSuccessful();
    $this->artisan('events:remind')->assertSuccessful();
    $this->artisan('events:remind')->assertSuccessful();

    Mail::assertQueuedCount(1);
});

it('nao manda lembretes de festas distantes', function () {
    Mail::fake();
    umEvento(['starts_at' => now()->addDays(40), 'ends_at' => now()->addDays(40)->addHours(6)]);

    $this->artisan('events:remind')->assertSuccessful();

    Mail::assertNotQueued(EventReminderMail::class);
});

it('nao manda lembretes de festas por confirmar', function () {
    Mail::fake();
    umEvento(['status' => EventStatus::Draft]);

    $this->artisan('events:remind')->assertSuccessful();

    Mail::assertNotQueued(EventReminderMail::class);
});

it('o lembrete renderiza sem rebentar', function () {
    $event = umEvento();

    expect((new EventReminderMail($event))->render())
        ->toBeString()
        ->toContain('Comunión de Uxía');
});

/*
| O agendador é a peça que falha em silêncio: se ninguém o correr, não há
| erro nenhum — as coisas simplesmente não acontecem. Este teste garante
| pelo menos que os três comandos estão registados.
*/
it('os tres comandos estao agendados', function () {
    $schedule = app(Schedule::class);

    $commands = collect($schedule->events())
        ->map(fn ($event) => $event->command ?? '')
        ->implode(' ');

    expect($commands)
        ->toContain('reservations:release-holds')
        ->toContain('quotes:expire')
        ->toContain('events:remind');
});
