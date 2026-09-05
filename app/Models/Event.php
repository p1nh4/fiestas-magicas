<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\Locale;
use App\Models\Concerns\HasPublicUuid;
use App\Support\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'lead_id', 'reference', 'title', 'event_type', 'status',
        'starts_at', 'ends_at', 'setup_starts_at', 'teardown_ends_at',
        'venue_name', 'venue_address', 'venue_city', 'distance_km', 'guests_count',
        'locale', 'notes', 'internal_notes',
        'total_amount', 'deposit_amount', 'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'event_type' => EventType::class,
            'locale' => Locale::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'setup_starts_at' => 'immutable_datetime',
            'teardown_ends_at' => 'immutable_datetime',
            'distance_km' => 'decimal:1',
            'guests_count' => 'integer',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** Orcamento em vigor: a versao mais alta. */
    public function currentQuote(): ?Quote
    {
        return $this->quotes()->orderByDesc('version')->first();
    }

    /**
     * Janela de ocupacao da equipa: da montagem ao levantamento.
     * Quando nao ha horas de montagem definidas, usa-se o evento em si.
     */
    public function occupancyWindow(): Period
    {
        return new Period(
            $this->setup_starts_at ?? $this->starts_at,
            $this->teardown_ends_at ?? $this->ends_at,
        );
    }

    public function balanceDue(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->paid_amount, 2);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }
}
