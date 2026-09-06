<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\KeepsOldUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Páginas de texto: aviso legal, privacidade, cookies, "quiénes somos".
 *
 * A `key` é o identificador estável (`privacidad`), o `slug` é o endereço e
 * pode mudar com o idioma. O código refere-se sempre à key — assim a Sol
 * pode mudar o endereço sem partir a ligação no formulário.
 */
class Page extends Model
{
    use HasFactory;
    use HasTranslations;
    use KeepsOldUrls;

    public array $translatable = ['title', 'slug', 'body'];

    /** Tem pagina publica em /{idioma}/{slug}. */
    public function publicRouteName(): ?string
    {
        return 'page.show';
    }

    protected $fillable = ['key', 'title', 'slug', 'body', 'seo', 'is_published'];

    protected function casts(): array
    {
        return ['seo' => 'array', 'is_published' => 'boolean'];
    }

    /** Keys usadas pelo código. Se uma destas faltar, o rodapé fica sem link. */
    public const LEGAL = 'aviso-legal';

    public const PRIVACY = 'privacidad';

    public const COOKIES = 'cookies';

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * O endereço de uma página, ou null se não estiver publicada.
     *
     * Devolver null e não uma URL morta é de propósito: um `href="#"` numa
     * caixa de consentimento do RGPD é pior do que não haver link nenhum —
     * dá a entender que há uma política para ler quando não há. Enquanto a
     * página não existir, o texto aparece sem ligação.
     *
     * Sem cache. A primeira versão guardava o resultado num `static` dentro
     * do método, o que parecia inofensivo e não era: um `static` sobrevive
     * ao pedido inteiro e, na suite de testes, ao processo todo. Publicar
     * uma página deixava de ter efeito até reiniciar. Três consultas
     * indexadas por página não custam nada; um valor errado custa.
     */
    public static function urlFor(string $key, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        $page = static::query()->published()->where('key', $key)->first();
        $slug = $page?->getTranslation('slug', $locale, false);

        return blank($slug)
            ? null
            : route('page.show', ['locale' => $locale, 'slug' => $slug]);
    }
}
