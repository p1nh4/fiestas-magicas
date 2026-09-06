<?php

declare(strict_types=1);

namespace App\Support\Quotes;

use App\Enums\QuoteStatus;
use App\Models\Event;
use App\Models\Item;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Constroi orcamentos.
 *
 * Duas regras que valem por tudo o resto:
 *
 *  1. Um orcamento enviado NUNCA se edita. Cria-se a versao seguinte. Assim
 *     ha sempre prova do que o cliente viu quando carregou em aceitar — e
 *     numa discussao sobre precos isso e a unica coisa que conta.
 *
 *  2. A descricao e o preco ficam CONGELADOS na linha. Se o catalogo subir
 *     de preco amanha, o orcamento de hoje continua a dizer o que dizia.
 */
final class QuoteBuilder
{
    /** Novo orcamento em rascunho para um evento. */
    public function draftFor(Event $event): Quote
    {
        $version = (int) $event->quotes()->max('version') + 1;

        return Quote::create([
            'event_id' => $event->getKey(),
            'version' => $version,
            'status' => QuoteStatus::Draft,
            'public_token' => $this->token(),
            'valid_until' => now()->addDays((int) config('business.quote.valid_days')),
            'tax_rate' => (float) config('business.quote.vat_rate'),
            'deposit_pct' => (float) config('business.quote.deposit_pct'),
        ]);
    }

    /**
     * Nova versao a partir de uma existente: copia as linhas e deixa a
     * anterior intacta. E o que se usa quando o cliente pede uma alteracao.
     */
    public function reviseFrom(Quote $previous): Quote
    {
        return DB::transaction(function () use ($previous) {
            $quote = $this->draftFor($previous->event);

            foreach ($previous->lines as $line) {
                $quote->lines()->create($line->only([
                    'service_id', 'item_id', 'description',
                    'quantity', 'days', 'unit_price', 'position',
                ]) + ['line_total' => $line->line_total]);
            }

            return $this->recalculate($quote);
        });
    }

    public function addService(Quote $quote, Service $service, float $quantity = 1, ?string $description = null): QuoteLine
    {
        return $this->addLine($quote, [
            'service_id' => $service->getKey(),
            'description' => $description ?? $this->nameFor($service, $quote),
            'quantity' => $quantity,
            'days' => 1,
            'unit_price' => (float) $service->base_price,
        ]);
    }

    public function addItem(Quote $quote, Item $item, int $quantity, int $days = 1, ?string $description = null): QuoteLine
    {
        return $this->addLine($quote, [
            'item_id' => $item->getKey(),
            'description' => $description ?? $this->nameFor($item, $quote),
            'quantity' => $quantity,
            'days' => max(1, $days),
            'unit_price' => (float) $item->price_per_day,
        ]);
    }

    /** Linha livre: transporte, uma ideia so daquele cliente, um desconto. */
    public function addCustom(Quote $quote, string $description, float $unitPrice, float $quantity = 1): QuoteLine
    {
        return $this->addLine($quote, [
            'description' => $description,
            'quantity' => $quantity,
            'days' => 1,
            'unit_price' => $unitPrice,
        ]);
    }

    /**
     * Recalcula os totais.
     *
     * Tudo em bcmath. Somar dinheiro em virgula flutuante da diferencas de
     * cents que ninguem consegue explicar ao cliente.
     */
    public function recalculate(Quote $quote): Quote
    {
        $subtotal = '0.00';

        foreach ($quote->lines()->get() as $line) {
            $subtotal = bcadd($subtotal, $line->computeTotal(), 2);
        }

        $discount = (string) $quote->discount_amount;
        $base = bcsub($subtotal, $discount, 2);

        if (bccomp($base, '0.00', 2) < 0) {
            $base = '0.00';
        }

        $tax = Money::percent($base, (string) $quote->tax_rate);

        $quote->update([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => bcadd($base, $tax, 2),
        ]);

        return $quote->fresh();
    }

    /** Marca como enviado. So a partir daqui e que deixa de se poder editar. */
    public function markSent(Quote $quote): Quote
    {
        $quote->update(['status' => QuoteStatus::Sent, 'sent_at' => now()]);

        return $quote->fresh();
    }

    private function addLine(Quote $quote, array $attributes): QuoteLine
    {
        if (! $quote->isEditable()) {
            throw new \DomainException(
                "O orçamento {$quote->version} já foi enviado. Cria a versão seguinte em vez de o alterar."
            );
        }

        $line = $quote->lines()->create($attributes + [
            'position' => (int) $quote->lines()->max('position') + 1,
            'line_total' => 0,
        ]);

        $line->update(['line_total' => $line->computeTotal()]);
        $this->recalculate($quote);

        return $line->fresh();
    }

    /** Nome no idioma do cliente, nao no de quem esta a fazer o orcamento. */
    private function nameFor(Service|Item $model, Quote $quote): string
    {
        $locale = $quote->event?->locale?->value ?? config('app.locale');

        return $model->getTranslation('name', $locale, true) ?: ($model->sku ?? '');
    }

    /**
     * Token do link publico. 64 caracteres aleatorios: e a unica coisa que
     * protege o orcamento, por isso nao pode ser adivinhavel.
     */
    private function token(): string
    {
        return Str::random(64);
    }
}
