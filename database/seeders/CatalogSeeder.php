<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryKind;
use App\Enums\PriceMode;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Item;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial nos três idiomas.
 *
 * Regra que vale a pena manter: aqui não se inventam PREÇOS nem CONDIÇÕES.
 * Os serviços entram com base_price a 0, e o site mostra "a consultar" em
 * vez de um número imaginado. Quando a Sol disser os preços reais,
 * mudam-se no backoffice — não é preciso voltar a este ficheiro.
 *
 * O mesmo para as perguntas frequentes: as que dependem de condições que
 * ela ainda não confirmou ficam despublicadas, à espera de revisão.
 */
final class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $services = Category::firstOrCreate(
            ['kind' => CategoryKind::Service->value, 'name->es' => 'Celebraciones'],
            [
                'name' => ['es' => 'Celebraciones', 'gl' => 'Celebracións', 'pt' => 'Celebrações'],
                'slug' => ['es' => 'celebraciones', 'gl' => 'celebracions', 'pt' => 'celebracoes'],
            ],
        );

        $material = Category::firstOrCreate(
            ['kind' => CategoryKind::Item->value, 'name->es' => 'Material'],
            [
                'name' => ['es' => 'Material', 'gl' => 'Material', 'pt' => 'Material'],
                'slug' => ['es' => 'material', 'gl' => 'material', 'pt' => 'material'],
            ],
        );

        // ------------------------------------------------------------------
        //  Serviços. A chave 'image' do seo aponta para public/img/muestras.
        //  Ao trocar pelas fotos reais, basta manter os nomes dos ficheiros.
        // ------------------------------------------------------------------
        $catalogue = [
            [
                'image' => 'cumpleanos',
                'name' => ['es' => 'Cumpleaños', 'gl' => 'Aniversarios', 'pt' => 'Aniversários'],
                'slug' => ['es' => 'cumpleanos', 'gl' => 'aniversarios', 'pt' => 'aniversarios'],
                'summary' => [
                    'es' => 'Arco de globos, mesa dulce y rincón de fotos, a juego con la temática que elijas.',
                    'gl' => 'Arco de globos, mesa doce e recuncho de fotos, a xogo coa temática que escollas.',
                    'pt' => 'Arco de balões, mesa doce e canto de fotos, a condizer com o tema que escolheres.',
                ],
            ],
            [
                'image' => 'bautizo',
                'name' => ['es' => 'Bautizos', 'gl' => 'Bautizos', 'pt' => 'Batizados'],
                'slug' => ['es' => 'bautizos', 'gl' => 'bautizos', 'pt' => 'batizados'],
                'summary' => [
                    'es' => 'Globos orgánicos en tonos suaves, decoración de iglesia y detalles para los invitados.',
                    'gl' => 'Globos orgánicos en tons suaves, decoración de igrexa e detalles para os convidados.',
                    'pt' => 'Balões orgânicos em tons suaves, decoração de igreja e lembranças para os convidados.',
                ],
            ],
            [
                'image' => 'comunion',
                'name' => ['es' => 'Comuniones', 'gl' => 'Comuñóns', 'pt' => 'Comunhões'],
                'slug' => ['es' => 'comuniones', 'gl' => 'comunions', 'pt' => 'comunhoes'],
                'summary' => [
                    'es' => 'Mesa dulce, letras corpóreas y photocall. La temporada de mayo se llena pronto.',
                    'gl' => 'Mesa doce, letras corpóreas e photocall. A tempada de maio énchese cedo.',
                    'pt' => 'Mesa doce, letras grandes e photocall. A época de maio esgota cedo.',
                ],
            ],
            [
                'image' => 'boda',
                'name' => ['es' => 'Bodas', 'gl' => 'Vodas', 'pt' => 'Casamentos'],
                'slug' => ['es' => 'bodas', 'gl' => 'vodas', 'pt' => 'casamentos'],
                'summary' => [
                    'es' => 'Arco ceremonial, photocall floral y detalles de mesa. Coordinamos con el pazo o la finca.',
                    'gl' => 'Arco cerimonial, photocall floral e detalles de mesa. Coordinamos co pazo ou a finca.',
                    'pt' => 'Arco cerimonial, photocall floral e pormenores de mesa. Combinamos com a quinta.',
                ],
            ],
            [
                'image' => 'mesa-dulce',
                'name' => ['es' => 'Mesas dulces', 'gl' => 'Mesas doces', 'pt' => 'Mesas doces'],
                'slug' => ['es' => 'mesas-dulces', 'gl' => 'mesas-doces', 'pt' => 'mesas-doces'],
                'summary' => [
                    'es' => 'Soportes, campanas, mantel a juego y cartelería con el nombre.',
                    'gl' => 'Soportes, campás, mantel a xogo e cartelería co nome.',
                    'pt' => 'Suportes, redomas, toalha a condizer e placas com o nome.',
                ],
            ],
            [
                'image' => 'photocall',
                'name' => ['es' => 'Photocall', 'gl' => 'Photocall', 'pt' => 'Photocall'],
                'slug' => ['es' => 'photocall', 'gl' => 'photocall', 'pt' => 'photocall'],
                'summary' => [
                    'es' => 'Floral, de globos o con letras iluminadas, con atrezzo para los invitados.',
                    'gl' => 'Floral, de globos ou con letras iluminadas, con atrezzo para os convidados.',
                    'pt' => 'Floral, de balões ou com letras iluminadas, com adereços para os convidados.',
                ],
            ],
        ];

        foreach ($catalogue as $position => $data) {
            Service::updateOrCreate(
                ['slug->es' => $data['slug']['es']],
                [
                    'category_id' => $services->id,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'summary' => $data['summary'],
                    'description' => [],
                    // Preço real por confirmar com a Sol. A 0, o site mostra
                    // "a consultar" — que é honesto — em vez de um "desde 180 €"
                    // que ninguém disse.
                    'base_price' => 0,
                    'price_mode' => PriceMode::Quote,
                    'is_active' => true,
                    'position' => $position,
                    'seo' => ['image' => $data['image']],
                ],
            );
        }

        // ------------------------------------------------------------------
        //  Material de aluguer. O stock e as folgas também são estimativas
        //  a confirmar; o que não é estimativa é o comportamento — a base de
        //  dados impede reservar mais do que existe, seja qual for o número.
        // ------------------------------------------------------------------
        $items = [
            [
                'sku' => 'PHOTOCALL-FLORAL',
                'name' => ['es' => 'Photocall floral', 'gl' => 'Photocall floral', 'pt' => 'Photocall floral'],
                'slug' => ['es' => 'photocall-floral', 'gl' => 'photocall-floral', 'pt' => 'photocall-floral'],
                'stock_qty' => 1,
            ],
            [
                'sku' => 'LETRAS-LED',
                'name' => ['es' => 'Letras iluminadas', 'gl' => 'Letras iluminadas', 'pt' => 'Letras iluminadas'],
                'slug' => ['es' => 'letras-iluminadas', 'gl' => 'letras-iluminadas', 'pt' => 'letras-iluminadas'],
                'stock_qty' => 1,
            ],
            [
                'sku' => 'SOPORTES-MESA',
                'name' => ['es' => 'Soportes de mesa dulce', 'gl' => 'Soportes de mesa doce', 'pt' => 'Suportes de mesa doce'],
                'slug' => ['es' => 'soportes-mesa-dulce', 'gl' => 'soportes-mesa-doce', 'pt' => 'suportes-mesa-doce'],
                'stock_qty' => 3,
            ],
            [
                'sku' => 'SILLAS-TIFFANY',
                'name' => ['es' => 'Sillas Tiffany', 'gl' => 'Cadeiras Tiffany', 'pt' => 'Cadeiras Tiffany'],
                'slug' => ['es' => 'sillas-tiffany', 'gl' => 'cadeiras-tiffany', 'pt' => 'cadeiras-tiffany'],
                'stock_qty' => 40,
            ],
        ];

        foreach ($items as $data) {
            Item::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'category_id' => $material->id,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'description' => [],
                    'stock_qty' => $data['stock_qty'],
                    'price_per_day' => 0,
                    'buffer_before_min' => 120,
                    'buffer_after_min' => 1440,
                    'is_rentable' => true,
                    'is_active' => true,
                ],
            );
        }

        $this->seedFaqs();
    }

    /**
     * Perguntas frequentes.
     *
     * Só ficam publicadas as respostas que são verdade por construção —
     * geografia e idiomas. Tudo o que depende de condições comerciais que
     * a Sol ainda não confirmou entra despublicado: fica no backoffice para
     * ela rever e publicar, em vez de aparecer no site como facto.
     */
    private function seedFaqs(): void
    {
        $areas = implode(', ', config('business.service_areas'));

        $faqs = [
            [
                'published' => true,
                'question' => [
                    'es' => '¿A qué zonas os desplazáis?',
                    'gl' => 'A que zonas vos desprazades?',
                    'pt' => 'A que zonas se deslocam?',
                ],
                'answer' => [
                    'es' => "Trabajamos en Baiona y todo el Val Miñor, el Baixo Miño, Vigo y el norte de Portugal: {$areas}. Si tu sitio no está en la lista, pregúntanos igualmente.",
                    'gl' => "Traballamos en Baiona e todo o Val Miñor, o Baixo Miño, Vigo e o norte de Portugal: {$areas}. Se o teu sitio non está na lista, pregúntanos igual.",
                    'pt' => "Trabalhamos em Baiona e em todo o Val Miñor, no Baixo Miño, em Vigo e no norte de Portugal: {$areas}. Se o teu local não estiver na lista, pergunta na mesma.",
                ],
            ],
            [
                'published' => true,
                'question' => [
                    'es' => '¿En qué idiomas puedo escribiros?',
                    'gl' => 'En que idiomas podo escribirvos?',
                    'pt' => 'Em que idiomas posso escrever?',
                ],
                'answer' => [
                    'es' => 'En castellano, galego o português. Te respondemos en el idioma en el que nos escribas.',
                    'gl' => 'En castelán, galego ou portugués. Respondémosche no idioma no que nos escribas.',
                    'pt' => 'Em castelhano, galego ou português. Respondemos no idioma em que escreveres.',
                ],
            ],

            // --- por rever com a Sol antes de publicar ---
            [
                'published' => false,
                'question' => [
                    'es' => '¿Cuánto hay que pagar por adelantado?',
                    'gl' => 'Canto hai que pagar por adiantado?',
                    'pt' => 'Quanto é preciso pagar adiantado?',
                ],
                'answer' => [
                    'es' => 'PENDIENTE: confirmar con Sol el porcentaje de señal y cuándo se paga el resto.',
                    'gl' => 'PENDENTE: confirmar con Sol a porcentaxe de sinal e cando se paga o resto.',
                    'pt' => 'PENDENTE: confirmar com a Sol a percentagem de sinal e quando se paga o resto.',
                ],
            ],
            [
                'published' => false,
                'question' => [
                    'es' => '¿Con cuánta antelación hay que reservar?',
                    'gl' => 'Con canta antelación hai que reservar?',
                    'pt' => 'Com quanta antecedência é preciso reservar?',
                ],
                'answer' => [
                    'es' => 'PENDIENTE: confirmar con Sol, sobre todo para la temporada de comuniones.',
                    'gl' => 'PENDENTE: confirmar con Sol, sobre todo para a tempada de comuñóns.',
                    'pt' => 'PENDENTE: confirmar com a Sol, sobretudo para a época das comunhões.',
                ],
            ],
        ];

        foreach ($faqs as $position => $faq) {
            Faq::updateOrCreate(
                ['question->es' => $faq['question']['es']],
                [
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                    'position' => $position,
                    'is_published' => $faq['published'],
                ],
            );
        }
    }
}
