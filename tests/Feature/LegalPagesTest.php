<?php

declare(strict_types=1);

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publishAll(): void
{
    Page::query()->update(['is_published' => true]);
}

it('semeia as tres paginas legais nos tres idiomas', function () {
    $this->seed(Database\Seeders\LegalPagesSeeder::class);

    foreach ([Page::LEGAL, Page::PRIVACY, Page::COOKIES] as $key) {
        $page = Page::where('key', $key)->first();

        expect($page)->not->toBeNull("falta a página {$key}");

        foreach (['es', 'gl', 'pt'] as $locale) {
            expect(trim((string) $page->getTranslation('body', $locale, false)))
                ->not->toBeEmpty("a página {$key} está vazia em {$locale}");
            expect(trim((string) $page->getTranslation('slug', $locale, false)))
                ->not->toBeEmpty("a página {$key} não tem endereço em {$locale}");
        }
    }
});

it('nascem todas despublicadas', function () {
    $this->seed(Database\Seeders\LegalPagesSeeder::class);

    expect(Page::where('is_published', true)->count())->toBe(0);
});

/*
| O que só a Sol pode preencher fica marcado, não inventado.
|
| Um aviso legal com um NIF plausível mas falso é muito pior do que um com
| um buraco visível: o buraco vê-se e corrige-se; o número inventado
| publica-se e fica lá anos. Por isso os textos trazem PENDIENTE, e por isso
| as páginas nascem despublicadas.
|
| Quando ela tiver preenchido tudo, este teste passa a falhar — e isso é o
| sinal para o apagar.
*/
it('deixa marcado o que so a empresa pode preencher', function () {
    $this->seed(Database\Seeders\LegalPagesSeeder::class);

    $legal = Page::where('key', Page::LEGAL)->first();
    $privacy = Page::where('key', Page::PRIVACY)->first();

    expect($legal->getTranslation('body', 'es', false))->toContain('PENDIENTE')
        ->and($privacy->getTranslation('body', 'es', false))->toContain('PENDIENTE');
});

it('abre uma pagina publicada nos tres idiomas', function () {
    $this->seed(Database\Seeders\LegalPagesSeeder::class);
    publishAll();

    $this->get('/es/politica-de-privacidad')->assertOk()->assertSee('Política de privacidad');
    $this->get('/gl/politica-de-privacidade')->assertOk();
    $this->get('/pt/politica-de-privacidade')->assertOk();
});

it('devolve 404 numa pagina por publicar', function () {
    $this->seed(Database\Seeders\LegalPagesSeeder::class);

    $this->get('/es/politica-de-privacidad')->assertNotFound();
});

/*
| A rota das páginas apanha tudo o que sobra dentro do idioma. Registada
| antes das outras, engolia /zonas e /alquiler — e ninguém dava por isso até
| alguém reparar que o catálogo tinha desaparecido.
*/
it('nao engole as seccoes que ja existiam', function () {
    $this->seed(Database\Seeders\LegalPagesSeeder::class);
    publishAll();

    $this->get('/es/zonas')->assertOk();
    $this->get('/es/alquiler')->assertOk();
    $this->get('/es')->assertOk();
});

it('devolve 404 num endereco inventado', function () {
    $this->get('/es/nao-existe-nada-aqui')->assertNotFound();
});

// ------------------------------------------------------------------ ligações

/*
| O problema que originou tudo isto: a caixa de consentimento do RGPD
| apontava para href="#". Pedir consentimento apontando para o vazio é pior
| do que não pedir.
*/
it('o formulario nao tem ligacoes mortas quando nao ha politica', function () {
    $html = $this->get('/es')->assertOk()->getContent();

    expect($html)->not->toContain('href="#"');
});

it('o formulario liga a politica assim que ela e publicada', function () {
    $this->seed(Database\Seeders\LegalPagesSeeder::class);
    publishAll();

    $this->get('/es')
        ->assertOk()
        ->assertSee('/es/politica-de-privacidad', escape: false);
});

it('o rodape so liga o que existe', function () {
    // Sem páginas: os rótulos aparecem, mas sem ligação.
    $html = $this->get('/es')->assertOk()->getContent();
    expect($html)->not->toContain('/es/aviso-legal');

    $this->seed(Database\Seeders\LegalPagesSeeder::class);
    publishAll();

    $this->get('/es')->assertSee('/es/aviso-legal', escape: false);
});

/*
| A política de cookies diz que o site não usa analítica nem publicidade.
| Isso tem de continuar a ser verdade — se alguém acrescentar o Google
| Analytics e esquecer a política, a página passa a mentir.
*/
it('o site nao carrega analitica de terceiros', function () {
    $html = $this->get('/es')->assertOk()->getContent();

    expect($html)
        ->not->toContain('googletagmanager.com')
        ->not->toContain('google-analytics.com')
        ->not->toContain('connect.facebook.net')
        ->not->toContain('fonts.googleapis.com');
});
