<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Testimonial;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

it('mostra os dados de contacto reais da empresa', function () {
    $this->get('/es')
        ->assertSee(config('business.phone_display'))
        ->assertSee('wa.me/'.config('business.whatsapp'), false)
        ->assertSee(config('business.instagram'));
});

it('mostra os serviços do catálogo', function () {
    $this->get('/es')
        ->assertSee('Cumpleaños')
        ->assertSee('Comuniones')
        ->assertSee('Photocall');
});

it('mostra o nome da dona', function () {
    $this->get('/es')->assertSee('Sol Fernández');
});

/**
 * Estes três são o teste que interessa. Foi o erro que cometi na primeira
 * maqueta: inventar reviews, estrelas e "+300 fiestas". Se algum dia
 * alguém voltar a acrescentar isso sem dados reais, isto apanha.
 */
it('não mostra avaliações inventadas', function () {
    $html = $this->get('/es')->getContent();

    expect($html)->not->toContain('★')
        ->and($html)->not->toMatch('/\b4[,.]9\s*(\/|de)\s*5\b/')
        ->and($html)->not->toMatch('/\b\d+\s*reseñas\b/i');
});

it('esconde a secção de testemunhos enquanto não houver nenhum autorizado', function () {
    $this->get('/es')->assertDontSee(__('site.testimonials.title'));
});

it('mostra um testemunho assim que existir um real e autorizado', function () {
    Testimonial::create([
        'author_name' => 'Cliente real',
        'body' => 'Quedó todo precioso.',
        'locale' => 'es',
        'consent_at' => now(),
        'is_published' => true,
    ]);

    $this->get('/es')
        ->assertSee(__('site.testimonials.title'))
        ->assertSee('Cliente real');
});

it('não publica fotos de uma festa sem consentimento do cliente', function () {
    // A base de dados tem um CHECK a impedir isto. Aqui provamos que a
    // aplicação também não tenta contorná-lo.
    expect(fn () => Project::create([
        'title' => ['es' => 'Boda sin permiso'],
        'slug' => ['es' => 'boda-sin-permiso'],
        'event_type' => 'boda',
        'is_published' => true,
        'consent_at' => null,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});

it('não mostra preços inventados quando não há preços', function () {
    // Todos os serviços entram com base_price a 0 de propósito.
    $this->get('/es')->assertSee(__('site.services.on_request'));
});

it('serve o sitemap com as três versões de cada página', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    foreach (['es', 'gl', 'pt'] as $locale) {
        $response->assertSee(route('home', ['locale' => $locale]), false);
    }
});

it('fecha os robots fora de produção', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
});

it('manda os cabeçalhos de segurança', function () {
    $this->get('/es')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
