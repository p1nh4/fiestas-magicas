<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Service;
use App\Support\Availability\AvailabilityService;
use App\Support\Availability\OutOfStockException;
use App\Support\Payments\FakeGateway;
use App\Support\Payments\PaymentGateway;
use App\Support\Period;
use App\Support\Quotes\QuoteAcceptance;
use App\Support\Quotes\QuoteBuilder;
use App\Support\Quotes\QuoteNotAcceptable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->builder = app(QuoteBuilder::class);
    $this->acceptance = app(QuoteAcceptance::class);

    $this->chairs = Item::create([
        'sku' => 'SILLA', 'stock_qty' => 40, 'price_per_day' => 3.50,
        'name' => ['es' => 'Silla Tiffany'],
        'slug' => ['es' => 'silla', 'gl' => 'cadeira', 'pt' => 'cadeira'],
        'buffer_before_min' => 120, 'buffer_after_min' => 1440,
    ]);

    $this->arch = Service::create([
        'name' => ['es' => 'Arco de globos'],
        'slug' => ['es' => 'arco', 'gl' => 'arco-gl', 'pt' => 'arco-pt'],
        'base_price' => 180.00,
    ]);

    $client = Client::create(['name' => 'María', 'email' => 'maria@example.com', 'locale' => 'es']);

    $this->event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-TEST-1',
        'title' => 'Comunión de Uxía',
        'event_type' => 'comunion',
        'locale' => 'es',
        'starts_at' => now()->addDays(45),
        'ends_at' => now()->addDays(45)->addHours(8),
    ]);
});

function sentQuote(): App\Models\Quote
{
    $quote = test()->builder->draftFor(test()->event);
    test()->builder->addService($quote, test()->arch);
    test()->builder->addItem($quote, test()->chairs, 40);

    return test()->builder->markSent($quote->fresh());
}

// ---------------------------------------------------------------- construção

it('soma os totais com o IVA e o sinal', function () {
    $quote = sentQuote();

    // 180,00 (arco) + 40 × 3,50 (cadeiras) = 320,00
    expect((string) $quote->subtotal)->toBe('320.00')
        ->and((string) $quote->tax_amount)->toBe('67.20')     // 21 %
        ->and((string) $quote->total)->toBe('387.20')
        ->and($quote->depositAmount())->toBe('116.16');       // 30 %
});

it('cobra os dias de aluguer, não só a quantidade', function () {
    $quote = $this->builder->draftFor($this->event);
    $this->builder->addItem($quote, $this->chairs, 10, days: 3);

    // 10 × 3,50 × 3 dias
    expect((string) $quote->fresh()->subtotal)->toBe('105.00');
});

it('recusa alterar um orçamento já enviado', function () {
    $quote = sentQuote();

    expect(fn () => $this->builder->addService($quote, $this->arch))
        ->toThrow(DomainException::class);
});

it('cria a versão seguinte em vez de editar a anterior', function () {
    $v1 = sentQuote();
    $v2 = $this->builder->reviseFrom($v1);

    expect($v2->version)->toBe(2)
        ->and($v2->lines)->toHaveCount(2)
        ->and($v2->status)->toBe(QuoteStatus::Draft)
        ->and($v1->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and((string) $v2->total)->toBe((string) $v1->total);
});

// ---------------------------------------------------------------- aceitação

it('ao aceitar, confirma o evento e reserva o material', function () {
    $quote = sentQuote();

    $this->acceptance->accept($quote, '10.0.0.1');

    expect($quote->fresh()->status)->toBe(QuoteStatus::Accepted)
        ->and($this->event->fresh()->status)->toBe(EventStatus::Confirmed)
        ->and((string) $this->event->fresh()->total_amount)->toBe('387.20');

    $reservation = Reservation::sole();

    expect($reservation->item_id)->toBe($this->chairs->id)
        ->and($reservation->quantity)->toBe(40)
        ->and($reservation->event_id)->toBe($this->event->id);
});

it('a reserva inclui as folgas de montagem e limpeza da peça', function () {
    $quote = sentQuote();
    $this->acceptance->accept($quote);

    $period = Reservation::sole()->period;

    expect($period->start->lessThan($this->event->starts_at))->toBeTrue()
        ->and($period->end->greaterThan($this->event->ends_at))->toBeTrue();
});

it('cria o sinal por pagar', function () {
    $this->acceptance->accept(sentQuote());

    $payment = Payment::sole();

    expect($payment->kind)->toBe(PaymentKind::Deposit)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and((string) $payment->amount)->toBe('116.16');
});

it('guarda prova da aceitação sem o IP em claro', function () {
    $quote = sentQuote();
    $this->acceptance->accept($quote, '88.1.2.3');

    $quote = $quote->fresh();

    expect($quote->accepted_at)->not->toBeNull()
        ->and($quote->accepted_ip_hash)->not->toContain('88.1.2.3')
        ->and(strlen($quote->accepted_ip_hash))->toBe(64);
});

/**
 * O caso que justifica a transação: entre o envio do orçamento e a
 * aceitação podem passar dias, e nesses dias o material pode ter saído
 * para outra festa.
 */
it('recusa a aceitação se o material entretanto acabou, sem confirmar nada', function () {
    $quote = sentQuote();

    // outra festa leva as 40 cadeiras no mesmo dia
    app(AvailabilityService::class)->reserve(
        item: $this->chairs,
        window: new Period(now()->addDays(44)->toImmutable(), now()->addDays(47)->toImmutable()),
        quantity: 40,
        blockedReason: 'Outra festa',
    );

    expect(fn () => $this->acceptance->accept($quote))->toThrow(OutOfStockException::class);

    // nada ficou a meio
    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent)
        ->and($this->event->fresh()->status)->toBe(EventStatus::Draft)
        ->and(Payment::count())->toBe(0);
});

