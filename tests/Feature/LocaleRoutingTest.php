<?php

declare(strict_types=1);

use App\Support\Locales;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

it('abre a home nos três idiomas', function (string $locale) {
    $this->get("/{$locale}")->assertOk();
})->with(Locales::SUPPORTED);

it('recusa um idioma que não existe', function () {
    $this->get('/fr')->assertNotFound();
});

it('manda a raiz para o idioma do navegador', function (string $header, string $expected) {
    $this->withHeaders(['Accept-Language' => $header])
        ->get('/')
        ->assertRedirect("/{$expected}");
})->with([
    'português'    => ['pt-PT,pt;q=0.9', 'pt'],
    'galego'       => ['gl-ES,gl;q=0.9', 'gl'],
    'espanhol'     => ['es-ES,es;q=0.9', 'es'],
    'inglês cai no espanhol' => ['en-GB,en;q=0.9', 'es'],
]);

it('traduz mesmo o conteúdo, não só o prefixo', function () {
    // Se estas três dessem o mesmo texto, o i18n estaria a fingir.
    $es = $this->get('/es')->getContent();
    $gl = $this->get('/gl')->getContent();
    $pt = $this->get('/pt')->getContent();

    expect($es)->toContain('Cuéntanos cómo lo')
        ->and($gl)->toContain('Cóntanos como o')
        ->and($pt)->toContain('Conta-nos como a');
});

it('declara as três versões com hreflang', function () {
    $response = $this->get('/es');

    foreach (Locales::SUPPORTED as $locale) {
        $response->assertSee('hreflang="'.Locales::hreflang($locale).'"', false);
    }

    $response->assertSee('hreflang="x-default"', false);
});

it('marca a página como canónica para não competir consigo própria', function () {
    $this->get('/gl')->assertSee('rel="canonical"', false);
});

it('diz ao navegador em que idioma está a responder', function () {
    $this->get('/pt')->assertHeader('Content-Language', 'pt-PT');
});

it('mantém a pessoa na mesma página ao trocar de idioma', function () {
    $this->get('/es')->assertSee(route('home', ['locale' => 'gl']), false);
});
