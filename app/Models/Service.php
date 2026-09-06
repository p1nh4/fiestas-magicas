<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriceMode;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\KeepsOldUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;
    use KeepsOldUrls;

    public array $translatable = ['name', 'slug', 'summary', 'description'];

    protected $fillable = [
        'category_id', 'name', 'slug', 'summary', 'description',
        'base_price', 'price_mode', 'setup_minutes', 'is_active', 'position', 'seo',
    ];

    protected $attributes = [
        'base_price' => 0,
        'price_mode' => 'fixed',
        'setup_minutes' => 0,
        'is_active' => true,
        'position' => 0,
        'seo' => '{}',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'price_mode' => PriceMode::class,
            'setup_minutes' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
            'seo' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function publicRouteName(): ?string
    {
        return 'services.show';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * O endereço público deste serviço no idioma pedido, ou null se ele não
     * tiver endereço nesse idioma.
     *
     * Devolver null em vez de um `#` é a mesma regra do `Page::urlFor`:
     * um link que não vai a lado nenhum é pior do que texto simples, tanto
     * para quem clica como para quem indexa.
     */
    public function urlFor(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $slug = $this->getTranslation('slug', $locale, false);

        return blank($slug)
            ? null
            : route('services.show', ['locale' => $locale, 'slug' => $slug]);
    }
}
