<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Redirect;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Um trabalho publicado, com consentimento — que é o que a base de dados
 * exige para deixar publicar seja o que for.
 */
function publishedProject(array $overrides = []): Project
{
    return Project::create($overrides + [
        'title' => ['es' => 'Comunión en el pazo', 'gl' => 'Comuñón no pazo', 'pt' => 'Comunhão no solar'],
        'slug' => ['es' => 'comunion-pazo', 'gl' => 'comunion-pazo-gl', 'pt' => 'comunhao-solar'],
        'description' => ['es' => str_repeat('Montamos la mesa dulce y el photocall. ', 5)],
        'event_type' => 'comunion',
        'happened_on' => '2026-05-10',
        'venue' => 'Pazo de Mos',
        'city' => 'Nigrán',
        'guests_count' => 60,
        'is_published' => true,
        'published_at' => now(),
        'consent_at' => now()->subDay(),
    ]);
}

function activeService(array $overrides = []): Service
{
    return Service::create($overrides + [
        'name' => ['es' => 'Mesa dulce', 'gl' => 'Mesa doce', 'pt' => 'Mesa doce'],
        'slug' => ['es' => 'mesa-dulce', 'gl' => 'mesa-doce', 'pt' => 'mesa-doce-pt'],
        'summary' => ['es' => 'Tarta, chuches y flores a juego con la fiesta.'],
        'description' => ['es' => str_repeat('Lo montamos la mañana del evento. ', 5)],
        'is_active' => true,
    ]);
}

// -------------------------------------------------------------- a galeria

it('mostra a galeria com os trabalhos publicados', function () {
    $project = publishedProject();

    $this->get('/es/trabajos')
        ->assertOk()
        ->assertSee('Comunión en el pazo');

    expect($project->urlFor('es'))->toBe(url('/es/trabajos/comunion-pazo'));
});

/*
| A regra do consentimento é do CHECK, não do formulário — mas o site tem
| de a respeitar na mesma. Um trabalho por publicar não pode aparecer nem
| na galeria nem ter página própria.
*/
it('nao mostra trabalhos por publicar', function () {
    publishedProject([
        'title' => ['es' => 'Boda todavía en borrador'],
        'slug' => ['es' => 'boda-borrador'],
        'is_published' => false,
        'published_at' => null,
    ]);

    $this->get('/es/trabajos')->assertOk()->assertDontSee('Boda todavía en borrador');
    $this->get('/es/trabajos/boda-borrador')->assertNotFound();
});

