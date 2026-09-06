<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use App\Enums\LeadStatus;
use App\Enums\Locale;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pedido vindo do site. Ainda nao e cliente. */
class Lead extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'client_id', 'name', 'email', 'phone', 'locale', 'event_type', 'event_date',
        'guests_count', 'venue', 'budget_hint', 'message', 'status',
        'utm_source', 'utm_medium', 'utm_campaign', 'referrer', 'landing_path',
        'ip_hash', 'user_agent', 'contacted_at', 'converted_at', 'lost_reason',
    ];

    /* Ver a nota no Item: o Eloquent nao conhece os DEFAULT do Postgres. */
    protected $attributes = [
        'status' => 'new',
        'locale' => 'es',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'event_type' => EventType::class,
            'locale' => Locale::class,
            'event_date' => 'immutable_date',
            'guests_count' => 'integer',
            'contacted_at' => 'immutable_datetime',
            'converted_at' => 'immutable_datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * O IP nunca fica em claro. Guarda-se um hash, que chega para detetar
     * spam vindo da mesma origem sem armazenar um dado pessoal (RGPD).
     */
    public static function hashIp(?string $ip): ?string
    {
        return $ip === null ? null : hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
