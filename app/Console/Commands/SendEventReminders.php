<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Support\Mail\Notifier;
use Illuminate\Console\Command;

/**
 * O lembrete dos dias antes da festa.
 *
 * Corre uma vez por dia e apanha as festas confirmadas que começam dentro
 * da janela. O `reminder_sent_at` é o que impede o email de sair quatro
 * vezes: o comando corre todos os dias e a janela dura vários, portanto sem
 * a marca a cliente recebia o mesmo texto de segunda a quinta.
 *
 * Marca-se ANTES de enviar, não depois. Se o envio falhar, perde-se um
 * lembrete; se se marcasse depois e o processo morresse a meio, a cliente
 * recebia o email outra vez no dia seguinte. Entre falhar em silêncio uma
 * vez e chatear alguém repetidamente, prefere-se a primeira — e a falha
 * fica no log, que o Notifier escreve.
 */
class SendEventReminders extends Command
{
    protected $signature = 'events:remind';

    protected $description = 'Manda el recordatorio de los días previos a cada fiesta';

    public function handle(Notifier $notifier): int
    {
        $days = max(1, (int) config('business.event.reminder_days', 5));

        $events = Event::query()
            ->with('client')
            ->whereIn('status', [EventStatus::Confirmed->value, EventStatus::InProgress->value])
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [now(), now()->addDays($days)])
            ->get();

        foreach ($events as $event) {
            $event->update(['reminder_sent_at' => now()]);
            $notifier->eventReminder($event);
        }

        if ($events->isNotEmpty()) {
            $this->info("{$events->count()} recordatorios enviados.");
        }

        return self::SUCCESS;
    }
}