it('nao deixa publicar um trabalho sem o consentimento do cliente', function () {
    expect(fn () => Project::create([
        'title' => ['es' => 'Sin permiso'],
        'slug' => ['es' => 'sin-permiso'],
        'event_type' => 'boda',
        'is_published' => true,
        'published_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('filtra por tipo de festa', function () {
    publishedProject();
    publishedProject([
        'title' => ['es' => 'Boda en Baiona'],
        'slug' => ['es' => 'boda-baiona'],
        'event_type' => 'boda',
    ]);

    $this->get('/es/trabajos?tipo=boda')
        ->assertOk()
        ->assertSee('Boda en Baiona')
        ->assertDontSee('Comunión en el pazo');

    // Um tipo inventado na barra de endereços mostra tudo, em vez de dar
    // erro ou uma lista vazia sem explicação.
    $this->get('/es/trabajos?tipo=inventado')
        ->assertOk()
        ->assertSee('Boda en Baiona')
        ->assertSee('Comunión en el pazo');
});

// ------------------------------------------------------- a página do trabalho

it('abre a pagina de um trabalho em cada idioma', function () {
    publishedProject();

    $this->get('/es/trabajos/comunion-pazo')->assertOk()->assertSee('Pazo de Mos');
    $this->get('/pt/trabajos/comunhao-solar')->assertOk()->assertSee('Comunhão no solar');
});

/*
| O endereço de um idioma não abre noutro.
|
| Sem isto, a mesma festa responderia em três endereços por idioma e o
| Google escolheria um deles à sorte — que é a definição de conteúdo
| duplicado a competir consigo próprio.
*/
it('nao serve o endereco de um idioma noutro idioma', function () {
    publishedProject();

    $this->get('/pt/trabajos/comunion-pazo')->assertNotFound();
});

/*
| O hreflang tem de apontar para o endereço REAL do outro idioma.
|
| A versão anterior trocava só o prefixo — /pt/trabajos/comunion-pazo — e
| um hreflang que aponta para um 404 faz o Google descartar o grupo
| inteiro de anotações, incluindo as que estavam certas.
*/
it('aponta o hreflang para o endereco certo de cada idioma', function () {
    publishedProject();

    $this->get('/es/trabajos/comunion-pazo')
        ->assertOk()
        ->assertSee('hreflang="pt-PT" href="'.url('/pt/trabajos/comunhao-solar').'"', escape: false)
        ->assertDontSee(url('/pt/trabajos/comunion-pazo'));
});

it('mantem o endereco antigo a funcionar depois de mudar o slug', function () {
    $project = publishedProject();

    $project->update(['slug' => ['es' => 'comunion-en-el-pazo', 'gl' => 'comunion-pazo-gl', 'pt' => 'comunhao-solar']]);

    expect(Redirect::where('from_path', '/es/trabajos/comunion-pazo')->exists())->toBeTrue();

    $this->get('/es/trabajos/comunion-pazo')
        ->assertRedirect('/es/trabajos/comunion-en-el-pazo');
});

// -------------------------------------------------------------- serviços

it('abre a pagina de um servico', function () {
    activeService();

    $this->get('/es/servicios/mesa-dulce')
        ->assertOk()
        ->assertSee('Mesa dulce')
        ->assertSee('Tarta, chuches y flores a juego con la fiesta.');
});

it('nao mostra a pagina de um servico desativado', function () {
    activeService(['is_active' => false]);

    $this->get('/es/servicios/mesa-dulce')->assertNotFound();
});

/*
| Preço a zero é "a consultar", nunca "0 €".
|
| A regra atravessa o site inteiro e existe porque um preço publicado é
| uma promessa: mais vale não dizer nada do que dizer o número errado.
*/
it('mostra o preco a consultar quando nao ha preco', function () {
    activeService(['base_price' => 0]);

    $this->get('/es/servicios/mesa-dulce')
        ->assertOk()
        ->assertSee(__('services.show.on_request'));
});

// -------------------------------------------------------------- navegação

/*
| A navegação era feita de âncoras, e uma âncora só funciona na portada.
| Este teste existe para a próxima pessoa não voltar a pôr lá um "#".
*/
it('a navegacao leva a paginas de verdade e nao a ancoras soltas', function () {
    publishedProject();

    $this->get('/es/trabajos/comunion-pazo')
        ->assertOk()
        ->assertSee(url('/es/trabajos'))
        ->assertSee(url('/es/alquiler'))
        ->assertSee(url('/es/zonas'))
        ->assertDontSee('href="#alquiler"', escape: false);
});

it('poe os trabalhos e os servicos no sitemap', function () {
    publishedProject();
    activeService();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(url('/es/trabajos'))
        ->assertSee(url('/pt/trabajos/comunhao-solar'))
        ->assertSee(url('/gl/servicios/mesa-doce'));
});

/*
| A portada mandava a pessoa para o Instagram no "ver todos" e para
| #trabajos nos quadrados — ou seja, para fora do site e para lado nenhum.
*/
it('a portada liga os trabalhos e os servicos as suas paginas', function () {
    publishedProject();
    activeService();

    $this->get('/es')
        ->assertOk()
        ->assertSee(url('/es/trabajos/comunion-pazo'))
        ->assertSee(url('/es/servicios/mesa-dulce'));
});
