<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriceMode;
use App\Models\Concerns\HasPublicUuid;
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

    public array $translatable = ['name', 'slug', 'summary', 'description'];

    protected $fillable = [
        'category_id', 'name', 'slug', 'summary', 'description',
        'base_price', 'price_mode', 'setup_minutes', 'is_active', 'position', 'seo',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
