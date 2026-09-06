<?php

declare(strict_types=1);

use App\Enums\QuoteStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventDesign;
use App\Models\Item;
use App\Models\Quote;
use App\Models\Reservation;
use App\Support\Designs\DesignToQuote;
use App\Support\Quotes\QuoteBuilder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sillas = Item::create([
        'sku' => 'SILLA',
        'name' => ['es' => 'Silla Tiffany'],
        'slug' => ['es' => 'silla-tiffany'],
        'stock_qty' => 40,
        'price_per_day' => 3.50,
    ]);

    $this->photocall = Item::create([
        'sku' => 'PHOTOCALL',
        'name' => ['es' => 'Photocall floral'],
        'slug' => ['es' => 'photocall-floral'],
        'stock_qty' => 1,
        'price_per_day' => 75.00,
    ]);

    $client = Client::create(['name' => 'Marta', 'email' => 'marta@example.com', 'locale' => 'es']);

    $this->event = Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-DIS-1',
        'title' => 'Comunión de Uxía',
        'event_type' => 'comunion',
        'locale' => 'es',
        'starts_at' => now()->addDays(30)->setTime(13, 0),
        'ends_at' => now()->addDays(30)->setTime(20, 0),
    ]);

    $this->design = EventDesign::create([
        'event_id' => $this->event->id,
        'theme' => 'Bosque encantado',
        'palette' => ['#c96f86', '#a8823c'],
    ]);
});

function comMaterial(EventDesign $design, Item $item, int $quantity, ?string $notes = null): void
{
    $design->items()->create([
        'item_id' => $item->id,
        'quantity' => $quantity,
        'notes' => $notes,
    ]);
}

// ------------------------------------------------------------------- regras

it('so deixa um projeto por festa', function () {
    expect(fn () => EventDesign::create(['event_id' => $this->event->id, 'theme' => 'Otro']))
        ->toThrow(QueryException::class);
});

it('recusa a mesma peca duas vezes no mesmo projeto', function () {
    comMaterial($this->design, $this->sillas, 40);

    expect(fn () => comMaterial($this->design, $this->sillas, 10))
        ->toThrow(QueryException::class);
});

/*
| O projeto é uma INTENÇÃO, não uma reserva. Listar 999 cadeiras quando só
| há 40 é um plano optimista, não um erro — e nada fica preso. Se isto um
| dia falhar, alguém ligou o projeto ao stock e passou a bloquear material
| por causa de ideias que ainda podem mudar.
*/
it('nao reserva nada nem esta limitado pelo stock', function () {
    comMaterial($this->design, $this->sillas, 999);

    expect($this->design->items()->count())->toBe(1)
        ->and(Reservation::count())->toBe(0);
});

// ------------------------------------------------------ passar ao orçamento

it('passa o material para um orcamento novo', function () {
    comMaterial($this->design, $this->sillas, 40);
    comMaterial($this->design, $this->photocall, 1);

    $result = app(DesignToQuote::class)->push($this->design);

    expect($result['added'])->toBe(2)
        ->and($result['quote']->version)->toBe(1)
        ->and($result['quote']->lines)->toHaveCount(2)
        // O preço vem do catálogo e fica congelado na linha.
        ->and((float) $result['quote']->total)->toBeGreaterThan(0);
});

it('usa a nota do projeto como descricao quando existe', function () {
    comMaterial($this->design, $this->sillas, 40, 'Sillas con lazo a juego');

    $quote = app(DesignToQuote::class)->push($this->design)['quote'];

    expect($quote->lines->first()->description)->toBe('Sillas con lazo a juego');
});

it('escreve no rascunho que ja existe em vez de criar outro', function () {
    comMaterial($this->design, $this->sillas, 40);

    $existing = app(QuoteBuilder::class)->draftFor($this->event);

    $result = app(DesignToQuote::class)->push($this->design);

    expect($result['quote']->id)->toBe($existing->id)
        ->and(Quote::count())->toBe(1);
});

/*
| A regra que atravessa o projeto inteiro: um orçamento enviado não se
| edita. Se o último já saiu, isto tem de criar a versão seguinte — nunca
| escrever por cima da prova do que a cliente viu.
*/
it('cria a versao seguinte quando o ultimo orcamento ja foi enviado', function () {
    comMaterial($this->design, $this->sillas, 40);

    $sent = app(QuoteBuilder::class)->draftFor($this->event);
    app(QuoteBuilder::class)->markSent($sent);

    $result = app(DesignToQuote::class)->push($this->design);

    expect($result['quote']->id)->not->toBe($sent->id)
        ->and($result['quote']->version)->toBe(2)
        ->and($sent->fresh()->status)->toBe(QuoteStatus::Sent);
});

it('cobra os dias que a festa dura, nao as folgas de transporte', function () {
    comMaterial($this->design, $this->sillas, 40);

    $quote = app(DesignToQuote::class)->push($this->design)['quote'];

    // A festa é de um dia; as folgas de montagem não se faturam.
    expect($quote->lines->first()->days)->toBe(1);
});

// ---------------------------------------------------------- disponibilidade

it('nao avisa nada quando ha material a mais que suficiente', function () {
    comMaterial($this->design, $this->sillas, 10);

    expect(app(DesignToQuote::class)->checkAvailability($this->design))->toBeEmpty();
});

it('avisa quando o material previsto nao chega', function () {
    comMaterial($this->design, $this->sillas, 100);

    $warnings = app(DesignToQuote::class)->checkAvailability($this->design);

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('Silla Tiffany')
        ->and($warnings[0])->toContain('100');
});

/*
| Avisar mas não impedir. Faltarem cadeiras é informação útil ao fazer o
| orçamento; recusar escrever a linha seria decidir pela Sol, que pode muito
| bem alugar as que faltam a um colega. Quem decide mesmo é o trigger, e só
| quando a cliente aceitar.
*/
it('avisa mas deixa passar ao orcamento na mesma', function () {
    comMaterial($this->design, $this->sillas, 100);

    $result = app(DesignToQuote::class)->push($this->design);

    expect($result['added'])->toBe(1)
        ->and($result['warnings'])->not->toBeEmpty();
});

// ----------------------------------------------------------------- montagem

it('conta os passos da montagem que ja estao feitos', function () {
    $this->design->update(['checklist' => [
        ['step' => 'Cargar la furgoneta', 'done' => true],
        ['step' => 'Montar el photocall', 'done' => false],
        ['step' => 'Recoger', 'done' => false],
    ]]);

    expect($this->design->checklistTotal())->toBe(3)
        ->and($this->design->checklistDone())->toBe(1);
});

// ---------------------------------------------------------------- demo

it('o comando de demonstracao cria e apaga os seus proprios dados', function () {
    $this->artisan('fiestas:demo')->assertSuccessful();

    expect(Event::where('reference', 'FM-DEMO-0001')->exists())->toBeTrue()
        ->and(EventDesign::whereHas('event', fn ($q) => $q->where('reference', 'FM-DEMO-0001'))->exists())->toBeTrue();

    // Correr duas vezes não duplica nada.
    $this->artisan('fiestas:demo')->assertSuccessful();
    expect(Event::where('reference', 'FM-DEMO-0001')->count())->toBe(1);

    $this->artisan('fiestas:demo --limpiar')->assertSuccessful();
    expect(Event::withTrashed()->where('reference', 'FM-DEMO-0001')->exists())->toBeFalse();
});
