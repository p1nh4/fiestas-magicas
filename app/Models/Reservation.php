<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\PeriodCast;
use App\Enums\ReservationStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueio de uma peca durante um intervalo.
 *
 * O `period` ja inclui as folgas de montagem e limpeza da peca. Sem evento
 * associado, e um bloqueio manual (manutencao, peca partida) e o
 * `blocked_reason` e obrigatorio.
 *
 * Escrever aqui pode rebentar: o trigger da base de dados recusa a linha
 * se nao houver stock. Usa o AvailabilityService, que traduz esse erro.
 */
class Reservation extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'item_id', 'event_id', 'quantity', 'period',
        'status', 'blocked_reason', 'hold_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'period' => PeriodCast::class,
            'status' => ReservationStatus::class,
            'quantity' => 'integer',
            'hold_expires_at' => 'immutable_datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isMaintenanceBlock(): bool
    {
        return $this->event_id === null;
    }

    /** So o que conta para o stock. Reservas canceladas nao ocupam nada. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '<>', ReservationStatus::Cancelled->value);
    }

    /** Reservas que se sobrepoem a um intervalo, no lado do Postgres. */
    public function scopeOverlapping(Builder $query, string $range): Builder
    {
        return $query->whereRaw('period && ?::tstzrange', [$range]);
    }
}
