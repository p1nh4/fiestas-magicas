<?php

declare(strict_types=1);

use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\ReservationStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Reservation;
use App\Models\Service;
use App\Support\Quotes\QuoteAcceptance;
use App\Support\Quotes\QuoteBuilder;
use App\Support\Quotes\QuoteNotAcceptable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| As duas maneiras de reservar material a dobrar
|--------------------------------------------------------------------------
| O `QuoteFlowTest` prova que aceitar um orçamento faz o que deve. Estes
| provam o que acontece quando a aceitação NÃO chega sozinha: um segundo
| clique, ou uma versão nova em cima de uma já aceite. Nos dois casos, o
| material acabava reservado duas vezes — e o trigger do stock deixava
| passar, porque somado ainda cabia.
*/

beforeEach(function () {
    $this->builder = app(QuoteBuilder::class);
    $this->acceptance = app(QuoteAcceptance::class);

    $this->chairs = Item::create([
        'sku' => 'SILLA', 'stock_qty' => 100, 'price_per_day' => 3.50,
        'name' => ['es' => 'Silla Tiffany'],
        'slug' => ['es' => 'silla', 'gl' => 'cadeira-gl', 'pt' => 'cadeira-pt'],
    ]);

    $this->arch = Service::create([
        'name' => ['es' => 'Arco de globos'],
        'slug' => ['es' => 'arco', 'gl' => 'arco-gl', 'pt' => 'arco-pt'],
        'base_price' => 180.00,
    ]);

    $client = Client::create(['name' => 'María', 'email' => 'maria@example.com', 'locale' => 'es']);

    $this->event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-GUARD-1',
        'title' => 'Comunión de Uxía',
        'event_type' => 'comunion',
        'locale' => 'es',
        'starts_at' => now()->addDays(45),
        'ends_at' => now()->addDays(45)->addHours(8),
    ]);
});

function orcamentoEnviado(int $cadeiras): Quote
{
    $quote = test()->builder->draftFor(test()->event);
    test()->builder->addService($quote, test()->arch);
    test()->builder->addItem($quote, test()->chairs, $cadeiras);

    return test()->builder->markSent($quote->fresh());
}

function cadeirasReservadas(): int
{
    return (int) Reservation::query()
        ->where('event_id', test()->event->id)
        ->where('status', ReservationStatus::Confirmed->value)
        ->sum('quantity');
}

// -------------------------------------------------- duplo clique em aceitar

it('aceitar duas vezes nao reserva o material a dobrar', function () {
    $quote = orcamentoEnviado(40);

    $this->acceptance->accept($quote);

    // O segundo pedido chega com o mesmo objeto em memória, tal como
    // chegaria vindo de um duplo clique: o estado que ele traz ainda diz
    // "enviado". Quem tem de recusar é a verificação de dentro da transação,
    // com a linha bloqueada.
    expect(fn () => $this->acceptance->accept($quote))
        ->toThrow(QuoteNotAcceptable::class);

    expect(cadeirasReservadas())->toBe(40);

    expect(Payment::where('event_id', $this->event->id)
        ->where('kind', PaymentKind::Deposit->value)
        ->count())->toBe(1);
});

// -------------------------------------------------- versão nova sobre aceite

it('aceitar a versao seguinte liberta o que a anterior reservou', function () {
    $v1 = orcamentoEnviado(40);
    $this->acceptance->accept($v1);

    expect(cadeirasReservadas())->toBe(40);

    // A cliente pediu mais cadeiras. A v1 fica intacta, como prova.
    $v2 = $this->builder->reviseFrom($v1->fresh());
    $this->builder->addItem($v2, $this->chairs, 20);
    $v2 = $this->builder->markSent($v2->fresh());

    $this->acceptance->accept($v2);

    // 60, e não 100: as 40 da v1 foram libertadas.
    expect(cadeirasReservadas())->toBe(60);

    expect($v1->fresh()->status)->toBe(QuoteStatus::Accepted)
        ->and($v2->fresh()->status)->toBe(QuoteStatus::Accepted);
});

it('nao deixa dois sinais pendentes para a mesma festa', function () {
    $v1 = orcamentoEnviado(40);
    $this->acceptance->accept($v1);

    $v2 = $this->builder->reviseFrom($v1->fresh());
    $v2 = $this->builder->markSent($v2->fresh());
    $this->acceptance->accept($v2);

    $sinais = Payment::where('event_id', $this->event->id)
        ->where('kind', PaymentKind::Deposit->value)
        ->where('status', PaymentStatus::Pending->value)
        ->get();

    expect($sinais)->toHaveCount(1)
        ->and((string) $sinais->first()->amount)
        ->toEqual($v2->fresh()->depositAmount());
});
