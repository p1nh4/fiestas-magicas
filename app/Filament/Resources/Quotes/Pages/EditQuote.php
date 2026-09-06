<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Support\Quotes\QuoteBuilder;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    /**
     * Apagar, mas so rascunhos.
     *
     * Nao havia forma nenhuma de apagar um orcamento. Dois cliques por
     * engano no botao "Presupuesto" do evento deixavam duas versoes vazias
     * para sempre, e o painel de entrada dizia "2 por enviar" todos os dias.
     *
     * Um orcamento ENVIADO continua a nao se apagar, e isso nao e um
     * esquecimento: e a unica prova do que a cliente viu quando aceitou.
     * Para esses cria-se a versao seguinte.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Borrar borrador')
                ->modalHeading('Borrar este borrador')
                ->modalDescription('Solo se pueden borrar los borradores. Un presupuesto ya enviado se queda como prueba de lo que vio la clienta.')
                ->visible(fn (): bool => $this->record->isEditable()),
        ];
    }

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
