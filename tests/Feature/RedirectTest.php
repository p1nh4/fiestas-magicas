<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function anArea(array $slug = ['es' => 'nigran', 'gl' => 'nigran', 'pt' => 'nigran']): ServiceArea
{
    return ServiceArea::create([
        'name' => 'Nigrán',
        'slug' => $slug,
        'country' => 'ES',
        'intro' => array_fill_keys(['es', 'gl', 'pt'], str_repeat('Texto propio de la zona. ', 12)),
        'is_published' => true,
    ]);
}

// ------------------------------------------------------------ criação automática

it('guarda o endereco antigo quando um slug muda', function () {
    $area = anArea();

    $area->update(['slug' => ['es' => 'decoracion-nigran', 'gl' => 'nigran', 'pt' => 'nigran']]);

    $redirect = Redirect::resolve('/es/zonas/nigran');

    expect($redirect)->not->toBeNull()
        ->and($redirect->to_path)->toBe('/es/zonas/decoracion-nigran')
        ->and($redirect->status_code)->toBe(301);
});

it('guarda um endereco por idioma alterado', function () {
    $area = anArea();

    $area->update(['slug' => ['es' => 'nigran-es', 'gl' => 'nigran-gl', 'pt' => 'nigran']]);

    expect(Redirect::resolve('/es/zonas/nigran'))->not->toBeNull()
        ->and(Redirect::resolve('/gl/zonas/nigran'))->not->toBeNull()
        // O português não mudou: não há nada para redirecionar.
        ->and(Redirect::resolve('/pt/zonas/nigran'))->toBeNull();
});

it('nao guarda nada quando o slug fica igual', function () {
    $area = anArea();

    $area->update(['intro' => array_fill_keys(['es', 'gl', 'pt'], str_repeat('Outro texto proprio da zona. ', 12))]);

    expect(Redirect::count())->toBe(0);
});

it('funciona tambem no material de aluguer', function () {
    $item = Item::create([
        'sku' => 'PHOTOCALL',
        'name' => ['es' => 'Photocall floral'],
        'slug' => ['es' => 'photocall-floral', 'gl' => 'photocall-floral', 'pt' => 'photocall-floral'],
        'stock_qty' => 1,
        'is_rentable' => true,
        'is_active' => true,
    ]);

    $item->update(['slug' => ['es' => 'photocall-de-flores', 'gl' => 'photocall-floral', 'pt' => 'photocall-floral']]);

    expect(Redirect::resolve('/es/alquiler/photocall-floral')?->to_path)
        ->toBe('/es/alquiler/photocall-de-flores');
});

it('funciona tambem nas paginas de texto', function () {
    $page = Page::create([
        'key' => 'privacidad',
        'title' => ['es' => 'Privacidad'],
        'slug' => ['es' => 'privacidad'],
        'body' => ['es' => 'Texto.'],
        'is_published' => true,
    ]);

    $page->update(['slug' => ['es' => 'politica-de-privacidad']]);

    expect(Redirect::resolve('/es/privacidad')?->to_path)->toBe('/es/politica-de-privacidad');
});

// ------------------------------------------------------------------ a cadeia

/*
| Se um endereço mudar duas vezes (A → B → C), a solução ingénua deixa /A a
| apontar para /B e /B para /C. São dois saltos: o navegador faz duas
| viagens e o Google conta como sinal perdido a cada uma. Aqui a cadeia é
| encurtada: /A passa a apontar direto para /C.
*/
it('encurta a cadeia quando um endereco muda duas vezes', function () {
    $area = anArea();

    $area->update(['slug' => ['es' => 'b', 'gl' => 'nigran', 'pt' => 'nigran']]);
    $area->update(['slug' => ['es' => 'c', 'gl' => 'nigran', 'pt' => 'nigran']]);

    expect(Redirect::resolve('/es/zonas/nigran')?->to_path)->toBe('/es/zonas/c')
        ->and(Redirect::resolve('/es/zonas/b')?->to_path)->toBe('/es/zonas/c');
});

/*
| E se voltar atrás? O endereço original está vivo outra vez, portanto o
| redirecionamento que apontava para fora dele tem de desaparecer — senão
| ficava a redirecionar uma página que existe, e nunca mais se via.
*/
it('apaga o redirecionamento quando o endereco antigo volta a ser usado', function () {
    $area = anArea();

    $area->update(['slug' => ['es' => 'outro', 'gl' => 'nigran', 'pt' => 'nigran']]);
    expect(Redirect::resolve('/es/zonas/nigran'))->not->toBeNull();

    $area->update(['slug' => ['es' => 'nigran', 'gl' => 'nigran', 'pt' => 'nigran']]);

    expect(Redirect::resolve('/es/zonas/nigran'))->toBeNull()
        ->and(Redirect::resolve('/es/zonas/outro')?->to_path)->toBe('/es/zonas/nigran');
});

it('nunca cria um redirecionamento para si proprio', function () {
    expect(Redirect::remember('/es/zonas/nigran', '/es/zonas/nigran'))->toBeNull()
        ->and(Redirect::count())->toBe(0);
});

// -------------------------------------------------------------- o middleware

it('leva a pessoa do endereco antigo para o novo', function () {
    $area = anArea();
    $area->update(['slug' => ['es' => 'decoracion-nigran', 'gl' => 'nigran', 'pt' => 'nigran']]);

    $this->get('/es/zonas/nigran')
        ->assertStatus(301)
        ->assertRedirect('/es/zonas/decoracion-nigran');

    // E o endereço novo continua a responder normalmente.
    $this->get('/es/zonas/decoracion-nigran')->assertOk();
});

it('conta quantas pessoas ainda chegam pelo endereco velho', function () {
    $area = anArea();
    $area->update(['slug' => ['es' => 'novo', 'gl' => 'nigran', 'pt' => 'nigran']]);

    $this->get('/es/zonas/nigran');
    $this->get('/es/zonas/nigran');

    expect(Redirect::resolve('/es/zonas/nigran')->hits)->toBe(2);
});

it('continua a dar 404 num endereco que nunca existiu', function () {
    $this->get('/es/zonas/nunca-existiu')->assertNotFound();
});

/*
| O middleware corre DEPOIS da resposta e só quando ela é 404. Assim uma
| visita normal não paga uma consulta à base de dados — e são as visitas
| normais que são quase todas.
*/
it('nao interfere com uma pagina que responde bem', function () {
    anArea();

    $this->get('/es/zonas/nigran')->assertOk();
    $this->get('/es')->assertOk();
});
