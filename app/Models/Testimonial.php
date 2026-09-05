<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Testemunhos REAIS. `consent_at` e NOT NULL na base de dados: sem
 * autorizacao de quem escreveu, nao entra. Nunca preencher esta tabela
 * com texto inventado — nenhum site serio do setor tem testemunhos falsos.
 */
class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'event_id', 'author_name', 'body', 'locale',
        'rating', 'source', 'source_url', 'consent_at', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'locale' => Locale::class,
            'rating' => 'integer',
            'consent_at' => 'immutable_datetime',
            'is_published' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNotNull('consent_at');
    }
}
