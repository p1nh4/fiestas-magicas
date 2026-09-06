<?php

declare(strict_types=1);

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CatalogSeeder::class);
});

/*
|--------------------------------------------------------------------------
| Validação inline do formulário
|--------------------------------------------------------------------------
| O JavaScript não é testado aqui — o que se testa é o contrato entre o
| Blade e o `lang/`, que é onde isto se parte de verdade: uma chave que
| existe em espanhol e falta em galego dá um formulário sem mensagens nesse
| idioma, e ninguém dá por isso até um cliente galego se queixar.
|
| Não substitui nada: o `LeadSubmissionTest` continua a ser quem prova que o
| servidor recusa o que tem de recusar, com JS ou sem ele.
*/

it('leva as mensagens de validacao nos tres idiomas', function (string $locale) {
    $html = $this->get("/{$locale}")->assertOk()->getContent();

    expect($html)->toContain('data-validacion=');

    $mensagens = [
        __('forms.errors.required', locale: $locale),
        __('forms.errors.email_format', locale: $locale),
        __('forms.errors.contact_required', locale: $locale),
    ];

    foreach ($mensagens as $mensagem) {
        expect($mensagem)->not->toStartWith('forms.');
        expect($html)->toContain(e(json_encode($mensagem, JSON_UNESCAPED_UNICODE), false));
    }
})->with(['es', 'gl', 'pt']);

it('o formulario continua a ser um POST normal', function () {
    /*
     * A garantia que paga tudo isto: sem JavaScript nenhum, o formulário
     * ainda submete. Se um dia alguém trocar isto por um componente que só
     * envia por JS, este teste cai.
     */
    $this->get('/es')
        ->assertOk()
        ->assertSee('method="POST"', false)
        ->assertSee('name="privacy"', false);
});
