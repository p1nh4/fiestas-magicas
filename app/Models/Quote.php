<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Orcamento. Nunca se edita um que ja foi enviado — cria-se a versao
 * seguinte. Assim ha sempre prova do que o cliente viu quando aceitou.
 */
class Quote extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'event_id', 'version', 'status', 'public_token', 'valid_until', 'currency',
        'subtotal', 'discount_amount', 'tax_rate', 'tax_amount', 'total',
        'deposit_pct', 'notes', 'sent_at', 'viewed_at', 'accepted_at',
        'accepted_ip_hash', 'rejected_at',
    ];

    protected $hidden = ['public_token', 'accepted_ip_hash'];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'version' => 'integer',
            'valid_until' => 'immutable_date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'deposit_pct' => 'decimal:2',
            'notes' => 'array',
            'sent_at' => 'immutable_datetime',
            'viewed_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('position');
    }

    public function isEditable(): bool
    {
        return $this->status === QuoteStatus::Draft;
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function depositAmount(): string
    {
        return bcdiv(bcmul((string) $this->total, (string) $this->deposit_pct, 4), '100', 2);
    }
}
