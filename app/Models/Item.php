<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\KeepsOldUrls;
use App\Support\Period;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Peca fisica com stock. E aqui que vive o risco de overbooking — ver
 * App\Support\Availability\AvailabilityService e o trigger
 * reservations_check_capacity() no schema.
 */
class Item extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;
    use KeepsOldUrls;

    public array $translatable = ['name', 'slug', 'description'];

    /** Tem pagina publica em /{idioma}/alquiler/{slug}. */
    public function publicRouteName(): ?string
    {
        return 'rentals.show';
    }

    protected $fillable = [
        'category_id', 'sku', 'name', 'slug', 'description',
        'stock_qty', 'price_per_day', 'replacement_value',
        'buffer_before_min', 'buffer_after_min',
        'requires_transport', 'is_rentable', 'is_active',
    ];

    /*
     * Os mesmos valores por omissao do schema.sql.
     *
     * O Eloquent NAO conhece os DEFAULT da base de dados. Um
     * `Item::create([...])` sem folgas fica com null em memoria — a linha
     * gravada leva os 120/1440 do Postgres, mas o objeto que fica na mao
     * nao. E entao `blockedWindowFor()` chama `padded(null, null)` e
     * rebenta com um TypeError.
     *
     * Foi o mesmo erro que apanhou o `Lead::status`. Repetir os defaults
     * aqui e duplicacao, sim — mas e duplicacao que o gerador mantem
     * sincronizada com o schema, e a alternativa e codigo defensivo
     * espalhado por todo o lado a perguntar se o valor existe.
     */
    protected $attributes = [
        'stock_qty' => 1,
        'price_per_day' => 0,
        'buffer_before_min' => 120,
        'buffer_after_min' => 1440,
        'requires_transport' => false,
        'is_rentable' => true,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'stock_qty' => 'integer',
            'price_per_day' => 'decimal:2',
            'replacement_value' => 'decimal:2',
            'buffer_before_min' => 'integer',
            'buffer_after_min' => 'integer',
            'requires_transport' => 'boolean',
            'is_rentable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Janela realmente bloqueada: o uso mais as folgas de logistica. */
    public function blockedWindowFor(Period $usage): Period
    {
        return $usage->padded($this->buffer_before_min, $this->buffer_after_min);
    }

    /** Catalogo publico de aluguer: so o que esta ativo e e alugavel a solto. */
    public function scopeRentable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_rentable', true);
    }
}
