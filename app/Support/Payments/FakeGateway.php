<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Payment;

/**
 * Passarela falsa: usada nos testes e em local enquanto nao ha chaves Stripe.
 *
 * Nao finge que cobrou. Devolve uma referencia previsivel e uma URL que
 * volta para o site, para o fluxo poder ser percorrido de ponta a ponta
 * sem nunca tocar em dinheiro nem em dados de cartao.
 */
final class FakeGateway implements PaymentGateway
{
    /** @var array<string, bool> */
    private array $confirmed = [];

    public function checkout(Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $reference = 'fake_'.$payment->uuid;

        return new CheckoutSession($reference, $successUrl);
    }

    public function confirm(string $reference): bool
    {
        return $this->confirmed[$reference] ?? false;
    }

    /** Só para os testes: simula que a pessoa pagou. */
    public function pretendPaid(string $reference): void
    {
        $this->confirmed[$reference] = true;
    }

    public function name(): string
    {
        return 'fake';
    }
}
