<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Um intervalo de tempo meio-aberto: [inicio, fim).
 *
 * Meio-aberto e a escolha importante. Se uma peca e devolvida as 20:00 e
 * outra reserva comeca as 20:00, nao ha conflito — mas com intervalos
 * fechados nos dois lados haveria. E o mesmo modelo que o tstzrange do
 * Postgres usa por omissao, por isso o PHP e a base de dados concordam.
 */
final class Period implements JsonSerializable, Stringable
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {
        if ($end <= $start) {
            throw new InvalidArgumentException(
                'O fim de um período tem de ser depois do início; '
                . "recebi [{$start->toIso8601String()}, {$end->toIso8601String()})."
            );
        }
    }

    public static function make(mixed $start, mixed $end): self
    {
        return new self(CarbonImmutable::parse($start), CarbonImmutable::parse($end));
    }

    /**
     * Lê o formato de range do Postgres: ["2027-05-16 08:00:00+00","2027-05-17 20:00:00+00")
     *
     * Só aceitamos limites [) porque é o único que escrevemos. Qualquer
     * outra combinação é sinal de que alguém mexeu na base de dados à mão
     * e é melhor rebentar do que adivinhar.
     */
    public static function fromDatabase(string $raw): self
    {
        $raw = trim($raw);

        if (! preg_match('/^\[\s*"?([^",]+)"?\s*,\s*"?([^",]+)"?\s*\)$/', $raw, $m)) {
            throw new InvalidArgumentException(
                "Formato de intervalo não reconhecido: {$raw}. Esperava [inicio,fim)."
            );
        }

        return self::make($m[1], $m[2]);
    }

    /** Formato aceite pelo Postgres num INSERT/UPDATE. */
    public function toDatabase(): string
    {
        return sprintf('[%s,%s)', $this->start->toIso8601String(), $this->end->toIso8601String());
    }

    public function overlaps(self $other): bool
    {
        return $this->start < $other->end && $other->start < $this->end;
    }

    public function contains(CarbonImmutable $moment): bool
    {
        return $moment >= $this->start && $moment < $this->end;
    }

    /**
     * Dias faturáveis. Uma peça levantada sexta e devolvida sábado conta
     * como 1 dia; qualquer fração acima disso arredonda para cima, que é
     * como se cobra aluguer.
     */
    public function billableDays(): int
    {
        return max(1, (int) ceil($this->start->diffInMinutes($this->end) / (60 * 24)));
    }

    public function durationInMinutes(): int
    {
        return (int) $this->start->diffInMinutes($this->end);
    }

    /** Alarga o intervalo com as folgas logísticas de uma peça. */
    public function padded(int $beforeMinutes, int $afterMinutes): self
    {
        return new self(
            $this->start->subMinutes(max(0, $beforeMinutes)),
            $this->end->addMinutes(max(0, $afterMinutes)),
        );
    }

    public function equals(self $other): bool
    {
        return $this->start->equalTo($other->start) && $this->end->equalTo($other->end);
    }

    public function jsonSerialize(): array
    {
        return [
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
        ];
    }

    public function __toString(): string
    {
        return $this->toDatabase();
    }
}
