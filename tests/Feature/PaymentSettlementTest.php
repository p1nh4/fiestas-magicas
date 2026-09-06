<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\DepositReceivedMail;
use App\Models\Client;
use App\Models\Event;
use App\Models\Payment;
use App\Support\Payments\SettlePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function umPagamentoPendente(string $amount = '150.00'): Payment
{
    $client = Client::create([
        'name' => 'Marta',
        'email' => 'marta@example.com',
        'locale' => 'es',
    ]);

    $event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-PAY-'.random_int(1000, 9999),
        'title' => 'Comunión',
        'event_type' => 'comunion',
        'status' => EventStatus::Confirmed,
        'locale' => 'es',
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(6),
        'total_amount' => '500.00',
        'paid_amount' => '0.00',
    ]);

    return Payment::create([
        'event_id' => $event->id,
        'client_id' => $client->id,
        'kind' => PaymentKind::Deposit,
        'method' => PaymentMethod::Card,
        'status' => PaymentStatus::Pending,
        'amount' => $amount,
        'currency' => 'EUR',
    ]);
}

/*
|--------------------------------------------------------------------------
| Liquidação de pagamentos
|--------------------------------------------------------------------------
| O dinheiro que entra por Bizum, transferência ou em mão passava por um
| caminho próprio no backoffice, sem bloqueio e — o que mais custava — sem
| recibo para a cliente. Agora entra pelo mesmo `SettlePayment` do cartão.
*/

it('o pagamento em mao soma ao evento e manda recibo', function () {
    Mail::fake();

    $payment = umPagamentoPendente('150.00');

    $liquidado = app(SettlePayment::class)->settleManually($payment, PaymentMethod::Bizum);

    expect($liquidado)->toBeTrue();

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->method)->toBe(PaymentMethod::Bizum)
        ->and($payment->paid_at)->not->toBeNull()
        ->and((string) $payment->event->fresh()->paid_amount)->toEqual('150.00');

    // Enfileirado, não enviado: o `DepositReceivedMail` é ShouldQueue, para
    // um SMTP lento nunca segurar a resposta a quem acabou de pagar.
    Mail::assertQueued(DepositReceivedMail::class);
    Mail::assertQueuedCount(1);
});

it('nao soma duas vezes o mesmo dinheiro', function () {
    Mail::fake();

    $payment = umPagamentoPendente('150.00');
    $settle = app(SettlePayment::class);

    expect($settle->settleManually($payment, PaymentMethod::Bizum))->toBeTrue();

    // Segunda passagem: o botão outra vez, ou o `payments:reconcile` a
    // cruzar-se com ele. Tem de desistir, e o evento tem de ficar na mesma.
    expect($settle->settleManually($payment->fresh(), PaymentMethod::Cash))->toBeFalse();

    expect((string) $payment->event->fresh()->paid_amount)->toEqual('150.00');

    // Um recibo só. Dois seriam a cliente a receber duas vezes "recebemos o
    // teu dinheiro" pelo mesmo dinheiro.
    Mail::assertQueuedCount(1);
});

it('o valor somado nao passa por virgula flutuante', function () {
    Mail::fake();

    // 0,07 e 0,29 são dos valores que mais depressa denunciam uma soma feita
    // em float. Feitas em `numeric` pelo Postgres, dão exatamente 0,36.
    $payment = umPagamentoPendente('0.07');
    app(SettlePayment::class)->settleManually($payment, PaymentMethod::Cash);

    $segundo = Payment::create([
        'event_id' => $payment->event_id,
        'client_id' => $payment->client_id,
        'kind' => PaymentKind::Balance,
        'method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Pending,
        'amount' => '0.29',
        'currency' => 'EUR',
    ]);

    app(SettlePayment::class)->settleManually($segundo, PaymentMethod::Cash);

    expect((string) $payment->event->fresh()->paid_amount)->toEqual('0.36');
});
