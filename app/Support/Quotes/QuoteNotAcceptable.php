<?php

declare(strict_types=1);

namespace App\Support\Quotes;

use App\Models\Quote;
use RuntimeException;

final class QuoteNotAcceptable extends RuntimeException
{
    private function __construct(public readonly Quote $quote, public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function expired(Quote $quote): self
    {
        return new self($quote, 'expired', "O orçamento v{$quote->version} caducou.");
    }

    public static function wrongStatus(Quote $quote): self
    {
        return new self($quote, 'status', "O orçamento v{$quote->version} não está à espera de resposta.");
    }

    /** Mensagem para o cliente, no idioma dele. */
    public function forHumans(): string
    {
        return __('quotes.errors.'.$this->reason);
    }
}
