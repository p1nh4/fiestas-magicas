<?php

declare(strict_types=1);

namespace App\Support\Events;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A referência que aparece no orçamento e na fatura: FM-2026-0031.
 *
 * Está sozinha num ficheiro porque é usada em dois sítios (ao converter um
 * pedido e ao criar um evento à mão) e porque tem uma armadilha: ler o
 * máximo e escrever a seguir NÃO é atómico. Duas pessoas a guardar ao mesmo
 * tempo davam a mesma referência e o UNIQUE da base de dados rebentava.
 *
 * O advisory lock resolve-o serializando só esta contagem, durante
 * microssegundos. É a mesma técnica do trigger das reservas.
 */
final class EventReference
{
    /** Constante arbitrária; só interessa que seja sempre a mesma. */
    private const LOCK = 20260101;

    /**
     * Tem de ser chamado DENTRO de uma transação: `pg_advisory_xact_lock`
     * larga o lock no commit, e sem transação larga-o de imediato — o que
     * seria o mesmo que não o ter.
     */
    public function next(?Carbon $when = null): string
    {
        $prefix = (string) config('business.event.reference_prefix', 'FM');
        $year = ($when ?? now())->year;

        DB::statement('SELECT pg_advisory_xact_lock(?)', [self::LOCK]);

        // Conta também os apagados: reaproveitar a referência de um evento
        // cancelado é a forma mais rápida de confundir duas festas.
        $used = Event::withTrashed()
            ->where('reference', 'like', "{$prefix}-{$year}-%")
            ->count();

        return sprintf('%s-%d-%04d', $prefix, $year, $used + 1);
    }
}
