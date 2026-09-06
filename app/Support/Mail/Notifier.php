<?php

declare(strict_types=1);

namespace App\Support\Mail;

use App\Mail\DepositReceivedMail;
use App\Mail\EventReminderMail;
use App\Mail\LeadAlertMail;
use App\Mail\LeadReceivedMail;
use App\Mail\QuoteReadyMail;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quote;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Quem recebe o quê, em que idioma, e o que acontece quando falha.
 *
 * Está tudo num sítio por duas razões.
 *
 * A primeira é o idioma. Cada pessoa recebe o email na língua em que
 * escreveu — quem preencheu o formulário em português não deve receber a
 * resposta em espanhol. Espalhado pelos controladores, mais cedo ou mais
 * tarde havia um sítio onde alguém se esquecia.
 *
 * A segunda é o que fazer quando o email falha, e é a mais importante:
 * **nunca rebentar o que estava a acontecer**. Um SMTP em baixo não pode
 * fazer o formulário público devolver um erro 500 nem impedir a Sol de
 * marcar um orçamento como enviado. O trabalho fica feito, o email fica
 * registado no log, e alguém trata disso depois. É por isso que cada método
 * aqui apanha Throwable e não deixa passar nada.
 */
final class Notifier
{
    /** Pedido novo: confirmação para a pessoa e aviso para a Sol. */
    public function leadReceived(Lead $lead): void
    {
        $locale = $lead->locale?->value ?? config('app.locale');

        // Muita gente deixa o email em branco e só põe o telefone. Não é
        // erro nenhum: é resposta por WhatsApp em vez de email.
        if (filled($lead->email)) {
            $this->send(
                fn () => Mail::to($lead->email)->locale($locale)->send(new LeadReceivedMail($lead)),
                'confirmação de pedido',
                ['lead' => $lead->id],
            );
        }

        if (filled($alert = $this->alertAddress())) {
            $this->send(
                fn () => Mail::to($alert)->locale('es')->send(new LeadAlertMail($lead)),
                'aviso interno de pedido',
                ['lead' => $lead->id],
            );
        }
    }

    /** O orçamento com o link mágico, no idioma do evento. */
    public function quoteSent(Quote $quote, string $url): void
    {
        $client = $quote->event?->client;

        if ($client === null || blank($client->email)) {
            return;
        }

        $locale = $quote->event?->locale?->value ?? config('app.locale');

        $this->send(
            fn () => Mail::to($client->email)->locale($locale)->send(new QuoteReadyMail($quote, $url)),
            'orçamento',
            ['quote' => $quote->id],
        );
    }

    /** Recibo do sinal. */
    public function depositReceived(Payment $payment): void
    {
        $client = $payment->client ?? $payment->event?->client;

        if ($client === null || blank($client->email)) {
            return;
        }

        $locale = $payment->event?->locale?->value ?? config('app.locale');

        $this->send(
            fn () => Mail::to($client->email)->locale($locale)->send(new DepositReceivedMail($payment)),
            'recibo do sinal',
            ['payment' => $payment->id],
        );
    }

    /** O lembrete dos dias antes, no idioma do evento. */
    public function eventReminder(Event $event): void
    {
        $client = $event->client;

        if ($client === null || blank($client->email)) {
            return;
        }

        $locale = $event->locale?->value ?? config('app.locale');

        $this->send(
            fn () => Mail::to($client->email)->locale($locale)->send(new EventReminderMail($event)),
            'lembrete de festa',
            ['event' => $event->id],
        );
    }

    /**
     * Para onde vão os avisos internos.
     *
     * Se não houver endereço configurado, não se envia nada — e não se
     * inventa um. Um email para um endereço adivinhado é pior do que email
     * nenhum: dá a sensação de que a Sol foi avisada quando não foi.
     */
    private function alertAddress(): ?string
    {
        return config('business.alert_email')
            ?: config('business.email')
            ?: null;
    }

    /**
     * @param  callable():void  $action
     * @param  array<string, mixed>  $context
     */
    private function send(callable $action, string $what, array $context = []): void
    {
        try {
            $action();
        } catch (Throwable $e) {
            // Sem re-lançar. O trabalho já está feito; o email é o extra.
            Log::error("Falhou o envio do email ({$what}): ".$e->getMessage(), $context);
        }
    }
}
