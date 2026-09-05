<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Identificador publico separado da chave interna.
 *
 * O id sequencial fica em casa; nas URLs e nas APIs vai o uuid. Assim
 * ninguem consegue contar quantos clientes ou quantas festas ha so por
 * olhar para um link (enumeration attack), e a chave interna continua a
 * ser um bigint, que e o que indexa bem.
 */
trait HasPublicUuid
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
