<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Event;
use App\Models\EventDesign;
use App\Models\Item;
use App\Models\ServiceArea;
use App\Support\Designs\DesignToQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sillas = Item::create([
        'sku' => 'SILLA-PUSH',
        'name' => ['es' => 'Silla Tiffany'],
        'slug' => ['es' => 'silla-push', 'gl' => 'silla-push-gl', 'pt' => 'silla-push-pt'],
        'stock_qty' => 100,
        'price_per_day' => 3.50,
    ]);

    $client = Client::create(['name' => 'Marta', 'email' => 'marta@example.com', 'locale' => 'es']);

    $this->event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-PUSH-1',
        'title' => 'Comunión de Uxía',
        'event_type' => 'comunion',
        'locale' => 'es',
        'starts_at' => now()->addDays(30)->setTime(13, 0),
        'ends_at' => now()->addDays(30)->setTime(20, 0),
    ]);

    $this->design = EventDesign::create([
        'event_id' => $this->event->id,
        'theme' => 'Bosque encantado',
    ]);

    $this->design->items()->create(['item_id' => $this->sillas->id, 'quantity' => 40]);
});

/*
|--------------------------------------------------------------------------
| "Pasar al presupuesto" duas vezes
|--------------------------------------------------------------------------
| O botão acrescentava sempre. Bastava juntar uma peça ao projeto e voltar a
| carregar para ficar com a lista inteira duplicada e o total a dobrar.
*/

it('nao duplica as linhas ao passar duas vezes', function () {
    $push = app(DesignToQuote::class);

    $primeiro = $push->push($this->design);
    $quote = $primeiro['quote'];

    $total = (string) $quote->total;

    $segundo = $push->push($this->design->fresh());

    expect($segundo['quote']->lines()->whereNotNull('item_id')->count())->toBe(1)
        ->and((string) $segundo['quote']->total)->toEqual($total);
});

it('reflete o que mudou no projeto', function () {
    $push = app(DesignToQuote::class);
    $push->push($this->design);

    // A Sol tira 10 cadeiras do projeto e volta a carregar no botão.
    $this->design->items()->first()->update(['quantity' => 30]);

    $resultado = $push->push($this->design->fresh());
    $linha = $resultado['quote']->lines()->whereNotNull('item_id')->first();

    expect((int) $linha->quantity)->toBe(30);
});

/*
|--------------------------------------------------------------------------
| Uma zona publicada sem endereço
|--------------------------------------------------------------------------
| O CHECK só olha para o texto, portanto deixa publicar sem slug. A listagem
| pública montava o link na mesma e partia para toda a gente, não só para a
| zona nova.
*/

it('a lista de zonas aguenta uma zona sem endereco', function () {
    // Ourense nao consta do `business.service_areas`, que aparece no JSON-LD
    // de todas as paginas — senao o nome vinha no HTML de qualquer maneira e
    // a assercao nao dizia nada.
    ServiceArea::create([
        'name' => 'Ourense',
        'slug' => [],
        'province' => 'Ourense',
        'intro' => ['es' => str_repeat('Montamos fiestas por toda la provincia desde hace años. ', 6)],
        'is_published' => true,
    ]);

    $resposta = $this->get('/es/zonas')->assertOk();

    // Nem rebenta, nem escreve um link que nao vai a lado nenhum.
    $resposta->assertDontSee('Ourense');
});

it('a zona com endereco continua a aparecer', function () {
    ServiceArea::create([
        'name' => 'Ourense',
        'slug' => ['es' => 'ourense', 'gl' => 'ourense', 'pt' => 'ourense'],
        'province' => 'Ourense',
        'intro' => ['es' => str_repeat('Montamos fiestas por toda la provincia desde hace años. ', 6)],
        'is_published' => true,
    ]);

    $this->get('/es/zonas')
        ->assertOk()
        ->assertSee('Ourense')
        ->assertSee('/es/zonas/ourense', false);
});
