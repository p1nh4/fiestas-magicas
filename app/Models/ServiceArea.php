<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\KeepsOldUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Um concelho onde a empresa trabalha, com página própria.
 *
 * Existe por uma razão de negócio concreta: ninguém escreve "decoración de
 * fiestas" no Google. Escreve "decoración de globos en Nigrán". Sem uma
 * página por sítio, a empresa não aparece nessas buscas — que são as que
 * trazem alguém a marcar uma festa.
 *
 * A armadilha está do outro lado. Onze páginas iguais com o nome do sítio
 * trocado chamam-se doorway pages, e o Google não se limita a ignorá-las:
 * desvaloriza o domínio inteiro. É por isso que publicar exige texto
 * próprio, e a regra vive no CHECK da base de dados e não numa validação
 * de formulário — uma regra que se contorna com um `update` não é regra.
 */
class ServiceArea extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;
    use KeepsOldUrls;

    public array $translatable = ['slug', 'intro'];

    /** Tem pagina publica em /{idioma}/zonas/{slug}. */
    public function publicRouteName(): ?string
    {
        return 'areas.show';
    }

    protected $fillable = [
        'name', 'slug', 'province', 'country',
        'distance_km', 'travel_minutes',
        'intro', 'seo', 'position', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:1',
            'travel_minutes' => 'integer',
            'position' => 'integer',
            'is_published' => 'boolean',
            'seo' => 'array',
        ];
    }

    /** Mínimo de caracteres para o texto contar como texto. Igual ao CHECK. */
    public const MIN_INTRO = 200;

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }

    /**
     * Já tem texto que chegue para poder ser publicada?
     *
     * O número é o mesmo do CHECK no schema.sql — de propósito, para que o
     * backoffice possa avisar antes de a base de dados recusar. Se um dia
     * mudar num sítio, tem de mudar no outro; há um teste que o verifica.
     */
    public function isPublishable(): bool
    {
        return mb_strlen(trim((string) $this->getTranslation('intro', 'es', false))) >= self::MIN_INTRO;
    }

    /** Distância a partir de Baiona, para mostrar na página. */
    public function distanceLabel(): ?string
    {
        if ($this->distance_km === null) {
            return null;
        }

        return rtrim(rtrim(number_format((float) $this->distance_km, 1, ',', '.'), '0'), ',').' km';
    }

    public function isPortugal(): bool
    {
        return $this->country === 'PT';
    }
}
