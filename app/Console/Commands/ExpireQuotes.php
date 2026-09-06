<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Console\Command;

/**
 * Marca como caducados os orçamentos cuja validade passou.
 *
 * O `QuoteStatus::Expired` existia no enum e ninguém o punha: um orçamento
 * enviado em março continuava a dizer "enviado" em outubro. Duas
 * consequências, e a segunda é a que custa dinheiro:
 *
 *  1. A lista do backoffice enche-se de coisas mortas e deixa de se ver o
 *     que está mesmo à espera de resposta.
 *
 *  2. Uma cliente que guardou o link podia aceitar meses depois, a preços
 *     de março. O `QuoteAcceptance` já recusa por data — mas é melhor o
 *     estado dizer a verdade do que depender de uma verificação no fim.
 *
 * O `valid_until` é uma DATA, não um instante: um orçamento válido até 15
 * de maio ainda vale no dia 15 inteiro. Daí o `<` e não o `<=`.
 */
class ExpireQuotes extends Command
{
    protected $signature = 'quotes:expire';

    protected $description = 'Marca como caducados los presupuestos cuya validez ha pasado';

    public function handle(): int
    {
        $expired = Quote::query()
            ->whereIn('status', [QuoteStatus::Sent->value, QuoteStatus::Viewed->value])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update([
                'status' => QuoteStatus::Expired->value,
                'updated_at' => now(),
            ]);

        if ($expired > 0) {
            $this->info("{$expired} presupuestos marcados como caducados.");
        }

        return self::SUCCESS;
    }
}
