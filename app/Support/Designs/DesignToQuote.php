<?php

declare(strict_types=1);

namespace App\Support\Designs;

use App\Models\EventDesign;
use App\Models\Quote;
use App\Support\Availability\AvailabilityService;
use App\Support\Period;
use App\Support\Quotes\QuoteBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Passa o material do projeto para um orçamento.
 *
 * É a automação que justifica o módulo existir. Sem ela, a Sol escrevia a
 * lista no caderno e depois voltava a escrevê-la no orçamento — duas vezes
 * o mesmo trabalho, e a segunda com a atenção já gasta.
 *
 * Três regras, e todas vêm de coisas que já estão decididas noutro sítio:
 *
 *  1. Nunca se escreve num orçamento já enviado. Cria-se a versão seguinte.
 *     Quem impõe isto é o QuoteBuilder; aqui só se escolhe qual usar.
 *
 *  2. A disponibilidade é VERIFICADA mas não bloqueia. Um aviso de que
 *     faltam cadeiras é informação útil ao fazer o orçamento; recusar-se a
 *     escrever a linha seria decidir pela Sol, que pode muito bem alugar as
 *     que faltam a um colega. Quem decide mesmo é o trigger, e só quando a
 *     cliente aceitar.
 *
 *  3. Preços e nomes ficam congelados na linha pelo QuoteBuilder. Este
 *     serviço não conhece preços nenhuns — e é bom que não conheça.
 */
final class DesignToQuote
{
    public function __construct(
        private readonly QuoteBuilder $builder,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Escreve o material do desenho no orçamento em rascunho do evento,
     * criando-o se não existir.
     *
     * @return array{quote: Quote, added: int, warnings: list<string>}
     */
    public function push(EventDesign $design): array
    {
        $event = $design->event;
        $warnings = $this->checkAvailability($design);

        return DB::transaction(function () use ($design, $event, $warnings) {
            $quote = $this->draftFor($event);

            /*
             * Substituir, nao acrescentar.
             *
             * O botao acrescentava sempre. Bastava a Sol juntar uma peca ao
             * projeto e voltar a carregar em "Pasar al presupuesto" para
             * ficar com a lista inteira duplicada e o total a dobrar — e
             * depois apagar seis linhas a mao no repetidor.
             *
             * Apagam-se so as linhas de MATERIAL. Os servicos que ela tenha
             * acrescentado a mao ao orcamento (uma deslocacao, uma hora
             * extra) nao vem do projeto e nao lhe compete a este botao
             * mexer-lhes.
             */
            $quote->lines()->whereNotNull('item_id')->delete();

            $added = 0;

            // Os dias faturáveis saem da janela do evento, não da folga
            // logística: a cliente paga os dias que usa, não o tempo que a
            // peça leva a ir e voltar.
            $days = (new Period(
                CarbonImmutable::parse($event->starts_at),
                CarbonImmutable::parse($event->ends_at),
            ))->billableDays();

            foreach ($design->items()->with('item')->get() as $line) {
                if ($line->item === null) {
                    continue;
                }

                $this->builder->addItem(
                    quote: $quote,
                    item: $line->item,
                    quantity: $line->quantity,
                    days: $days,
                    description: $line->notes ?: null,
                );

                $added++;
            }

            // Apagar linhas mexe nos totais tanto como acrescenta-las.
            $this->builder->recalculate($quote);

            return [
                'quote' => $quote->fresh(),
                'added' => $added,
                'warnings' => $warnings,
            ];
        });
    }

    /**
     * O que falta, se faltar alguma coisa.
     *
     * Corre ANTES da transação de propósito: é uma leitura, e mantê-la fora
     * evita segurar a transação aberta durante várias contas de stock.
     *
     * @return list<string>
     */
    public function checkAvailability(EventDesign $design): array
    {
        $event = $design->event;

        if ($event === null) {
            return [];
        }

        $usage = new Period(
            CarbonImmutable::parse($event->starts_at),
            CarbonImmutable::parse($event->ends_at),
        );

        $warnings = [];

        foreach ($design->items()->with('item')->get() as $line) {
            if ($line->item === null) {
                continue;
            }

            $window = $this->availability->blockedWindow($line->item, $usage);
            $free = $this->availability->availableQuantity($line->item, $window);

            if ($free < $line->quantity) {
                $warnings[] = $line->item->name.': pides '.$line->quantity.', quedan '.$free;
            }
        }

        return $warnings;
    }

    /**
     * O rascunho onde escrever.
     *
     * Se o último orçamento já foi enviado, faz-se a versão seguinte em vez
     * de lhe mexer — a regra do projeto inteiro, e a única prova do que a
     * cliente viu quando aceitou.
     */
    private function draftFor($event): Quote
    {
        $current = $event->currentQuote();

        if ($current === null) {
            return $this->builder->draftFor($event);
        }

        return $current->isEditable()
            ? $current
            : $this->builder->reviseFrom($current);
    }
}
