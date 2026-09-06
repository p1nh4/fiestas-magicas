<?php

declare(strict_types=1);

namespace App\Support\Monitoring;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * O sinal de vida do agendador.
 *
 * Se o `schedule:run` deixar de ser chamado, nada estoira: as reservas de
 * carrinho não voltam ao stock, os orçamentos ficam eternamente "enviados",
 * o lembrete da festa não sai e os pagamentos por confirmar ficam por
 * confirmar. Tudo em silêncio, que é a pior maneira de falhar — a Sol só
 * dá por ela quando uma cliente liga a perguntar.
 *
 * Um serviço de fora a bater no `/up` não apanha isto sozinho: o site
 * responde na mesma. Por isso o sinal do agendador entra na verificação do
 * `/up`, e o alarme que já existe passa a cobrir também o cron.
 */
final class SchedulerHeartbeat
{
    /**
     * Marca que o agendador correu agora.
     *
     * O prazo da entrada é muito maior do que a tolerância de propósito: se
     * a marca desaparecesse sozinha, o `/up` deixava de distinguir "o
     * agendador morreu" de "a cache esvaziou-se", e são coisas diferentes.
     */
    public function beat(): void
    {
        Cache::put(
            $this->key(),
            CarbonImmutable::now()->toIso8601String(),
            CarbonImmutable::now()->addDay(),
        );
    }

    /**
     * Quando é que o agendador deu sinal pela última vez.
     */
    public function lastBeat(): ?CarbonImmutable
    {
        $marca = Cache::get($this->key());

        if (! is_string($marca) || $marca === '') {
            return null;
        }

        return CarbonImmutable::parse($marca);
    }

    /**
     * Deu sinal há pouco tempo?
     *
     * Sem marca nenhuma a resposta é não, e não "ainda não sei". Um
     * agendador que nunca arrancou é o caso mais provável logo a seguir a
     * um deploy, e é precisamente o que não se pode deixar passar em
     * silêncio.
     */
    public function isAlive(): bool
    {
        $ultimo = $this->lastBeat();

        if ($ultimo === null) {
            return false;
        }

        return $ultimo->greaterThan(
            CarbonImmutable::now()->subMinutes($this->toleranceMinutes()),
        );
    }

    public function toleranceMinutes(): int
    {
        return (int) config('monitor.scheduler_tolerance_minutes', 15);
    }

    private function key(): string
    {
        return (string) config('monitor.heartbeat_key', 'monitor:scheduler-heartbeat');
    }
}
