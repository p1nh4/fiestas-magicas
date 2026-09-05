<?php

declare(strict_types=1);

use App\Enums\LeadStatus;
use App\Models\Lead;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

function validLead(array $overrides = []): array
{
    return array_merge([
        'name' => 'María Pérez',
        'phone' => '600 111 222',
        'event_type' => 'comunion',
        'event_date' => now()->addMonths(4)->toDateString(),
        'guests_count' => 60,
        'venue' => 'Salón A Carballeira',
        'message' => 'Quería algo en rosa y dorado.',
        'privacy' => '1',
    ], $overrides);
}

it('guarda um pedido válido e leva à página de agradecimento', function () {
    $this->post('/es/presupuesto', validLead())
        ->assertRedirect(route('lead.thanks', ['locale' => 'es']));

    $lead = Lead::sole();

    expect($lead->name)->toBe('María Pérez')
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->locale->value)->toBe('es');
});

it('guarda o idioma em que a pessoa escreveu', function () {
    $this->post('/pt/presupuesto', validLead());

    expect(Lead::sole()->locale->value)->toBe('pt');
});

it('aceita só o email, sem telefone', function () {
    $this->post('/es/presupuesto', validLead(['phone' => null, 'email' => 'maria@example.com']))
        ->assertSessionHasNoErrors();

    expect(Lead::sole()->email)->toBe('maria@example.com');
});

it('recusa um pedido sem forma nenhuma de responder', function () {
    $this->post('/es/presupuesto', validLead(['phone' => null, 'email' => null]))
        ->assertSessionHasErrors(['email', 'phone']);

    expect(Lead::count())->toBe(0);
});

it('exige o consentimento de privacidade, sem o pré-marcar', function () {
    $this->post('/es/presupuesto', validLead(['privacy' => null]))
        ->assertSessionHasErrors('privacy');

    expect(Lead::count())->toBe(0);
});

it('recusa uma data que já passou', function () {
    $this->post('/es/presupuesto', validLead(['event_date' => now()->subDay()->toDateString()]))
        ->assertSessionHasErrors('event_date');
});

it('apanha robots pela armadilha do campo escondido', function () {
    $this->post('/es/presupuesto', validLead(['website' => 'http://spam.example']))
        ->assertSessionHasErrors('website');

    expect(Lead::count())->toBe(0);
});

it('nunca guarda o IP em claro', function () {
    $this->post('/es/presupuesto', validLead());

    $lead = Lead::sole();

    expect($lead->ip_hash)->not->toBeNull()
        ->and($lead->ip_hash)->not->toContain('127.0.0.1')
        ->and(strlen($lead->ip_hash))->toBe(64);
});

it('guarda de onde veio a visita, para saber onde investir', function () {
    $this->post('/es/presupuesto?utm_source=instagram&utm_campaign=comuniones', validLead([
        'utm_source' => 'instagram',
        'utm_campaign' => 'comuniones',
    ]));

    $lead = Lead::sole();

    expect($lead->utm_source)->toBe('instagram')
        ->and($lead->utm_campaign)->toBe('comuniones');
});

it('normaliza o email para minúsculas', function () {
    $this->post('/es/presupuesto', validLead(['email' => '  MARIA@Example.COM ', 'phone' => null]));

    expect(Lead::sole()->email)->toBe('maria@example.com');
});

it('não deixa entrar na página de agradecimento sem ter enviado nada', function () {
    $this->get('/es/gracias')->assertRedirect(route('home', ['locale' => 'es']));
});

it('trava um envio em massa do mesmo sítio', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->post('/es/presupuesto', validLead());
    }

    $this->post('/es/presupuesto', validLead())->assertStatus(429);
});
