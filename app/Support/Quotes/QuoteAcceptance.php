<?php

declare(strict_types=1);

namespace App\Support\Quotes;

use App\Enums\EventStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\ReservationStatus;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Reservation;
use App\Support\Availability\AvailabilityService;
use App\Support\Availability\OutOfStockException;
use Illuminate\Support\Facades\DB;

/**
 * O que acontece quando o cliente carrega em "aceitar".
 *
 * Quatro coisas ao mesmo tempo, ou nenhuma:
 *   1. o orcamento passa a aceite, com prova de quando e de onde
 *   2. o evento passa a confirmado e fica com o total
 *   3. as pecas do orcamento ficam RESERVADAS
 *   4. cria-se o sinal a pagar
 *
 * O ponto 3 e o que obriga a transacao. Entre o momento em que enviamos o
 * orcamento e o momento em que o cliente o aceita podem passar dias, e
 * nesses dias as cadeiras podem ter sido reservadas para outra festa.
 * Se nao houver stock, o trigger da base de dados recusa, a transacao
 * desfaz-se inteira, e o cliente ve uma mensagem em vez de ficar com um
 * evento confirmado sem material.
 */
final class QuoteAcceptance
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * @throws QuoteNotAcceptable
     * @throws OutOfStockException
     */
    public function accept(Quote $quote, ?string $ip = null): Quote
    {
        // Falha cedo e barato, com a mensagem certa para o cliente. Nao e
        // esta a verificacao que decide — e a de dentro da transacao.
        $this->assertAcceptable($quote);

        return DB::transaction(function () use ($quote, $ip) {
            /*
             * A verificacao que conta, com a linha bloqueada.
             *
             * Sem isto, dois pedidos ao mesmo tempo — um duplo clique, ou o
             * telemovel a repetir o POST numa ligacao ma — passavam AMBOS
             * pela verificacao de cima e entravam ambos aqui. Resultado: o
             * material reservado a dobrar (com stock de sobra o trigger
             * deixa passar, porque 20+20 ainda cabe em 40) e dois sinais
             * pendentes para a mesma festa. O `throttle:20,1` da rota nao
             * trava isto: sao dois pedidos, nao vinte.
             */
            $locked = Quote::query()
                ->whereKey($quote->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw QuoteNotAcceptable::wrongStatus($quote);
            }

            $this->assertAcceptable($locked);

            $quote = $locked;
            $event = $quote->event;
            $usage = $event->occupancyWindow();

            /*
             * O material que uma versao anterior ja tinha reservado.
             *
             * A cliente aceitou a v1 com 40 cadeiras, pediu uma alteracao, e
             * a Sol enviou-lhe a v2 com 60. Sem isto, aceitar a v2 reservava
             * mais 60 POR CIMA das 40 — 100 cadeiras presas para uma festa
             * que precisa de 60 — e, se o stock nao chegasse, a cliente
             * recebia "nao ha material" por causa da reserva dela propria.
             *
             * Cancela-se primeiro e reserva-se a seguir, dentro da mesma
             * transacao: se a nova reserva nao couber, tudo se desfaz e as
             * reservas antigas ficam como estavam. E ha uma festa so, logo
             * ha uma lista de material so — a do orcamento que vale agora.
             *
             * Os bloqueios de manutencao nao tem evento e nao sao tocados.
             */
            Reservation::query()
                ->where('event_id', $event->getKey())
                ->whereIn('status', [
                    ReservationStatus::Confirmed->value,
                    ReservationStatus::Hold->value,
                ])
                ->update([
                    'status' => ReservationStatus::Cancelled->value,
                    'updated_at' => now(),
                ]);

            foreach ($quote->lines()->whereNotNull('item_id')->with('item')->get() as $line) {
                $item = $line->item;

                if ($item === null) {
                    continue;   // peça apagada do catálogo entretanto
                }

                $this->availability->reserve(
                    item: $item,
                    window: $this->availability->blockedWindow($item, $usage),
                    quantity: (int) ceil((float) $line->quantity),
                    event: $event,
                );
            }

            $quote->update([
                'status' => QuoteStatus::Accepted,
                'accepted_at' => now(),
                // prova de aceitação sem guardar o IP em claro
                'accepted_ip_hash' => Lead::hashIp($ip),
            ]);

            $deposit = $quote->depositAmount();

            $event->update([
                'status' => EventStatus::Confirmed,
                'total_amount' => $quote->total,
                'deposit_amount' => $deposit,
            ]);

            // As outras versões deixam de estar em cima da mesa.
            $event->quotes()
                ->whereKeyNot($quote->getKey())
                ->whereIn('status', [QuoteStatus::Sent->value, QuoteStatus::Viewed->value])
                ->update(['status' => QuoteStatus::Expired->value, 'updated_at' => now()]);

            /*
             * O sinal. Um por festa, nao um por versao aceite.
             *
             * Se ja houver um sinal PAGO, nao se cria outro: o que a cliente
             * pagou esta pago, e a diferenca acerta-se no dia (`Balance`).
             * Se houver um por pagar, atualiza-se o valor em vez de deixar
             * dois pendentes na lista da Sol — e limpa-se a referencia da
             * passarela, porque a sessao de pagamento antiga era do valor
             * antigo.
             */
            if (bccomp($deposit, '0.00', 2) > 0) {
                $jaPago = $event->payments()
                    ->where('kind', PaymentKind::Deposit->value)
                    ->where('status', PaymentStatus::Paid->value)
                    ->exists();

                if (! $jaPago) {
                    $pendente = $event->payments()
                        ->where('kind', PaymentKind::Deposit->value)
                        ->where('status', PaymentStatus::Pending->value)
                        ->lockForUpdate()
                        ->first();

                    if ($pendente !== null) {
                        $pendente->update([
                            'amount' => $deposit,
                            'currency' => $quote->currency,
                            'provider' => null,
                            'provider_reference' => null,
                        ]);
                    } else {
                        Payment::create([
                            'event_id' => $event->getKey(),
                            'client_id' => $event->client_id,
                            'kind' => PaymentKind::Deposit,
                            'method' => PaymentMethod::Card,
                            'status' => PaymentStatus::Pending,
                            'amount' => $deposit,
                            'currency' => $quote->currency,
                        ]);
                    }
                }
            }

            return $quote->fresh();
        });
    }

    public function reject(Quote $quote): Quote
    {
        $this->assertAcceptable($quote);

        $quote->update(['status' => QuoteStatus::Rejected, 'rejected_at' => now()]);

        return $quote->fresh();
    }

    /** Primeira abertura do link: serve para a Sol saber que já foi visto. */
    public function markViewed(Quote $quote): void
    {
        if ($quote->status === QuoteStatus::Sent) {
            $quote->update(['status' => QuoteStatus::Viewed, 'viewed_at' => now()]);
        }
    }

    private function assertAcceptable(Quote $quote): void
    {
        if ($quote->isExpired()) {
            throw QuoteNotAcceptable::expired($quote);
        }

        if (! in_array($quote->status, [QuoteStatus::Sent, QuoteStatus::Viewed], true)) {
            throw QuoteNotAcceptable::wrongStatus($quote);
        }
    }
}
