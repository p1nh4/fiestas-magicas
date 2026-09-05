<?php

declare(strict_types=1);

use App\Support\Period;
use Carbon\CarbonImmutable;

it('recusa um período que acaba antes de começar', function () {
    expect(fn () => Period::make('2027-05-16 20:00', '2027-05-16 12:00'))
        ->toThrow(InvalidArgumentException::class);
});

it('recusa um período de duração zero', function () {
    expect(fn () => Period::make('2027-05-16 12:00', '2027-05-16 12:00'))
        ->toThrow(InvalidArgumentException::class);
});

/**
 * Estes são os formatos reais devolvidos pelo Postgres 16, incluindo
 * microssegundos e deslocações de fuso diferentes.
 */
it('lê os formatos de tstzrange que o Postgres devolve', function (string $raw) {
    $p = Period::fromDatabase($raw);
    expect($p->end)->toBeGreaterThan($p->start);
})->with([
    '["2026-09-05 16:42:33.213448+00","2026-09-06 16:42:33.213448+00")',
    '["2027-05-16 06:00:00+00","2027-05-17 18:00:00+00")',
    '["2027-05-16 08:00:00+02","2027-05-17 20:00:00+02")',
]);

it('recusa limites que não sejam [inicio,fim)', function (string $raw) {
    expect(fn () => Period::fromDatabase($raw))->toThrow(InvalidArgumentException::class);
})->with([
    '("2027-01-01 00:00+00","2027-01-02 00:00+00")',
    '["2027-01-01 00:00+00","2027-01-02 00:00+00"]',
    'empty',
    '[2027-01-01)',
]);

it('faz a volta completa base de dados -> objeto -> base de dados', function () {
    $original = Period::make('2027-05-16 08:00:00+02', '2027-05-17 20:00:00+02');
    expect(Period::fromDatabase($original->toDatabase())->equals($original))->toBeTrue();
});

/**
 * A regra que evita conflitos falsos: uma peça devolvida às 20:00 pode
 * sair outra vez às 20:00. Intervalo meio-aberto, [inicio, fim).
 */
it('não considera sobreposição quando um período acaba onde o outro começa', function () {
    $a = Period::make('2027-05-16 08:00', '2027-05-16 20:00');
    $b = Period::make('2027-05-16 20:00', '2027-05-17 08:00');

    expect($a->overlaps($b))->toBeFalse()
        ->and($b->overlaps($a))->toBeFalse();
});

it('deteta um minuto de sobreposição', function () {
    $a = Period::make('2027-05-16 08:00', '2027-05-16 20:00');
    $b = Period::make('2027-05-16 19:59', '2027-05-17 08:00');

    expect($a->overlaps($b))->toBeTrue();
});

it('conta dias faturáveis arredondando para cima, com mínimo de um', function (
    string $start, string $end, int $expected
) {
    expect(Period::make($start, $end)->billableDays())->toBe($expected);
})->with([
    'duas horas contam um dia'     => ['2027-05-16 08:00', '2027-05-16 10:00', 1],
    'exatamente 24 h'              => ['2027-05-16 08:00', '2027-05-17 08:00', 1],
    'um dia e um minuto contam 2'  => ['2027-05-16 08:00', '2027-05-17 08:01', 2],
    'fim de semana'                => ['2027-05-14 08:00', '2027-05-17 08:00', 3],
]);

it('alarga o período com as folgas logísticas da peça', function () {
    $usage = Period::make('2027-05-16 12:00', '2027-05-16 20:00');
    $blocked = $usage->padded(120, 1440);

    expect($blocked->start->toDateTimeString())->toBe('2027-05-16 10:00:00')
        ->and($blocked->end->toDateTimeString())->toBe('2027-05-17 20:00:00');
});

it('sabe se contém um instante, com o fim excluído', function () {
    $p = Period::make('2027-05-16 08:00', '2027-05-16 20:00');

    expect($p->contains(CarbonImmutable::parse('2027-05-16 08:00')))->toBeTrue()
        ->and($p->contains(CarbonImmutable::parse('2027-05-16 19:59')))->toBeTrue()
        ->and($p->contains(CarbonImmutable::parse('2027-05-16 20:00')))->toBeFalse();
});
