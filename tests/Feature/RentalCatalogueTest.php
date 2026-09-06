<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Event;
use App\Models\Item;
use App\Support\Availability\AvailabilityService;
use App\Support\Period;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->chairs = Item::create([
        'sku' => 'SILLA',
        'name' => ['es' => 'Silla Tiffany', 'gl' => 'Cadeira Tiffany', 'pt' => 'Cadeira Tiffany'],
        'slug' => ['es' => 'silla-tiffany', 'gl' => 'cadeira-tiffany', 'pt' => 'cadeira-tiffany'],
        'stock_qty' => 40,
        'price_per_day' => 3.50,
        'buffer_before_min' => 120,
        'buffer_after_min' => 1440,
        'is_rentable' => true,
        'is_active' => true,
    ]);
});

function occupy(Item $item, int $quantity, CarbonImmutable $from, CarbonImmutable $to): void
{
    $client = Client::create(['name' => 'Ocupa', 'email' => 'ocupa@example.com', 'locale' => 'es']);

    $event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-OCUPA-'.random_int(1000, 9999),
        'title' => 'Festa que ocupa',
        'event_type' => 'boda',
        'locale' => 'es',
        'starts_at' => $from,
        'ends_at' => $to,
    ]);

    app(AvailabilityService::class)->reserve(
        item: $item,
        window: $item->blockedWindowFor(new Period($from, $to)),
        quantity: $quantity,
        event: $event,
    );
}

// ------------------------------------------------------------------ listagem

it('lista o material alugavel', function () {
    $this->get('/es/alquiler')
        ->assertOk()
        ->assertSee('Silla Tiffany')
        ->assertSee('/es/alquiler/silla-tiffany', escape: false);
});

it('nao lista pecas que nao se alugam a solto', function () {
    Item::create([
        'sku' => 'INTERNO',
        'name' => ['es' => 'Escalera de montaje'],
        'slug' => ['es' => 'escalera'],
        'stock_qty' => 2,
        'is_rentable' => false,
        'is_active' => true,
    ]);

    $response = $this->get('/es/alquiler');

    $response->assertOk()->assertDontSee('Escalera de montaje');
    expect($response->getContent())->not->toContain('/es/alquiler/escalera');
});

it('responde no endereco de cada idioma', function () {
    $this->get('/es/alquiler/silla-tiffany')->assertOk();
    $this->get('/gl/alquiler/cadeira-tiffany')->assertOk();
    $this->get('/pt/alquiler/cadeira-tiffany')->assertOk();

    $this->get('/gl/alquiler/silla-tiffany')->assertNotFound();
});

it('devolve 404 numa peca que nao se aluga', function () {
    Item::create([
        'sku' => 'INTERNO',
        'name' => ['es' => 'Escalera'],
        'slug' => ['es' => 'escalera'],
        'stock_qty' => 2,
        'is_rentable' => false,
    ]);

    $this->get('/es/alquiler/escalera')->assertNotFound();
});

// ------------------------------------------------------------ disponibilidade

it('sem datas nao mostra resultado nenhum', function () {
    $this->get('/es/alquiler/silla-tiffany')
        ->assertOk()
        ->assertDontSee(__('rentals.result.not_a_booking'));
});

it('diz quantas ficam livres', function () {
    $from = CarbonImmutable::now()->addMonth()->startOfDay();

    $this->get('/es/alquiler/silla-tiffany?desde='.$from->toDateString().'&hasta='.$from->addDay()->toDateString().'&cantidad=10')
        ->assertOk()
        ->assertSee(__('rentals.result.yes', [
            'free' => 40,
            'from' => $from->format('d/m/Y'),
            'to' => $from->addDay()->endOfDay()->format('d/m/Y'),
        ]));
});

