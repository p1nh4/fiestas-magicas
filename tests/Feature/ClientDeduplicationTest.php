<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Lead;
use App\Support\Leads\LeadConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| A mesma pessoa, escrita de outra maneira
|--------------------------------------------------------------------------
| Converter um pedido tem de reencontrar a cliente que já lá está. Falhava
| nos dois casos mais comuns: a Sol grava o email com maiúsculas e o
| formulário chega em minúsculas; e o telefone gravado com indicativo nunca
| batia com o mesmo número escrito sem ele. Em ambos nascia um segundo
| cliente, e o histórico da mesma pessoa partia-se em dois.
*/

beforeEach(function () {
    $this->conversion = app(LeadConversion::class);
});

function pedidoDe(array $overrides): Lead
{
    return Lead::create($overrides + [
        'name' => 'Marta Pérez',
        'locale' => 'es',
        'event_type' => 'comunion',
        'event_date' => now()->addMonths(3)->toDateString(),
    ]);
}

it('reencontra a cliente com o email escrito com maiusculas', function () {
    $existente = Client::create([
        'name' => 'Marta Pérez',
        'email' => 'Marta@Gmail.com',
        'locale' => 'es',
    ]);

    $event = $this->conversion->convert(pedidoDe(['email' => 'marta@gmail.com']));

    expect(Client::count())->toBe(1)
        ->and($event->client_id)->toBe($existente->id);
});

it('reencontra a cliente pelo telefone escrito com outro formato', function () {
    $existente = Client::create([
        'name' => 'Marta Pérez',
        'phone' => '+34600111222',
        'locale' => 'es',
    ]);

    $event = $this->conversion->convert(pedidoDe(['phone' => '600 111 222']));

    expect(Client::count())->toBe(1)
        ->and($event->client_id)->toBe($existente->id);
});

it('nao confunde duas pessoas diferentes', function () {
    Client::create(['name' => 'Marta', 'email' => 'marta@gmail.com', 'locale' => 'es']);

    $event = $this->conversion->convert(pedidoDe([
        'name' => 'Lucía',
        'email' => 'lucia@gmail.com',
        'phone' => '699888777',
    ]));

    expect(Client::count())->toBe(2)
        ->and($event->client->name)->toBe('Lucía');
});
