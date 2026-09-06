<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Filament\Widgets\MaterialQueSaleWidget;
use App\Filament\Widgets\ProximasFiestasWidget;
use App\Filament\Widgets\ResumenWidget;
use App\Models\Client;
use App\Models\Event;
use App\Models\Item;
use App\Models\Lead;
use App\Models\Payment;
use App\Support\Availability\AvailabilityService;
use App\Support\Period;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // O painel exige sessão iniciada: sem isto, os widgets nem chegam a
    // ser construídos e o teste passaria a testar o ecrã de login.
    $this->actingAs(App\Models\User::create([
        'name' => 'Sol',
        'email' => 'sol@example.com',
        'password' => 'segredo-de-teste',
        'is_active' => true,
    ]));
});

function unEvento(array $overrides = []): Event
{
    $client = Client::create([
        'name' => 'Marta',
        'email' => 'marta-'.random_int(1000, 9999).'@example.com',
        'locale' => 'es',
    ]);

    return Event::create($overrides + [
        'client_id' => $client->id,
        'reference' => 'FM-DASH-'.random_int(1000, 9999),
        'title' => 'Comunión de Uxía',
        'event_type' => 'comunion',
        'status' => EventStatus::Confirmed,
        'locale' => 'es',
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(6),
        'venue_city' => 'Nigrán',
    ]);
}

// ------------------------------------------------------------------ números

it('conta os pedidos por responder', function () {
    Lead::create([
        'name' => 'Sin responder', 'email' => 'a@example.com', 'locale' => 'es',
        'event_type' => 'boda', 'status' => LeadStatus::New,
    ]);
    Lead::create([
        'name' => 'Ya contestado', 'email' => 'b@example.com', 'locale' => 'es',
        'event_type' => 'boda', 'status' => LeadStatus::Contacted,
    ]);

    Livewire::test(ResumenWidget::class)
        ->assertSee('Sin responder')
        ->assertSee('1');
});

it('soma o dinheiro por receber', function () {
    $event = unEvento();

    Payment::create([
        'event_id' => $event->id,
        'client_id' => $event->client_id,
        'kind' => PaymentKind::Deposit,
        'method' => 'transfer',
        'status' => PaymentStatus::Pending,
        'amount' => 150.00,
    ]);

    // Um já cobrado não entra na conta.
    Payment::create([
        'event_id' => $event->id,
        'client_id' => $event->client_id,
        'kind' => PaymentKind::Balance,
        'method' => 'cash',
        'status' => PaymentStatus::Paid,
        'amount' => 300.00,
        'paid_at' => now(),
    ]);

    Livewire::test(ResumenWidget::class)->assertSee('150,00 €');
});

// ------------------------------------------------------------------ agenda

it('mostra as proximas festas por ordem de data', function () {
    unEvento(['title' => 'Boda lejana', 'starts_at' => now()->addDays(20), 'ends_at' => now()->addDays(20)->addHours(6)]);
    unEvento(['title' => 'Bautizo cercano', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(4)]);

    $html = Livewire::test(ProximasFiestasWidget::class)
        ->assertSee('Bautizo cercano')
        ->assertSee('Boda lejana')
        ->html();

    expect(strpos($html, 'Bautizo cercano'))->toBeLessThan(strpos($html, 'Boda lejana'));
});

/*
| Os rascunhos aparecem de propósito. Uma festa por confirmar a três semanas
| é precisamente aquilo em que é preciso pensar — se só se vissem as
| confirmadas, o painel dava uma falsa sensação de calma.
*/
it('mostra tambem as festas em rascunho', function () {
    unEvento(['title' => 'Todavía en borrador', 'status' => EventStatus::Draft]);

    Livewire::test(ProximasFiestasWidget::class)->assertSee('Todavía en borrador');
});

it('nao mostra festas canceladas nem passadas', function () {
    unEvento(['title' => 'Cancelada', 'status' => EventStatus::Cancelled]);
    unEvento([
        'title' => 'Ya pasó',
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDays(10)->addHours(4),
    ]);

    Livewire::test(ProximasFiestasWidget::class)
        ->assertDontSee('Cancelada')
        ->assertDontSee('Ya pasó');
});

// ---------------------------------------------------------------- carrinha

it('mostra o material que sai esta semana', function () {
    $item = Item::create([
        'sku' => 'SILLA',
        'name' => ['es' => 'Silla Tiffany'],
        'slug' => ['es' => 'silla'],
        'stock_qty' => 40,
        'buffer_before_min' => 120,
        'buffer_after_min' => 1440,
    ]);

    $event = unEvento(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(6)]);

    app(AvailabilityService::class)->reserve(
        item: $item,
        window: $item->blockedWindowFor(new Period(
            CarbonImmutable::parse($event->starts_at),
            CarbonImmutable::parse($event->ends_at),
        )),
        quantity: 40,
        event: $event,
    );

    Livewire::test(MaterialQueSaleWidget::class)
        ->assertSee('Silla Tiffany')
        ->assertSee('Comunión de Uxía');
});

it('nao mostra material que sai daqui a um mes', function () {
    $item = Item::create([
        'sku' => 'PHOTOCALL',
        'name' => ['es' => 'Photocall floral'],
        'slug' => ['es' => 'photocall'],
        'stock_qty' => 1,
    ]);

    $event = unEvento(['starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHours(6)]);

    app(AvailabilityService::class)->reserve(
        item: $item,
        window: $item->blockedWindowFor(new Period(
            CarbonImmutable::parse($event->starts_at),
            CarbonImmutable::parse($event->ends_at),
        )),
        quantity: 1,
        event: $event,
    );

    Livewire::test(MaterialQueSaleWidget::class)->assertDontSee('Photocall floral');
});

/*
| Um bloqueio manual — uma peça partida — também tira material da garagem.
| Se não aparecesse aqui, descobria-se na véspera da festa.
*/
it('mostra tambem os bloqueios manuais', function () {
    $item = Item::create([
        'sku' => 'LETRAS',
        'name' => ['es' => 'Letras iluminadas'],
        'slug' => ['es' => 'letras'],
        'stock_qty' => 1,
    ]);

    app(AvailabilityService::class)->reserve(
        item: $item,
        window: new Period(
            CarbonImmutable::now()->addDay(),
            CarbonImmutable::now()->addDays(3),
        ),
        quantity: 1,
        blockedReason: 'Una letra fundida, en reparación',
    );

    Livewire::test(MaterialQueSaleWidget::class)
        ->assertSee('Letras iluminadas')
        ->assertSee('Una letra fundida, en reparación');
});