it('desconta o que ja esta reservado', function () {
    $from = CarbonImmutable::now()->addMonth()->startOfDay()->addHours(12);
    occupy($this->chairs, 35, $from, $from->addHours(8));

    $day = $from->toDateString();

    $this->get("/es/alquiler/silla-tiffany?desde={$day}&hasta={$day}&cantidad=10")
        ->assertOk()
        ->assertSee(__('rentals.result.no_enough', ['free' => 5, 'wanted' => 10]));
});

it('diz que nao ha nenhuma quando esta tudo ocupado', function () {
    $from = CarbonImmutable::now()->addMonth()->startOfDay()->addHours(12);
    occupy($this->chairs, 40, $from, $from->addHours(8));

    $day = $from->toDateString();

    $this->get("/es/alquiler/silla-tiffany?desde={$day}&hasta={$day}&cantidad=1")
        ->assertOk()
        ->assertSee(__('rentals.result.none'));
});

/*
| A frase que evita um mal-entendido caro: consultar não reserva. Quem
| reserva é o orçamento aceite. Se este teste falhar, alguém tirou o aviso
| da página e alguma cliente vai chegar ao sábado convencida de que tinha
| as cadeiras guardadas.
*/
it('avisa sempre que consultar nao e reservar', function () {
    $from = CarbonImmutable::now()->addMonth()->toDateString();

    $this->get("/es/alquiler/silla-tiffany?desde={$from}&hasta={$from}")
        ->assertOk()
        ->assertSee(__('rentals.result.not_a_booking'));
});

it('nao cria reservas ao consultar', function () {
    $from = CarbonImmutable::now()->addMonth()->toDateString();

    $this->get("/es/alquiler/silla-tiffany?desde={$from}&hasta={$from}&cantidad=40");

    expect(App\Models\Reservation::count())->toBe(0);
});

// -------------------------------------------------------------------- erros

it('nao rebenta com datas escritas a mao no endereco', function () {
    $this->get('/es/alquiler/silla-tiffany?desde=nao-e-uma-data&hasta=tambem-nao')
        ->assertOk()
        ->assertSee(__('rentals.errors.dates'));
});

it('recusa a volta antes da saida', function () {
    $from = CarbonImmutable::now()->addMonth();

    $this->get('/es/alquiler/silla-tiffany?desde='.$from->toDateString().'&hasta='.$from->subDays(3)->toDateString())
        ->assertOk()
        ->assertSee(__('rentals.errors.order'));
});

it('recusa uma data que ja passou', function () {
    $past = CarbonImmutable::now()->subMonth()->toDateString();
    $future = CarbonImmutable::now()->addMonth()->toDateString();

    $this->get("/es/alquiler/silla-tiffany?desde={$past}&hasta={$future}")
        ->assertOk()
        ->assertSee(__('rentals.errors.past'));
});

/*
| Sem este limite, `?desde=2020-01-01&hasta=2200-01-01` punha o cálculo de
| disponibilidade a percorrer 65 000 dias. Não é um ataque sofisticado: é
| alguém a brincar com a barra de endereços.
*/
it('recusa um intervalo absurdamente longo', function () {
    $from = CarbonImmutable::now()->addDay()->toDateString();
    $to = CarbonImmutable::now()->addYears(50)->toDateString();

    $this->get("/es/alquiler/silla-tiffany?desde={$from}&hasta={$to}")
        ->assertOk()
        ->assertSee(__('rentals.errors.too_long'));
});

// ------------------------------------------------------------------ sitemap

it('poe o catalogo de aluguer no sitemap, nas tres linguas', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain('/es/alquiler')
        ->and($xml)->toContain('/es/alquiler/silla-tiffany')
        ->and($xml)->toContain('/gl/alquiler/cadeira-tiffany')
        ->and($xml)->toContain('/pt/alquiler/cadeira-tiffany');
});

it('nao poe no sitemap pecas que nao se alugam', function () {
    Item::create([
        'sku' => 'INTERNO',
        'name' => ['es' => 'Escalera'],
        'slug' => ['es' => 'escalera'],
        'stock_qty' => 2,
        'is_rentable' => false,
    ]);

    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/alquiler/escalera');
});
