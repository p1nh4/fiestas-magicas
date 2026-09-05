<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'event_id', 'client_id', 'kind', 'method', 'status', 'amount', 'currency',
        'provider', 'provider_reference', 'paid_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PaymentKind::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'immutable_datetime',
            'meta' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeSettled(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Paid->value);
    }
}
