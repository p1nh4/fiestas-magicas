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
 * Aviso interno: chegou um pedido novo.
 *
 * Sempre em espanhol — é a Sol que o lê. E o `replyTo` é o email de quem
 * pediu: assim ela carrega em "responder" e escreve à pessoa, em vez de
 * responder ao próprio site.
 */
class LeadAlertMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Lead $lead) {}

    public function envelope(): Envelope
    {
        $subject = 'Nueva petición · '.$this->lead->event_type->label().' · '.$this->lead->name;

        return new Envelope(
            subject: $subject,
            replyTo: array_filter([$this->lead->email]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.lead-alert');
    }
}
