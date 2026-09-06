<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * O lembrete dos dias antes da festa.
 *
 * Existe porque o email do sinal promete: "unos días antes te escribimos
 * para cerrar los últimos detalles". Sem isto, o sistema fazia uma promessa
 * que não cumpria — e uma promessa por cumprir custa mais confiança do que
 * a promessa nunca feita valia.
 */
class EventReminderMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Event $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mails.reminder.subject', [
            'event' => $this->event->title,
        ]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.event-reminder');
    }
}
