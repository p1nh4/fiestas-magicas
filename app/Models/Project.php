<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EventType;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\KeepsOldUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * Trabalho publicado no portefólio. É a principal arma de SEO do site.
 *
 * `consent_at` não é burocracia: são fotos da festa de outra pessoa, muitas
 * vezes com os filhos dela lá dentro. A base de dados tem um CHECK que
 * impede publicar sem autorização.
 *
 * Sobre as fotos: ficam em `photos`, uma lista de caminhos no disco
 * `public`, tal como no módulo de projetos. Este model chegou a implementar
 * o `HasMedia` da medialibrary, mas era o único sítio do projeto que a
 * usava e nem sequer havia campo no formulário para lá pôr uma foto. Dois
 * sistemas de imagens para uma empresa com um portefólio não se justificam;
 * se o número de fotos um dia crescer ao ponto de precisar de conversões
 * automáticas, volta-se atrás com calma.
 */
class Project extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;
    use KeepsOldUrls;

    public array $translatable = ['title', 'slug', 'description'];

    protected $fillable = [
        'event_id', 'title', 'slug', 'description', 'event_type', 'happened_on',
        'venue', 'city', 'guests_count', 'is_featured', 'is_published',
        'published_at', 'position', 'seo', 'consent_at', 'photos',
    ];

    protected $attributes = [
        'photos' => '[]',
        'seo' => '{}',
        'is_featured' => false,
        'is_published' => false,
        'position' => 0,
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
            'photos' => 'array',
            'consent_at' => 'immutable_datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function publicRouteName(): ?string
    {
        return 'projects.show';
    }

    /**
     * O endereço público deste trabalho no idioma pedido.
     *
     * null quando o trabalho não tem título nesse idioma, ou quando ainda
     * não está publicado — nos dois casos não há página, e um link para uma
     * página que não existe é um 404 à espera de acontecer.
     */
    public function urlFor(?string $locale = null): ?string
    {
        if (! $this->is_published) {
            return null;
        }

        $locale ??= app()->getLocale();
        $slug = $this->getTranslation('slug', $locale, false);

        return blank($slug)
            ? null
            : route('projects.show', ['locale' => $locale, 'slug' => $slug]);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function canBePublished(): bool
    {
        return $this->consent_at !== null;
    }

    /**
     * Os endereços das fotos, prontos para um `src`.
     *
     * @return list<string>
     */
    public function photoUrls(): array
    {
        $disk = Storage::disk('public');

        return array_values(array_map(
            static fn (string $path): string => $disk->url($path),
            array_filter($this->photos ?? [], 'is_string'),
        ));
    }

    /**
     * A foto de capa, ou null se ainda não houver nenhuma.
     *
     * Devolver null e não um marcador é de propósito: quem chama decide se
     * mostra a imagem de exemplo ou se não mostra nada. Um marcador
     * devolvido daqui acabaria a fingir-se de foto real numa etiqueta
     * `og:image` e a aparecer no WhatsApp de alguém.
     */
    public function coverUrl(): ?string
    {
        return $this->photoUrls()[0] ?? null;
    }
}
