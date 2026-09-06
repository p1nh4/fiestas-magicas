<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\Payment;
use App\Support\Payments\FakeGateway;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\SettlePayment;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    // A passarela falsa fica registada como singleton para que o comando e
    // o teste falem com a MESMA instância — é nela que o `pretendPaid`
    // guarda o que foi pago.
    $this->gateway = new FakeGateway;
    $this->app->instance(PaymentGateway::class, $this->gateway);
});

function sinalPendente(array $overrides = []): Payment
{
    $client = Client::create([
        'name' => 'Marta Pérez',
        'email' => 'marta@example.test',
        'locale' => 'es',
    ]);

    $event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-2026-9001',
        'title' => 'Comunión de prueba',
        'event_type' => 'comunion',
        'status' => EventStatus::Confirmed,
        'locale' => 'es',
        'starts_at' => now()->addDays(30)->setTime(13, 0),
        'ends_at' => now()->addDays(30)->setTime(20, 0),
    ]);

    return Payment::create($overrides + [
        'event_id' => $event->id,
        'client_id' => $client->id,
        'kind' => PaymentKind::Deposit,
        'method' => PaymentMethod::Card,
        'status' => PaymentStatus::Pending,
        'amount' => '150.00',
        'currency' => 'EUR',
        'provider' => 'fake',
        'provider_reference' => 'fake_ref_1',
    ]);
}

/*
| O buraco que este comando tapa.
|
| Quem paga no telemóvel e fecha o separador nunca volta ao site. Antes,
| esse pagamento ficava "pendente" para sempre: o dinheiro na conta da Sol
| e o evento a dizer que estava por cobrar.
*/
it('confirma com a passarela um pagamento de quem nunca voltou ao site', function () {
    $payment = sinalPendente();
    $this->gateway->pretendPaid('fake_ref_1');

    $this->artisan('payments:reconcile')->assertSuccessful();

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and((float) $payment->event->refresh()->paid_amount)->toBe(150.0);
});

it('nao toca no que a passarela nao confirma', function () {
    $payment = sinalPendente();

    $this->artisan('payments:reconcile')->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending)
        ->and((float) $payment->event->refresh()->paid_amount)->toBe(0.0);
});

/*
| Idempotência.
|
| O agendador corre de hora a hora. Sem a guarda do `lockForUpdate` e da
| verificação de estado, a segunda passagem somava outra vez ao evento e
| mandava um segundo recibo do mesmo dinheiro.
*/
it('nao soma duas vezes se correr outra vez', function () {
    $payment = sinalPendente();
    $this->gateway->pretendPaid('fake_ref_1');

    $this->artisan('payments:reconcile')->assertSuccessful();
    $this->artisan('payments:reconcile')->assertSuccessful();

    expect((float) $payment->event->refresh()->paid_amount)->toBe(150.0);
});

it('nao liquida duas vezes o mesmo pagamento', function () {
    $payment = sinalPendente();
    $settle = app(SettlePayment::class);

    expect($settle->settle($payment))->toBeTrue()
        ->and($settle->settle($payment->fresh()))->toBeFalse();
});

/*
| Um pagamento pendente há três semanas não é um pagamento que a Stripe vá
| confirmar. Continuar a perguntar por ele é ruído; vale mais que alguém
| olhe para ele.
*/
it('ignora os pagamentos velhos', function () {
    $payment = sinalPendente();
    $payment->forceFill(['created_at' => now()->subDays(30)])->save();
    $this->gateway->pretendPaid('fake_ref_1');

    $this->artisan('payments:reconcile')->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending);
});

it('nao pergunta por pagamentos sem referencia da passarela', function () {
    $payment = sinalPendente(['provider_reference' => null]);

    $this->artisan('payments:reconcile')->assertSuccessful();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending);
});

it('esta agendado', function () {
    $comandos = collect(app(Schedule::class)->events())
        ->map(fn ($e) => $e->command ?? '')
        ->implode(' ');

    expect($comandos)->toContain('payments:reconcile');
});
