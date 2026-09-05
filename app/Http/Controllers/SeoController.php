<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\Response;

final class SeoController extends Controller
{
    /**
     * Sitemap com as três versões de cada página e as ligações hreflang
     * entre elas. Sem isto, o Google trata as versões em galego e português
     * como conteúdo duplicado do espanhol.
     */
    public function sitemap(): Response
    {
        $pages = [
            ['route' => 'home', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ];

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

        foreach ($pages as $page) {
            foreach (Locales::SUPPORTED as $locale) {
                $xml->startElement('url');
                $xml->writeElement('loc', route($page['route'], ['locale' => $locale]));
                $xml->writeElement('changefreq', $page['changefreq']);
                $xml->writeElement('priority', $page['priority']);

                foreach (Locales::SUPPORTED as $alternate) {
                    $xml->startElement('xhtml:link');
                    $xml->writeAttribute('rel', 'alternate');
                    $xml->writeAttribute('hreflang', Locales::hreflang($alternate));
                    $xml->writeAttribute('href', route($page['route'], ['locale' => $alternate]));
                    $xml->endElement();
                }

                $xml->startElement('xhtml:link');
                $xml->writeAttribute('rel', 'alternate');
                $xml->writeAttribute('hreflang', 'x-default');
                $xml->writeAttribute('href', route($page['route'], ['locale' => Locales::DEFAULT]));
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
}
