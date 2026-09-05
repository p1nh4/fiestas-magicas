<?php

declare(strict_types=1);

namespace App\Support\Quotes;

use App\Enums\EventStatus;
use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use App\Support\Availability\AvailabilityService;
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
     * @throws \App\Support\Availability\OutOfStockException
     */
    public function accept(Quote $quote, ?string $ip = null): Quote
    {
        $this->assertAcceptable($quote);

        return DB::transaction(function () use ($quote, $ip) {
            $event = $quote->event;
            $usage = $event->occupancyWindow();

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

            if (bccomp($deposit, '0.00', 2) > 0) {
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
