<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Mail\Notifier;
use Illuminate\Support\Facades\DB;

/**
 * Dar um pagamento por liquidado.
 *
 * Isto estava escrito dentro do `QuoteController::paid`, e isso tinha dois
 * problemas.
 *
 * O primeiro é que só corria quando a pessoa VOLTAVA do Stripe. Quem paga
 * e fecha o separador — no telemóvel, com o autocarro a chegar — nunca
 * volta, e o dinheiro entrava na conta da Sol sem que o sistema soubesse:
 * o evento continuava a dizer "pendente de cobro" e a cliente nunca recebia
 * recibo. Não havia webhook nem nada que fosse perguntar depois. Agora há
 * o `payments:reconcile`, e é este serviço que os dois usam.
 *
 * O segundo é que eram três passos soltos — marcar o pagamento, somar ao
 * evento, mandar o recibo — sem transação. Se o segundo falhasse, o
 * pagamento ficava pago e o `paid_amount` do evento errado, que é o tipo de
 * diferença que só se descobre a fechar contas.
 *
 * Passou a haver um TERCEIRO caminho até aqui: o botão do backoffice, para
 * o dinheiro que entra por Bizum, transferência ou em mão. Esse botão fazia
 * o seu próprio `update` + `increment` e saltava tudo isto — sem `lock`,
 * sem reverificar o estado e, sobretudo, sem mandar recibo. Uma cliente que
 * pagasse por Bizum nunca recebia prova nenhuma. Agora entra por
 * `settleManually()`, que é este mesmo caminho com o método e a data que a
 * Sol escolheu.
 *
 * A regra de ouro fica igual e é a mais importante: **nunca se dá por pago
 * porque alguém abriu uma URL**. Quem confirma é o fornecedor — ou, no
 * dinheiro em mão, a própria Sol.
 */
final class SettlePayment
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * Confirma junto do fornecedor e, se ele disser que sim, liquida.
     *
     * @return bool true se ESTA chamada o liquidou. Já estar pago devolve
     *              false — e não é erro: quer dizer que outra via chegou
     *              lá primeiro.
     */
    public function settleIfPaid(Payment $payment, PaymentGateway $gateway): bool
    {
        if ($payment->provider_reference === null) {
            return false;
        }

        if (! $gateway->confirm($payment->provider_reference)) {
            return false;
        }

        return $this->settle($payment);
    }

    /**
     * Liquida um pagamento recebido fora da passarela.
     *
     * Bizum, transferência, dinheiro em mão. Quem confirma é a Sol, que o
     * viu na conta — daí não haver `confirm()` nenhum aqui. O resto do
     * caminho é exatamente o mesmo do cartão, e é esse o ponto.
     *
     * @param  PaymentMethod|string  $method
     */
    public function settleManually(Payment $payment, $method, ?\DateTimeInterface $paidAt = null): bool
    {
        return $this->settle($payment, [
            'method' => $method instanceof \BackedEnum ? $method->value : $method,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    /**
     * Liquida, assumindo que já há confirmação.
     *
     * O `lockForUpdate` dentro da transação é o que impede o recibo de sair
     * duas vezes quando dois caminhos se cruzam — a volta do browser, o
     * `payments:reconcile` e o botão do backoffice: quem chegar em segundo
     * lugar encontra o estado já em `paid` e desiste.
     *
     * @param  array<string, mixed>  $extra  Campos a gravar além do estado.
     */
    public function settle(Payment $payment, array $extra = []): bool
    {
        $liquidado = DB::transaction(function () use ($payment, $extra): bool {
            $fresh = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->first();

            if ($fresh === null || $fresh->status !== PaymentStatus::Pending) {
                return false;
            }

            $fresh->update($extra + [
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            /*
             * A soma é feita pelo Postgres, em `numeric`, com o valor a ir
             * como parâmetro.
             *
             * Aqui estava `increment('paid_amount', (float) $amount)`, e o
             * `increment` do Laravel escreve o valor DENTRO da expressão SQL
             * em vez de o passar como parâmetro — depois de o converter para
             * float. Num ficheiro cuja razão de existir é o dinheiro nunca
             * passar por vírgula flutuante, era o único sítio onde passava.
             */
            if ($fresh->event_id !== null) {
                DB::update(
                    'update events set paid_amount = paid_amount + ?::numeric, updated_at = now() where id = ?',
                    [(string) $fresh->amount, $fresh->event_id],
                );
            }

            return true;
        });

        // O recibo vai DEPOIS do commit, e de propósito. Dentro da
        // transação, um envio lento segura a linha bloqueada; e se a
        // transação abortasse a seguir, já tinha saído um "recebemos o teu
        // dinheiro" por um pagamento que afinal não ficou registado.
        if ($liquidado) {
            $this->notifier->depositReceived($payment->fresh());
        }

        return $liquidado;
    }
}
