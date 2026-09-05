<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Period;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Converte a coluna tstzrange do Postgres num objeto Period e vice-versa.
 *
 * @implements CastsAttributes<Period|null, Period|array{0: mixed, 1: mixed}|string|null>
 */
final class PeriodCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Period
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof Period ? $value : Period::fromDatabase((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        $period = match (true) {
            $value instanceof Period => $value,
            is_string($value) => Period::fromDatabase($value),
            is_array($value) && count($value) === 2 => Period::make(...array_values($value)),
            default => throw new InvalidArgumentException(
                'Um período tem de ser um App\Support\Period, um par [início, fim] '
                . 'ou uma string no formato do Postgres.'
            ),
        };

        return [$key => $period->toDatabase()];
    }
}
