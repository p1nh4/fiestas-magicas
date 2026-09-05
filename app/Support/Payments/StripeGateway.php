<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Payment;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * Stripe.
 *
 * O site NUNCA ve dados de cartao: a pessoa e enviada para o Checkout da
 * Stripe e volta de la. Isso tira o projeto quase todo do ambito do PCI-DSS,
 * o que para uma empresa de uma pessoa so faz toda a diferenca.
 */
final class StripeGateway implements PaymentGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function checkout(Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $payment->uuid,
            'customer_email' => $payment->client?->email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    // em cêntimos, e como inteiro: a Stripe não aceita decimais
                    'unit_amount' => (int) bcmul((string) $payment->amount, '100', 0),
                    'product_data' => [
                        'name' => __('quotes.deposit_for', [
                            'event' => $payment->event?->title ?? '',
                        ]),
                    ],
                ],
            ]],
            'metadata' => [
                'payment_uuid' => $payment->uuid,
                'event_uuid' => $payment->event?->uuid,
            ],
        ]);

        if (! is_string($session->url)) {
            throw new RuntimeException('A Stripe não devolveu uma URL de checkout.');
        }

        return new CheckoutSession($session->id, $session->url);
    }

    public function confirm(string $reference): bool
    {
        $session = $this->stripe->checkout->sessions->retrieve($reference);

        return $session->payment_status === Session::PAYMENT_STATUS_PAID;
    }

    public function name(): string
    {
        return 'stripe';
    }
}
