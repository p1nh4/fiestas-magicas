<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * O orçamento, com o link mágico.
 *
 * É o email mais importante do sistema: leva a credencial que permite
 * aceitar o orçamento sem login. Por isso diz-se à pessoa, no corpo, que o
 * link é dela e não deve ser reenviado — a segurança de um link mágico
 * depende de quem o tem.
 */
class QuoteReadyMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Quote $quote,
        public readonly string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mails.quote.subject', [
            'event' => $this->quote->event?->title ?? '',
        ]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.quote-ready');
    }
}
