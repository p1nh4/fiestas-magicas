<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Support\Quotes\QuoteBuilder;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    /**
     * Os totais nunca vêm do formulário.
     *
     * O repeater grava as linhas; a partir daí, quem manda é o QuoteBuilder,
     * que soma em bcmath. Se os totais fossem campos editáveis, mais cedo ou
     * mais tarde havia um orçamento cujo total não bate com as linhas — e
     * numa discussão com um cliente é sempre a linha que tem razão.
     */
    protected function afterSave(): void
    {
        $quote = $this->record;

        foreach ($quote->lines()->get() as $line) {
            $line->update(['line_total' => $line->computeTotal()]);
        }

        app(QuoteBuilder::class)->recalculate($quote);

        $this->refreshFormData([
            'subtotal', 'tax_amount', 'total', 'discount_amount',
        ]);
    }
}
