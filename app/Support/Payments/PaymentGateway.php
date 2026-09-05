<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Payment;

/**
 * Cobrar dinheiro, atras de uma interface.
 *
 * Nao e arquitetura por gosto: a empresa ainda nao tem conta Stripe, e sem
 * isto nada do que depende de pagamentos podia ser escrito nem testado.
 * Assim o resto do sistema fica pronto, os testes correm contra a
 * implementacao falsa, e no dia em que houver chaves so muda uma linha no
 * service provider.
 *
 * Serve tambem para o dia em que a Stripe deixar de servir — na Galiza
 * muita gente paga por Bizum, e o Redsys entra aqui sem mexer no resto.
 */
interface PaymentGateway
{
    /**
     * Prepara a cobranca e devolve para onde mandar a pessoa.
     *
     * @param  string  $successUrl  para onde volta depois de pagar
     * @param  string  $cancelUrl   para onde volta se desistir
     */
    public function checkout(Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Confirma junto do fornecedor que o pagamento entrou.
     * Nunca se confia na volta do browser: so nisto.
     */
    public function confirm(string $reference): bool;

    public function name(): string;
}
