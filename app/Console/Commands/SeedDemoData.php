<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventDesign;
use App\Models\Item;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Dados de demonstração, para ver o backoffice com coisas dentro.
 *
 * Um sistema vazio não se consegue avaliar: todas as listas dizem "no hay
 * nada" e não se percebe se o ecrã está bem desenhado ou se só está vazio.
 *
 * Tudo o que este comando cria leva DEMO no nome e uma referência própria,
 * para se apagar depois sem dúvidas — `php artisan fiestas:demo --limpiar`.
 *
 * Recusa-se a correr em produção. Não por prudência abstrata: uma festa
 * inventada no meio da agenda real é a maneira mais rápida de a Sol deixar
 * de confiar no que lá está.
 */
class SeedDemoData extends Command
{
    protected $signature = 'fiestas:demo {--limpiar : Borra los datos de demostración}';

    protected $description = 'Crea datos de ejemplo para ver el backoffice funcionando';

    private const REF = 'FM-DEMO-0001';

    private const MARK = '[DEMO]';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Esto no corre en producción. Y es a propósito.');

            return self::FAILURE;
        }

        return $this->option('limpiar') ? $this->limpiar() : $this->crear();
    }

    private function crear(): int
    {
        DB::transaction(function (): void {
            $client = Client::firstOrCreate(
                ['email' => 'demo@fiestasmagicas.test'],
                [
                    'name' => self::MARK.' Marta Pérez',
                    'phone' => '+34600000000',
                    'locale' => 'es',
                    'city' => 'Nigrán',
                    'source' => 'instagram',
                ],
            );

            $event = Event::firstOrCreate(
                ['reference' => self::REF],
                [
                    'client_id' => $client->id,
                    'title' => self::MARK.' Comunión de Uxía',
                    'event_type' => 'comunion',
                    'status' => EventStatus::Confirmed,
                    'locale' => 'es',
                    'starts_at' => now()->addDays(18)->setTime(13, 0),
                    'ends_at' => now()->addDays(18)->setTime(20, 0),
                    'setup_starts_at' => now()->addDays(18)->setTime(9, 0),
                    'teardown_ends_at' => now()->addDays(18)->setTime(22, 0),
                    'venue_name' => 'Pazo de Mos',
                    'venue_city' => 'Nigrán',
                    'guests_count' => 60,
                    'distance_km' => 7.5,
                    'notes' => 'Quiere tonos suaves y una mesa dulce grande.',
                ],
            );

            $design = EventDesign::firstOrCreate(
                ['event_id' => $event->id],
                [
                    'theme' => 'Bosque encantado',
                    // As cores da própria marca, para o ecrã parecer o site.
                    'palette' => ['#f4dbe1', '#c96f86', '#ebd9b4', '#a8823c'],
                    'notes' => "Mesa dulce con follaje y tonos empolvados.\n\n".
                               'Photocall floral en la entrada del pazo, con luz cálida.',
                    'inspiration' => ['https://www.instagram.com/fiestas_magicas_en_galicia/'],
                    'checklist' => [
                        ['step' => 'Cargar la furgoneta la noche antes', 'done' => true],
                        ['step' => 'Montar el photocall antes de que llegue el catering', 'done' => false],
                        ['step' => 'Colocar la mesa dulce a las 12:00', 'done' => false],
                        ['step' => 'Recoger a las 22:00', 'done' => false],
                    ],
                ],
            );

            // O material vem do catálogo real, se lá estiver. Não se inventam
            // peças: se o CatalogSeeder ainda não correu, o projeto fica sem
            // material e diz-se isso no fim.
            $skus = ['SILLAS-TIFFANY' => 40, 'PHOTOCALL-FLORAL' => 1, 'SOPORTES-MESA' => 3];
            $position = 0;

            foreach ($skus as $sku => $quantity) {
                $item = Item::where('sku', $sku)->first();

                if ($item === null) {
                    continue;
                }

                $design->items()->firstOrCreate(
                    ['item_id' => $item->id],
                    ['quantity' => $quantity, 'position' => $position++],
                );
            }

            /*
            | Um trabalho publicado, para o portefólio não estar vazio.
            |
            | Leva consentimento com data porque a base de dados recusa
            | publicar sem ele — e é bom que o exemplo mostre a regra em
            | vez de a contornar. Sem fotos: entra a imagem de exemplo, e
            | a página serve para ver o desenho, não para enganar ninguém.
            */
            //
            // `first()` e depois `create()`, e nao `firstOrCreate()`: o
            // `firstOrCreate` junta as chaves da procura aos valores da
            // criação, e uma chave com caminho JSON — `slug->es` — não é
            // um atributo que se possa atribuir. Ou era descartada em
            // silêncio, ou rebentava, conforme a configuração.
            $existe = Project::query()->where('slug->es', 'demo-comunion-pazo')->exists();

            if (! $existe) {
                Project::create([
                    'title' => [
                        'es' => self::MARK.' Comunión en el Pazo de Mos',
                        'gl' => self::MARK.' Comuñón no Pazo de Mos',
                        'pt' => self::MARK.' Comunhão no Pazo de Mos',
                    ],
                    'slug' => [
                        'es' => 'demo-comunion-pazo',
                        'gl' => 'demo-comunion-pazo-gl',
                        'pt' => 'demo-comunhao-pazo',
                    ],
                    'description' => [
                        'es' => "Mesa dulce con follaje y tonos empolvados, y un photocall floral en la entrada.\n\n".
                                'Montamos por la mañana, antes de que llegara el catering, y recogimos a las diez.',
                    ],
                    'event_type' => 'comunion',
                    'happened_on' => now()->subMonths(2)->toDateString(),
                    'venue' => 'Pazo de Mos',
                    'city' => 'Nigrán',
                    'guests_count' => 60,
                    'is_featured' => true,
                    'is_published' => true,
                    'published_at' => now()->subMonths(2),
                    'consent_at' => now()->subMonths(2),
                ]);
            }

            Lead::firstOrCreate(
                ['email' => 'demo-lead@fiestasmagicas.test'],
                [
                    'name' => self::MARK.' Carmen Vázquez',
                    'phone' => '+34600111111',
                    'locale' => 'gl',
                    'event_type' => 'boda',
                    'event_date' => now()->addMonths(7)->toDateString(),
                    'guests_count' => 120,
                    'venue' => 'Finca en Gondomar',
                    'message' => 'Queremos arco floral e photocall. Orzamento aproximado?',
                    'status' => LeadStatus::New,
                    'utm_source' => 'instagram',
                    'utm_medium' => 'bio',
                ],
            );
        });

        $sinMaterial = EventDesign::query()
            ->whereHas('event', fn ($q) => $q->where('reference', self::REF))
            ->withCount('items')
            ->first()?->items_count === 0;

        $this->info('Datos de demostración creados.');
        $this->newLine();
        $this->line('  Solicitudes  → una petición sin responder');
        $this->line('  Eventos      → '.self::REF.', dentro de 18 días');
        $this->line('  Proyectos    → "Bosque encantado", con paleta y montaje');
        $this->line('  Trabajos     → uno publicado, visible en /es/trabajos');
        $this->newLine();

        if ($sinMaterial) {
            $this->warn('El proyecto quedó sin material: falta el catálogo.');
            $this->line('  php artisan db:seed --class=CatalogSeeder');
            $this->newLine();
        }

        $this->line('Para borrarlo todo:  php artisan fiestas:demo --limpiar');

        return self::SUCCESS;
    }

    private function limpiar(): int
    {
        DB::transaction(function (): void {
            // O evento leva o desenho atrás (ON DELETE CASCADE), e é por isso
            // que aqui não é preciso apagar o desenho à mão.
            Event::withTrashed()->where('reference', self::REF)->forceDelete();
            Lead::where('email', 'demo-lead@fiestasmagicas.test')->delete();
            Project::where('slug->es', 'demo-comunion-pazo')->delete();
            Client::withTrashed()->where('email', 'demo@fiestasmagicas.test')->forceDelete();
        });

        $this->info('Datos de demostración borrados.');

        return self::SUCCESS;
    }
}
