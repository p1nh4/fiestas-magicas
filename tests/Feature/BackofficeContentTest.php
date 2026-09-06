<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| Estes testes não verificam ecrãs — verificam as regras que os ecrãs
| prometem. Um formulário do Filament é uma sugestão; o CHECK da base de
| dados é que decide, e é isso que aqui se prova.
*/

it('nao publica um trabalho sem permissao do cliente', function () {
    $project = Project::create([
        'title' => ['es' => 'Comunión en Nigrán'],
        'slug' => ['es' => 'comunion-nigran'],
        'event_type' => 'comunion',
        'city' => 'Nigrán',
    ]);

    expect(fn () => $project->update(['is_published' => true]))
        ->toThrow(QueryException::class);
});

it('publica um trabalho quando ha permissao', function () {
    $project = Project::create([
        'title' => ['es' => 'Comunión en Nigrán'],
        'slug' => ['es' => 'comunion-nigran'],
        'event_type' => 'comunion',
        'city' => 'Nigrán',
        'consent_at' => now(),
    ]);

    $project->update(['is_published' => true, 'published_at' => now()]);

    expect($project->fresh()->is_published)->toBeTrue();
});

it('nao guarda uma opiniao sem data de consentimento', function () {
    expect(fn () => Testimonial::create([
        'author_name' => 'Inventada',
        'body' => 'Todo genial.',
        'locale' => 'es',
    ]))->toThrow(QueryException::class);
});

it('guarda uma opiniao com consentimento', function () {
    $client = Client::create(['name' => 'Marta', 'email' => 'marta@example.com', 'locale' => 'es']);

    $testimonial = Testimonial::create([
        'client_id' => $client->id,
        'author_name' => 'Marta P.',
        'body' => 'Quedó todo precioso.',
        'locale' => 'es',
        'rating' => 5,
        'source' => 'whatsapp',
        'consent_at' => now(),
        'is_published' => true,
    ]);

    expect($testimonial->exists)->toBeTrue();
});

/*
| O trabalho aparece na página do concelho quando a localidade bate certo
| com o nome da zona. É por isso que o formulário avisa para escrever igual
| — e é isto que prova que o aviso não é decorativo.
*/
it('um trabalho aparece na pagina da zona quando a localidade bate certo', function () {
    App\Models\ServiceArea::create([
        'name' => 'Nigrán',
        'slug' => ['es' => 'nigran'],
        'country' => 'ES',
        'intro' => ['es' => str_repeat('Texto propio de la zona. ', 12)],
        'is_published' => true,
    ]);

    Project::create([
        'title' => ['es' => 'Comunión de Uxía'],
        'slug' => ['es' => 'comunion-uxia'],
        'event_type' => 'comunion',
        'city' => 'Nigrán',
        'venue' => 'Pazo de Mos',
        'consent_at' => now(),
        'is_published' => true,
        'published_at' => now(),
    ]);

    $this->get('/es/zonas/nigran')
        ->assertOk()
        ->assertSee('Comunión de Uxía');
});

it('um trabalho por publicar nao aparece na pagina da zona', function () {
    App\Models\ServiceArea::create([
        'name' => 'Nigrán',
        'slug' => ['es' => 'nigran'],
        'country' => 'ES',
        'intro' => ['es' => str_repeat('Texto propio de la zona. ', 12)],
        'is_published' => true,
    ]);

    Project::create([
        'title' => ['es' => 'Boda sin permiso'],
        'slug' => ['es' => 'boda-sin-permiso'],
        'event_type' => 'boda',
        'city' => 'Nigrán',
    ]);

    $this->get('/es/zonas/nigran')
        ->assertOk()
        ->assertDontSee('Boda sin permiso');
});
