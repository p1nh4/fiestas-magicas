<?php

declare(strict_types=1);

use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Até quando vale um orçamento
|--------------------------------------------------------------------------
| O `valid_until` é uma DATA. Um orçamento válido até 20 de setembro vale no
| dia 20 inteiro — é assim que o `quotes:expire` o trata, e havia um teste a
| fixá-lo. O `isExpired()` do modelo dizia o contrário desde a meia-noite
| desse dia, e quem via a diferença era a cliente: a Sol dizia-lhe que ainda
| dava e o link já mostrava "caducado".
*/

it('ainda vale no proprio dia da validade', function () {
    $quote = new Quote(['valid_until' => now()->toDateString()]);

    expect($quote->isExpired())->toBeFalse();
});

it('caduca no dia seguinte', function () {
    $quote = new Quote(['valid_until' => now()->subDay()->toDateString()]);

    expect($quote->isExpired())->toBeTrue();
});

it('sem data de validade nunca caduca', function () {
    expect((new Quote(['valid_until' => null]))->isExpired())->toBeFalse();
});
