<?php

declare(strict_types=1);

namespace App\Support\Availability;

use App\Enums\ReservationStatus;
use App\Models\Event;
use App\Models\Item;
use App\Models\Reservation;
use App\Support\Period;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Disponibilidade de peças.
 *
 * ------------------------------------------------------------------
 *  Porque é que isto duplica a lógica do trigger da base de dados
 * ------------------------------------------------------------------
 *  Não duplica por desleixo: são dois papéis diferentes.
 *
 *  · O trigger é a GARANTIA. Corre dentro da transação, tem o advisory
 *    lock, e nenhum bug do PHP o contorna. É a última palavra.
 *
 *  · Este serviço é a RESPOSTA AO UTILIZADOR. Serve para o calendário
 *    dizer "restam 12 cadeiras" antes de a pessoa carregar em reservar,
 *    e para o formulário dar um erro decente em vez de uma exceção de SQL.
 *
 *  Nunca se deve confiar só neste serviço para escrever: entre a leitura
 *  e a escrita, outra pessoa pode reservar. Por isso `reserve()` escreve
 *  na mesma e deixa o trigger decidir — só traduz o erro do Postgres para
 *  uma exceção com sentido.
 *
 *  O algoritmo é o mesmo do trigger (varrimento de linha): o pico de
 *  procura num intervalo só pode acontecer no início de alguma reserva,
 *  ou no início do próprio intervalo que estamos a testar.
 */
final class AvailabilityService
{
    /** SQLSTATE que o trigger levanta quando não há stock. */
    private const CHECK_VIOLATION = '23514';

    /**
     * Janela que a peça fica realmente bloqueada: o tempo do evento mais
     * as folgas de transporte, montagem e limpeza da própria peça.
     */
    public function blockedWindow(Item $item, Period $usage): Period
    {
        return $usage->padded($item->buffer_before_min, $item->buffer_after_min);
    }

    /** Quantas unidades ainda se podem reservar nesta janela. */
    public function availableQuantity(Item $item, Period $window): int
    {
        return max(0, $item->stock_qty - $this->peakDemand($item, $window));
    }

    public function isAvailable(Item $item, Period $window, int $quantity = 1): bool
    {
        return $quantity > 0 && $this->availableQuantity($item, $window) >= $quantity;
    }

    /**
     * Máximo de unidades simultaneamente reservadas dentro da janela.
     *
     * Os pontos candidatos são os inícios das reservas que se sobrepõem à
     * janela, mais o início da própria janela — sem este último, uma janela
     * que começa a meio de uma reserva longa daria zero procura.
     */
    public function peakDemand(Item $item, Period $window): int
    {
        $range = $window->toDatabase();

        $row = DB::selectOne(<<<'SQL'
            SELECT COALESCE(MAX(s.demand), 0) AS peak
            FROM (
                SELECT b.ts, SUM(r.quantity)::int AS demand
                FROM (
                    SELECT DISTINCT lower(period) AS ts
                      FROM reservations
                     WHERE item_id = :item
                       AND status <> 'cancelled'
                       AND period && :range1::tstzrange
                    UNION
                    SELECT lower(:range2::tstzrange)
                ) b
                JOIN reservations r
                  ON r.item_id = :item2
                 AND r.status <> 'cancelled'
                 AND r.period @> b.ts
                GROUP BY b.ts
            ) s
        SQL, [
            'item' => $item->getKey(),
            'item2' => $item->getKey(),
            'range1' => $range,
            'range2' => $range,
        ]);

        return (int) ($row->peak ?? 0);
    }

    /**
     * Dia a dia, para pintar o calendário do catálogo de aluguer.
     *
     * @return array<string, int>  '2027-05-16' => unidades livres nesse dia
     */
    public function dailyAvailability(Item $item, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $out = [];
        $day = $from->startOfDay();
        $last = $to->startOfDay();

        while ($day <= $last) {
            $window = new Period($day, $day->addDay());
            $out[$day->toDateString()] = $this->availableQuantity($item, $window);
            $day = $day->addDay();
        }

        return $out;
    }

    /**
     * Reserva de facto. A verificação prévia é só para falhar cedo e barato;
     * quem garante é o trigger, dentro da transação.
     *
     * @throws OutOfStockException
     */
    public function reserve(
        Item $item,
        Period $window,
        int $quantity = 1,
        ?Event $event = null,
        ?string $blockedReason = null,
        ?CarbonImmutable $holdUntil = null,
    ): Reservation {
        if ($event === null && $blockedReason === null) {
            throw new \InvalidArgumentException(
                'Uma reserva tem de estar ligada a um evento ou trazer um motivo de bloqueio.'
            );
        }

        try {
            return DB::transaction(function () use ($item, $window, $quantity, $event, $blockedReason, $holdUntil) {
                return Reservation::create([
                    'item_id' => $item->getKey(),
                    'event_id' => $event?->getKey(),
                    'quantity' => $quantity,
                    'period' => $window,
                    'status' => $holdUntil
                        ? ReservationStatus::Hold
                        : ReservationStatus::Confirmed,
                    'blocked_reason' => $blockedReason,
                    'hold_expires_at' => $holdUntil,
                ]);
            });
        } catch (Throwable $e) {
            if ($this->isOutOfStock($e)) {
                throw OutOfStockException::for(
                    $item,
                    $window,
                    $quantity,
                    $this->availableQuantity($item, $window),
                    $e,
                );
            }

            throw $e;
        }
    }

    /**
     * Liberta as reservas de carrinho que expiraram.
     * Corre no scheduler, de poucos em poucos minutos.
     */
    public function releaseExpiredHolds(): int
    {
        return Reservation::query()
            ->where('status', ReservationStatus::Hold->value)
            ->where('hold_expires_at', '<', now())
            ->update([
                'status' => ReservationStatus::Cancelled->value,
                'updated_at' => now(),
            ]);
    }

    private function isOutOfStock(Throwable $e): bool
    {
        $sqlState = null;

        if ($e instanceof \PDOException) {
            $sqlState = $e->getCode();
        } elseif ($e->getPrevious() instanceof \PDOException) {
            $sqlState = $e->getPrevious()->getCode();
        }

        if ((string) $sqlState === self::CHECK_VIOLATION) {
            return true;
        }

        // rede de segurança: a mensagem do trigger é nossa e é estável
        return str_contains($e->getMessage(), 'Stock insuficiente');
    }
}
