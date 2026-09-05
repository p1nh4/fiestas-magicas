<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Trabalho publicado no portefolio. E a principal arma de SEO do site.
 *
 * `consent_at` nao e burocracia: sao fotos da festa de outra pessoa. A
 * base de dados tem um CHECK que impede publicar sem autorizacao.
 */
class Project extends Model implements HasMedia
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;
    use InteractsWithMedia;

    public array $translatable = ['title', 'slug', 'description'];

    protected $fillable = [
        'event_id', 'title', 'slug', 'description', 'event_type', 'happened_on',
        'venue', 'city', 'guests_count', 'is_featured', 'is_published',
        'published_at', 'position', 'seo', 'consent_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'happened_on' => 'immutable_date',
            'guests_count' => 'integer',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'immutable_datetime',
            'position' => 'integer',
            'seo' => 'array',
            'consent_at' => 'immutable_datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(480)->format('webp')->nonQueued();
        $this->addMediaConversion('card')->width(960)->format('webp');
        $this->addMediaConversion('full')->width(1920)->format('webp');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }

    public function canBePublished(): bool
    {
        return $this->consent_at !== null;
    }
}
