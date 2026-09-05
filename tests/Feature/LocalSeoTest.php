<?php

declare(strict_types=1);

use App\Models\ServiceArea;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Uma zona pronta a publicar: com texto a sério. */
function areaWithText(array $overrides = []): ServiceArea
{
    $text = str_repeat('Texto propio de la zona, escrito a mano. ', 8);

    return ServiceArea::create($overrides + [
        'name' => 'Nigrán',
        'slug' => ['es' => 'nigran', 'gl' => 'nigran', 'pt' => 'nigran'],
        'province' => 'Pontevedra',
        'country' => 'ES',
        'distance_km' => 7.5,
        'travel_minutes' => 12,
        'intro' => ['es' => $text, 'gl' => $text, 'pt' => $text],
        'is_published' => true,
    ]);
}

// ------------------------------------------------------------- doorway pages

/*
| A regra que protege o site inteiro.
|
| Onze páginas iguais com o nome do concelho trocado chamam-se doorway
| pages. O Google não se limita a ignorá-las: desvaloriza o domínio todo.
| Por isso publicar exige texto próprio, e a regra vive no CHECK da base de
| dados — uma validação de formulário contorna-se com um update.
*/
it('nao deixa publicar uma zona sem texto proprio', function () {
    ServiceArea::create([
        'name' => 'Gondomar',
        'slug' => ['es' => 'gondomar'],
        'country' => 'ES',
    ]);

    expect(fn () => ServiceArea::where('name', 'Gondomar')->update(['is_published' => true]))
        ->toThrow(QueryException::class);
});

it('nao deixa publicar com uma frase de encher', function () {
    $area = ServiceArea::create([
        'name' => 'Gondomar',
        'slug' => ['es' => 'gondomar'],
        'country' => 'ES',
    ]);

    expect(fn () => $area->update([
        'intro' => ['es' => 'Decoramos fiestas en Gondomar.'],
        'is_published' => true,
    ]))->toThrow(QueryException::class);
});

it('deixa publicar quando ha texto a serio', function () {
    $area = areaWithText();

    expect($area->is_published)->toBeTrue()
        ->and($area->isPublishable())->toBeTrue();
});

/*
| O aviso no backoffice e o CHECK na base de dados têm de dizer o mesmo
| número. Se divergirem, a Sol vê "listo" no ecrã e leva com um erro do
| Postgres ao gravar — o pior dos dois mundos.
*/
it('o minimo do model e o mesmo minimo do schema', function () {
    $schema = file_get_contents(database_path('schema/schema.sql'));

    expect($schema)->toContain(
        "length(coalesce(intro->>'es', '')) >= ".ServiceArea::MIN_INTRO
    );
});

// ------------------------------------------------------------------ páginas

it('mostra a pagina de uma zona publicada', function () {
    areaWithText();

    $this->get('/es/zonas/nigran')
        ->assertOk()
        ->assertSee('Nigrán')
        ->assertSee('Texto propio de la zona');
});

it('devolve 404 numa zona por publicar', function () {
    ServiceArea::create([
        'name' => 'Gondomar',
        'slug' => ['es' => 'gondomar'],
        'country' => 'ES',
    ]);

    $this->get('/es/zonas/gondomar')->assertNotFound();
});

it('devolve 404 num endereco inventado', function () {
    $this->get('/es/zonas/paris')->assertNotFound();
});

it('responde no endereco de cada idioma', function () {
    ServiceArea::create([
        'name' => 'Valença',
        'slug' => ['es' => 'valenca-es', 'gl' => 'valenca-gl', 'pt' => 'valenca-pt'],
        'country' => 'PT',
        'intro' => array_fill_keys(['es', 'gl', 'pt'], str_repeat('Texto proprio da zona. ', 12)),
        'is_published' => true,
    ]);

    $this->get('/es/zonas/valenca-es')->assertOk();
    $this->get('/gl/zonas/valenca-gl')->assertOk();
    $this->get('/pt/zonas/valenca-pt')->assertOk();

    // O endereço de um idioma não responde noutro: são URLs distintos, e
    // servir a mesma página em dois endereços é conteúdo duplicado.
    $this->get('/pt/zonas/valenca-es')->assertNotFound();
});

it('lista as zonas publicadas com ligacao e as outras so como texto', function () {
    areaWithText();
    ServiceArea::create([
        'name' => 'Tui',
        'slug' => ['es' => 'tui'],
        'country' => 'ES',
    ]);

    $response = $this->get('/es/zonas');

    $response->assertOk()
        ->assertSee('Nigrán')
        ->assertSee('Tui')
        ->assertSee('/es/zonas/nigran', escape: false);

    // A zona por publicar aparece pelo nome, mas ninguém lhe pode clicar.
    expect($response->getContent())->not->toContain('/es/zonas/tui');
});

// ------------------------------------------------------------------ sitemap

it('poe as zonas publicadas no sitemap, nas tres linguas', function () {
    areaWithText();

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('/es/zonas/nigran')
        ->and($xml)->toContain('/gl/zonas/nigran')
        ->and($xml)->toContain('/pt/zonas/nigran')
        ->and($xml)->toContain('/es/zonas');
});

it('nao anuncia no sitemap zonas que dao 404', function () {
    ServiceArea::create([
        'name' => 'Tui',
        'slug' => ['es' => 'tui'],
        'country' => 'ES',
    ]);

    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/zonas/tui');
});

/*
| O hreflang tem de apontar para o endereço REAL de cada idioma. A primeira
| versão do sitemap trocava só o prefixo do idioma e mantinha o slug — o que
| dava links para páginas que não existem, e ensinava o Google a desconfiar
| do resto.
*/
it('o hreflang aponta para o endereco de cada idioma, nao para o mesmo slug', function () {
    ServiceArea::create([
        'name' => 'Valença',
        'slug' => ['es' => 'valenca-es', 'gl' => 'valenca-gl', 'pt' => 'valenca-pt'],
        'country' => 'PT',
        'intro' => array_fill_keys(['es', 'gl', 'pt'], str_repeat('Texto proprio da zona. ', 12)),
        'is_published' => true,
    ]);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)->toContain('hreflang="pt-PT" href="'.url('/pt/zonas/valenca-pt').'"')
        ->and($xml)->not->toContain('/pt/zonas/valenca-es');
});

// ------------------------------------------------------------------- factos

it('nao inventa texto para as zonas semeadas', function () {
    $this->seed(Database\Seeders\ServiceAreaSeeder::class);

    expect(ServiceArea::count())->toBeGreaterThan(5)
        // Nenhuma nasce publicada, e nenhuma nasce com texto: os factos
        // (distância, província) semeiam-se; o texto escreve-se.
        ->and(ServiceArea::where('is_published', true)->count())->toBe(0)
        ->and(ServiceArea::get()->every(fn (ServiceArea $a) => blank($a->getTranslation('intro', 'es', false))))
        ->toBeTrue();
});

it('voltar a correr o seeder nao despublica o que ja estava escrito', function () {
    $this->seed(Database\Seeders\ServiceAreaSeeder::class);

    $text = str_repeat('Texto propio escrito por la duena. ', 8);
    ServiceArea::where('name', 'Nigrán')->first()->update([
        'intro' => ['es' => $text],
        'slug' => ['es' => 'decoracion-nigran'],
        'is_published' => true,
    ]);

    $this->seed(Database\Seeders\ServiceAreaSeeder::class);

    $area = ServiceArea::where('name', 'Nigrán')->first();

    expect($area->is_published)->toBeTrue()
        ->and($area->getTranslation('slug', 'es', false))->toBe('decoracion-nigran');
});
