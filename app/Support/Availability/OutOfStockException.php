<?php

declare(strict_types=1);

namespace App\Support\Availability;

use App\Models\Item;
use App\Support\Period;
use RuntimeException;
use Throwable;

/**
 * A base de dados recusou a reserva por falta de stock.
 *
 * Traz o suficiente para o formulário dizer à pessoa o que aconteceu e o
 * que pode fazer — não "SQLSTATE[23514]".
 */
final class OutOfStockException extends RuntimeException
{
    private function __construct(
        public readonly Item $item,
        public readonly Period $period,
        public readonly int $requested,
        public readonly int $available,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function for(
        Item $item,
        Period $period,
        int $requested,
        int $available,
        ?Throwable $previous = null,
    ): self {
        return new self(
            $item,
            $period,
            $requested,
            $available,
            sprintf(
                'Não há stock de "%s" entre %s e %s: pediram-se %d unidades e há %d livres.',
                $item->getTranslation('name', config('app.fallback_locale', 'es')) ?: $item->sku,
                $period->start->format('d/m/Y H:i'),
                $period->end->format('d/m/Y H:i'),
                $requested,
                $available,
            ),
            $previous,
        );
    }

    /** Mensagem traduzida para mostrar ao cliente. */
    public function forHumans(): string
    {
        return trans_choice('availability.out_of_stock', $this->available, [
            'item' => $this->item->getTranslation('name', app()->getLocale()) ?: $this->item->sku,
            'requested' => $this->requested,
            'available' => $this->available,
        ]);
    }
}
