<?php

declare(strict_types=1);

namespace App\Support\Leads;

use App\Enums\EventStatus;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\Lead;
use App\Support\Events\EventReference;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Transforma um pedido do site num cliente e num evento em rascunho.
 *
 * É o passo que a Sol faz mais vezes, por isso não pode viver dentro de um
 * botão do Filament: aqui é testável, e o botão passa a ser três linhas.
 *
 * Três decisões que valem a pena explicar:
 *
 *  1. NÃO se cria um cliente duplicado. Se já houver alguém com o mesmo
 *     email ou telefone, reaproveita-se. Um cliente repetido parte o
 *     histórico em dois e ninguém volta a juntá-lo.
 *
 *  2. A referência (FM-2026-0031) é gerada dentro de um advisory lock, tal
 *     como as reservas. Duas conversões ao mesmo tempo davam a mesma
 *     referência e o UNIQUE da base de dados rebentava na cara de alguém.
 *
 *  3. O lead não é apagado nem alterado para lá do necessário. Fica lá,
 *     ligado ao cliente, com a data de conversão — é o que permite dizer
 *     daqui a um ano quantos pedidos do Instagram deram festa.
 */
final class LeadConversion
{
    public function __construct(private readonly EventReference $reference) {}

    /**
     * @param  Carbon|null  $startsAt  Início real da festa. Sem isto, usa-se a
     *                                 data que o cliente escreveu no formulário
     *                                 com um horário por omissão, que a Sol
     *                                 corrige no evento.
     */
    public function convert(Lead $lead, ?Carbon $startsAt = null, ?Carbon $endsAt = null): Event
    {
        return DB::transaction(function () use ($lead, $startsAt, $endsAt) {
            $client = $this->clientFor($lead);

            $start = $startsAt ?? $this->defaultStart($lead);
            $end = $endsAt ?? $start->copy()->addHours(
                max(1, (int) config('business.event.default_hours', 6))
            );

            $event = Event::create([
                'client_id' => $client->getKey(),
                'lead_id' => $lead->getKey(),
                'reference' => $this->reference->next($start),
                'title' => $this->titleFor($lead, $client),
                'event_type' => $lead->event_type,
                'status' => EventStatus::Draft,
                'starts_at' => $start,
                'ends_at' => $end,
                'venue_name' => $lead->venue,
                'guests_count' => $lead->guests_count,
                'locale' => $lead->locale,
                'notes' => $lead->message,
            ]);

            $updates = [
                'client_id' => $client->getKey(),
                'contacted_at' => $lead->contacted_at ?? now(),
                'converted_at' => now(),
            ];

            /*
            | O estado só se toca se ainda estiver por responder.
            |
            | Duas armadilhas de uma vez. A primeira: se a Sol já o pôs em
            | "perdido" ou "ganho", quem manda é ela — converter não é
            | motivo para lhe apagar a decisão.
            |
            | A segunda é mais traiçoeira. O `new` da coluna é um DEFAULT da
            | base de dados, e o Eloquent não conhece os defaults da base de
            | dados: num objeto acabado de criar sem `status`, `$lead->status`
            | é null, não LeadStatus::New. Reescrever esse null numa coluna
            | NOT NULL rebenta. Por isso o estado entra no update só quando
            | há mesmo um estado novo para escrever.
            */
            if ($lead->status === null || $lead->status === LeadStatus::New) {
                $updates['status'] = LeadStatus::Contacted;
            }

            $lead->update($updates);

            return $event;
        });
    }

    /** Já foi convertido? O botão no backoffice usa isto para se esconder. */
    public function alreadyConverted(Lead $lead): bool
    {
        return $lead->converted_at !== null;
    }

    /**
     * Cliente existente ou novo.
     *
     * A procura é por email OU telefone porque muita gente escreve só um
     * dos dois. O CHECK da base de dados garante que pelo menos um existe.
     */
    private function clientFor(Lead $lead): Client
    {
        if ($lead->client_id !== null && $lead->client !== null) {
            return $lead->client;
        }

        $existing = Client::query()
            ->when($lead->email !== null, fn ($q) => $q->orWhere('email', $lead->email))
            ->when($lead->phone !== null, fn ($q) => $q->orWhere('phone', $lead->phone))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Client::create([
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'whatsapp' => $lead->phone,
            'locale' => $lead->locale,
            // De onde veio, para o marketing saber onde investir. Sem utm,
            // assume-se o site — foi de lá que o formulário chegou.
            'source' => $lead->utm_source ?? 'web',
            // Atenção: NÃO se marca marketing_opt_in_at. Pedir um orçamento
            // não é consentir receber publicidade (RGPD). Esse consentimento
            // tem de ser dado à parte.
        ]);
    }

    private function defaultStart(Lead $lead): Carbon
    {
        $date = $lead->event_date !== null
            ? Carbon::parse($lead->event_date)
            : Carbon::now()->addMonth();

        return $date->setTime((int) config('business.event.default_start_hour', 12), 0);
    }

    /**
     * Título de trabalho. É o que a Sol vê na agenda, por isso leva o tipo
     * de festa e o nome de quem a pediu — não uma referência que ninguém
     * decora de cor.
     */
    private function titleFor(Lead $lead, Client $client): string
    {
        return trim($lead->event_type->label().' · '.$client->name);
    }
}
