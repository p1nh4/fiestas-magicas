<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClientKind;
use App\Enums\Locale;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'kind', 'name', 'legal_name', 'tax_id', 'email', 'phone', 'whatsapp',
        'locale', 'address_line', 'postal_code', 'city', 'province', 'country',
        'source', 'notes', 'marketing_opt_in_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ClientKind::class,
            'locale' => Locale::class,
            'marketing_opt_in_at' => 'immutable_datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** So se pode enviar marketing a quem deu consentimento explicito (RGPD). */
    public function acceptsMarketing(): bool
    {
        return $this->marketing_opt_in_at !== null;
    }
}
