<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmação para quem preencheu o formulário.
 *
 * Vai para a fila (ShouldQueue) e não é enviado no pedido: se o servidor de
 * email estiver em baixo, o formulário tem de continuar a funcionar. Uma
 * pessoa que perde o pedido por causa de um SMTP lento é um cliente
 * perdido; um email que chega dois minutos depois não é nada.
 */
class LeadReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mails.lead.subject'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.lead-received');
    }
}
