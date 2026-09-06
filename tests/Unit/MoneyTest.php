<?php

declare(strict_types=1);

use App\Support\Money;

/*
|--------------------------------------------------------------------------
| Dinheiro
|--------------------------------------------------------------------------
| O bcmath não arredonda: trunca. Estes números são os que denunciam isso —
| e são os mesmos que apareciam nos orçamentos, sempre um cêntimo a menos.
*/

it('arredonda em vez de truncar', function (string $valor, string $esperado) {
    expect(Money::round($valor))->toBe($esperado);
})->with([
    ['6.9993', '7.00'],
    ['6.9900', '6.99'],
    ['6.995', '7.00'],
    ['4.995', '5.00'],
    ['0.004', '0.00'],
    ['0.005', '0.01'],
]);

it('arredonda os negativos para longe de zero', function () {
    expect(Money::round('-6.9993'))->toBe('-7.00')
        ->and(Money::round('-0.005'))->toBe('-0.01');
});

it('calcula o IVA certo sobre uma base que trunca mal', function () {
    // 21 % de 33,33 € são 6,9993. Truncado dava 6,99, e o IVA declarado
    // deixava de bater com 21 % da base.
    expect(Money::percent('33.33', '21'))->toBe('7.00');
});

it('multiplica quantidades fracionarias sem perder o centimo', function () {
    // A coluna quantity é numeric(8,2): 1,50 × 3,33 € = 4,995.
    expect(Money::mul('1.50', '3.33'))->toBe('5.00');
});

it('o sinal de 30 por cento sai certo', function () {
    expect(Money::percent('40.33', '30'))->toBe('12.10');
});