it('não deixa aceitar duas vezes', function () {
    $quote = sentQuote();
    $this->acceptance->accept($quote);

    expect(fn () => $this->acceptance->accept($quote->fresh()))
        ->toThrow(QuoteNotAcceptable::class);

    expect(Reservation::count())->toBe(1);
});

it('não deixa aceitar um orçamento caducado', function () {
    $quote = sentQuote();
    $quote->update(['valid_until' => now()->subDay()]);

    expect(fn () => $this->acceptance->accept($quote->fresh()))
        ->toThrow(QuoteNotAcceptable::class);
});

it('caduca as outras versões quando uma é aceite', function () {
    $v1 = sentQuote();
    $v2 = $this->builder->markSent($this->builder->reviseFrom($v1));

    $this->acceptance->accept($v2->fresh());

    expect($v1->fresh()->status)->toBe(QuoteStatus::Expired)
        ->and($v2->fresh()->status)->toBe(QuoteStatus::Accepted);
});

// ---------------------------------------------------------------- página pública

it('abre o orçamento pelo link, sem login', function () {
    $quote = sentQuote();

    $this->get("/es/presupuesto/{$quote->public_token}")
        ->assertOk()
        ->assertSee('Comunión de Uxía')
        ->assertSee('387,20 €');
});

it('marca como visto na primeira abertura', function () {
    $quote = sentQuote();

    $this->get("/es/presupuesto/{$quote->public_token}");

    expect($quote->fresh()->status)->toBe(QuoteStatus::Viewed);
});

it('não mostra um orçamento em rascunho', function () {
    $quote = $this->builder->draftFor($this->event);

    $this->get("/es/presupuesto/{$quote->public_token}")->assertNotFound();
});

it('não mostra nada com um token inventado', function () {
    $this->get('/es/presupuesto/'.str_repeat('a', 64))->assertNotFound();
});

it('aceita pelo formulário e mostra a confirmação', function () {
    $quote = sentQuote();

    $this->post("/es/presupuesto/{$quote->public_token}/aceptar")
        ->assertRedirect(route('quote.show', ['locale' => 'es', 'token' => $quote->public_token]));

    expect($quote->fresh()->status)->toBe(QuoteStatus::Accepted);
});

it('mostra uma mensagem em vez de rebentar quando o material acabou', function () {
    $quote = sentQuote();

    app(AvailabilityService::class)->reserve(
        item: $this->chairs,
        window: new Period(now()->addDays(44)->toImmutable(), now()->addDays(47)->toImmutable()),
        quantity: 40,
        blockedReason: 'Outra festa',
    );

    $this->from("/es/presupuesto/{$quote->public_token}")
        ->post("/es/presupuesto/{$quote->public_token}/aceptar")
        ->assertSessionHas('quote_error');

    expect($quote->fresh()->status)->toBe(QuoteStatus::Sent);
});

// ---------------------------------------------------------------- pagamento

it('leva o cliente para a passarela e guarda a referência', function () {
    $quote = sentQuote();
    $this->acceptance->accept($quote);

    $this->post("/es/presupuesto/{$quote->public_token}/pagar")->assertRedirect();

    $payment = Payment::sole();

    expect($payment->provider)->toBe('fake')
        ->and($payment->provider_reference)->toStartWith('fake_');
});

/**
 * O teste de segurança que interessa: voltar à URL de sucesso não pode
 * dar um pagamento por feito. Sem isto, qualquer pessoa escrevia o
 * endereço à mão e ficava com o sinal por pago.
 */
it('não dá o sinal por pago só porque alguém abriu a URL de retorno', function () {
    $quote = sentQuote();
    $this->acceptance->accept($quote);
    $this->post("/es/presupuesto/{$quote->public_token}/pagar");

    $this->get("/es/presupuesto/{$quote->public_token}/pagado")
        ->assertSessionHas('quote_error');

    expect(Payment::sole()->status)->toBe(PaymentStatus::Pending);
});

it('marca como pago quando a passarela confirma', function () {
    $quote = sentQuote();
    $this->acceptance->accept($quote);
    $this->post("/es/presupuesto/{$quote->public_token}/pagar");

    $gateway = app(PaymentGateway::class);
    expect($gateway)->toBeInstanceOf(FakeGateway::class);
    $gateway->pretendPaid(Payment::sole()->provider_reference);

    $this->get("/es/presupuesto/{$quote->public_token}/pagado")
        ->assertSessionHas('payment_confirmed');

    expect(Payment::sole()->status)->toBe(PaymentStatus::Paid)
        ->and((string) $this->event->fresh()->paid_amount)->toBe('116.16');
});
