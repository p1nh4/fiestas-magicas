<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ServiceArea;
use App\Support\Locales;
use Illuminate\Http\Response;

final class SeoController extends Controller
{
    /**
     * Sitemap com as três versões de cada página e as ligações hreflang
     * entre elas. Sem isto, o Google trata as versões em galego e português
     * como conteúdo duplicado do espanhol.
     *
     * Nota sobre a forma: cada entrada é um mapa idioma => URL, e não uma
     * rota com o idioma trocado. Nas páginas de zona o endereço muda mesmo
     * entre idiomas (o slug está guardado por idioma), e a primeira versão
     * disto — que assumia a mesma rota com outro prefixo — teria escrito
     * hreflangs a apontar para páginas inexistentes.
     */
    public function sitemap(): Response
    {
        $entries = [];

        // Home.
        $entries[] = [
            'urls' => $this->urlsFor('home'),
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        // Lista de zonas.
        $entries[] = [
            'urls' => $this->urlsFor('areas.index'),
            'changefreq' => 'monthly',
            'priority' => '0.7',
        ];

        // Catálogo de aluguer.
        $entries[] = [
            'urls' => $this->urlsFor('rentals.index'),
            'changefreq' => 'monthly',
            'priority' => '0.7',
        ];

        foreach (Item::query()->rentable()->get() as $item) {
            $urls = $this->translatedUrls('rentals.show', $item, 'slug');

            if ($urls !== []) {
                $entries[] = [
                    'urls' => $urls,
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                    'lastmod' => $item->updated_at?->toAtomString(),
                ];
            }
        }

        // Uma entrada por zona publicada. As que estão em rascunho não
        // entram: não têm página, e anunciá-las no sitemap seria mandar o
        // Google a um 404.
        foreach (ServiceArea::query()->published()->get() as $area) {
            $urls = $this->translatedUrls('areas.show', $area, 'slug');

            if ($urls !== []) {
                $entries[] = [
                    'urls' => $urls,
                    'changefreq' => 'monthly',
                    'priority' => '0.8',
                    'lastmod' => $area->updated_at?->toAtomString(),
                ];
            }
        }

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        foreach ($entries as $entry) {
            foreach ($entry['urls'] as $url) {
                $xml->startElement('url');
                $xml->writeElement('loc', $url);

                if (! empty($entry['lastmod'])) {
                    $xml->writeElement('lastmod', $entry['lastmod']);
                }

                $xml->writeElement('changefreq', $entry['changefreq']);
                $xml->writeElement('priority', $entry['priority']);

                foreach ($entry['urls'] as $alternate => $alternateUrl) {
                    $xml->startElement('xhtml:link');
                    $xml->writeAttribute('rel', 'alternate');
                    $xml->writeAttribute('hreflang', Locales::hreflang($alternate));
                    $xml->writeAttribute('href', $alternateUrl);
                    $xml->endElement();
                }

                $xml->startElement('xhtml:link');
                $xml->writeAttribute('rel', 'alternate');
                $xml->writeAttribute('hreflang', 'x-default');
                $xml->writeAttribute('href', $entry['urls'][Locales::DEFAULT] ?? reset($entry['urls']));
                $xml->endElement();

                $xml->endElement();
            }
        }

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            // Fora de produção fecha-se tudo: um site de testes indexado
            // rouba posições ao site a sério.
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * As URLs de um registo com endereço próprio por idioma.
     *
     * Um registo sem slug num idioma simplesmente não existe nesse idioma:
     * melhor faltar no sitemap do que apontar para uma página que dá 404.
     *
     * @return array<string, string>
     */
    private function translatedUrls(string $route, object $model, string $field): array
    {
        $urls = [];

        foreach (Locales::SUPPORTED as $locale) {
            $slug = $model->getTranslation($field, $locale, false);

            if (blank($slug)) {
                continue;
            }

            $urls[$locale] = route($route, ['locale' => $locale, 'slug' => $slug]);
        }

        return $urls;
    }

    /**
     * O mesmo caminho nos três idiomas, para rotas sem parâmetros próprios.
     *
     * @return array<string, string>
     */
    private function urlsFor(string $route): array
    {
        $out = [];
        foreach (Locales::SUPPORTED as $locale) {
            $out[$locale] = route($route, ['locale' => $locale]);
        }

        return $out;
    }
}
