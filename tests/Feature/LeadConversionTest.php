<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\Lead;
use App\Support\Events\EventReference;
use App\Support\Leads\LeadConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->conversion = app(LeadConversion::class);
});

function newLead(array $overrides = []): Lead
{
    // Atencao a ordem: com o operador + do PHP, ganham as chaves da
    // ESQUERDA. Os overrides tem de vir primeiro ou nao fazem nada.
    return Lead::create($overrides + [
        'name' => 'Marta Pérez',
        'email' => 'marta@example.com',
        'phone' => '+34600111222',
        'locale' => 'es',
        'event_type' => 'comunion',
        'event_date' => now()->addMonths(3)->toDateString(),
        'guests_count' => 60,
        'venue' => 'Pazo de Mos',
        'message' => 'Queremos mesa dulce y photocall.',
        'utm_source' => 'instagram',
    ]);
}

// ---------------------------------------------------------------- conversão

it('cria cliente e evento a partir de um pedido do site', function () {
    $lead = newLead();

    $event = $this->conversion->convert($lead);

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->status)->toBe(EventStatus::Draft)
        ->and($event->guests_count)->toBe(60)
        ->and($event->venue_name)->toBe('Pazo de Mos')
        ->and($event->notes)->toBe('Queremos mesa dulce y photocall.')
        ->and($event->client->name)->toBe('Marta Pérez')
        ->and($event->client->source)->toBe('instagram')
        ->and($event->lead_id)->toBe($lead->id);
});

it('marca o pedido como convertido sem o apagar', function () {
    $lead = newLead();

    $this->conversion->convert($lead);
    $lead->refresh();

    expect($lead->exists)->toBeTrue()
        ->and($lead->converted_at)->not->toBeNull()
        ->and($lead->contacted_at)->not->toBeNull()
        ->and($lead->status)->toBe(LeadStatus::Contacted)
        ->and($lead->client_id)->not->toBeNull();
});

it('nao duplica um cliente que ja existe com o mesmo email', function () {
    $existing = Client::create([
        'name' => 'Marta P.',
        'email' => 'marta@example.com',
        'locale' => 'es',
    ]);

    $event = $this->conversion->convert(newLead());

    expect(Client::count())->toBe(1)
        ->and($event->client_id)->toBe($existing->id);
});

it('nao duplica um cliente que ja existe so com o mesmo telefone', function () {
    $existing = Client::create([
        'name' => 'Marta pelo telefone',
        'phone' => '+34600111222',
        'locale' => 'es',
    ]);

    $event = $this->conversion->convert(newLead(['email' => null]));

    expect(Client::count())->toBe(1)
        ->and($event->client_id)->toBe($existing->id);
});

/*
| RGPD. Pedir um orçamento não é consentir receber publicidade — são duas
| coisas diferentes e a lei trata-as como duas coisas diferentes. Se este
| teste falhar, alguém pôs a empresa a arriscar uma coima.
*/
it('nao assume consentimento de marketing ao converter', function () {
    $event = $this->conversion->convert(newLead());

    expect($event->client->marketing_opt_in_at)->toBeNull()
        ->and($event->client->acceptsMarketing())->toBeFalse();
});

it('usa a data pedida pelo cliente quando nao se indica outra', function () {
    $lead = newLead(['event_date' => '2027-05-15']);

    $event = $this->conversion->convert($lead);

    expect($event->starts_at->toDateString())->toBe('2027-05-15')
        ->and($event->ends_at->greaterThan($event->starts_at))->toBeTrue();
});

it('respeita as horas indicadas a mao', function () {
    $start = Carbon::parse('2027-05-15 17:00');
    $end = Carbon::parse('2027-05-15 23:30');

    $event = $this->conversion->convert(newLead(), $start, $end);

    expect($event->starts_at->format('H:i'))->toBe('17:00')
        ->and($event->ends_at->format('H:i'))->toBe('23:30');
});

it('aguenta um pedido sem data nenhuma', function () {
    $event = $this->conversion->convert(newLead(['event_date' => null]));

    expect($event->starts_at)->not->toBeNull()
        ->and($event->ends_at->greaterThan($event->starts_at))->toBeTrue();
});

// ---------------------------------------------------------------- referência

it('gera referencias sequenciais dentro do mesmo ano', function () {
    $a = $this->conversion->convert(newLead(['email' => 'a@example.com', 'phone' => null]));
    $b = $this->conversion->convert(newLead(['email' => 'b@example.com', 'phone' => null]));

    $year = $a->starts_at->year;

    expect($a->reference)->toBe("FM-{$year}-0001")
        ->and($b->reference)->toBe("FM-{$year}-0002");
});

it('conta tambem os eventos apagados, para nunca reutilizar uma referencia', function () {
    $first = $this->conversion->convert(newLead(['email' => 'a@example.com', 'phone' => null]));
    $year = $first->starts_at->year;

    $first->delete();

    $second = $this->conversion->convert(newLead(['email' => 'b@example.com', 'phone' => null]));

    expect($second->reference)->toBe("FM-{$year}-0002");
});

/*
| O advisory lock só existe dentro de uma transação. Fora dela é largado de
| imediato, ou seja, não serve de nada. Este teste não prova a concorrência
| — prova que o contrato está escrito onde é preciso.
*/
it('gera a referencia dentro de uma transacao', function () {
    DB::transaction(function () {
        $ref = app(EventReference::class)->next(Carbon::parse('2027-01-01'));

        expect($ref)->toBe('FM-2027-0001');
    });
});
