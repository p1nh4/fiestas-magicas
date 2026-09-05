<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
 * Os testes nao dependem de assets compilados.
 *
 * Sem isto, o @vite do layout procura o manifesto do build; se ninguem
 * correu "npm run build" nem tem o "npm run dev" ligado, a pagina rebenta
 * e os testes passam a inspecionar o ecra de erro do Laravel em vez do
 * site. Foi exatamente o que aconteceu: quatro testes a falhar com
 * <html lang="en"> — o ecra de erro — e um deles a queixar-se de um "★"
 * que vinha do proprio ecra de erro, nao do site.
 *
 * withoutVite() troca as tags por vazio. O que se esta a testar aqui e o
 * HTML que o Blade produz, nao o pipeline do Vite.
 */
pest()->beforeEach(function () {
    $this->withoutVite();
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
