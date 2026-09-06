<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Arredondar dinheiro sem passar por vírgula flutuante.
 *
 * O `bcmath` não arredonda: TRUNCA. `bcdiv('699.9300', '100', 2)` dá 6.99 e
 * não 7.00, e isso não é um pormenor académico — é o IVA de uma linha a não
 * bater com 21 % da base, em cada linha e em cada orçamento, sempre para
 * baixo. Numa festa com dez linhas são dez cêntimos que ninguém consegue
 * explicar à cliente.
 *
 * A técnica é a de sempre: somar meia unidade da última casa antes de
 * truncar. Feita em bcmath, portanto sem float em passo nenhum.
 */
final class Money
{
    /** Arredonda meia-unidade para cima (para longe de zero nos negativos). */
    public static function round(string $value, int $scale = 2): string
    {
        $half = '0.'.str_repeat('0', $scale).'5';

        if (bccomp($value, '0', $scale + 4) < 0) {
            return bcsub($value, $half, $scale);
        }

        return bcadd($value, $half, $scale);
    }

    /** Multiplica e arredonda. */
    public static function mul(string $a, string $b, int $scale = 2): string
    {
        return self::round(bcmul($a, $b, $scale + 4), $scale);
    }

    /** Divide e arredonda. */
    public static function div(string $a, string $b, int $scale = 2): string
    {
        return self::round(bcdiv($a, $b, $scale + 4), $scale);
    }

    /** Uma percentagem de um valor: 21 % de 33,33 dá 7,00, não 6,99. */
    public static function percent(string $value, string $pct, int $scale = 2): string
    {
        return self::div(bcmul($value, $pct, $scale + 4), '100', $scale);
    }
}
