<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importa um lote de fotos de festas para o portefólio, como rascunho.
 *
 * As fotos vivem em `database/seed-assets/portfolio/<slug>/` (comitadas no
 * git, para chegarem a qualquer clone). Este comando copia-as para o disco
 * `public` — a mesma pasta `trabajos` que o FileUpload do Filament usa — e
 * cria um `Project` por evento, sempre com `is_published = false`.
 *
 * Fica de propósito sem `consent_at`: a base de dados recusa publicar sem
 * ele (`projects_publish_chk`), e a autorização de cada cliente para usar
 * as fotos da própria festa é uma decisão da Sol, não deste script. Depois
 * de correr, os rascunhos aparecem em /admin/projects para ela rever,
 * traduzir, e só então publicar um a um.
 *
 * Idempotente: corre `where('slug->es', ...)->exists()` antes de criar,
 * pelo mesmo motivo do SeedDemoData — `firstOrCreate` com uma chave de
 * caminho JSON (`slug->es`) não é um atributo atribuível.
 */
class ImportPortfolioPhotos extends Command
{
    protected $signature = 'fiestas:importar-fotos {--limpiar : Borra los proyectos importados por este comando}';

    protected $description = 'Importa las fotos de database/seed-assets/portfolio/ como proyectos en borrador';

    private const MARK = '[IMPORTADO]';

    /**
     * Um evento por pasta. `event_type` é o melhor palpite a partir da
     * decoração — a Sol confirma ou corrige no formulário.
     *
     * @var array<string, array{es: string, gl: string, pt: string, event_type: string, files: list<string>}>
     */
    private const EVENTOS = [
        'dinossauros' => [
            'es' => 'Cumpleaños de dinosaurios',
            'gl' => 'Aniversario de dinosauros',
            'pt' => 'Aniversário de dinossauros',
            'event_type' => 'cumpleanos_infantil',
            'files' => ['geral.jpg', 'detalhe.jpg'],
        ],
        'gabrielly-paris' => [
            'es' => 'Gabrielly, un año en París',
            'gl' => 'Gabrielly, un ano en París',
            'pt' => 'Gabrielly, um aninho em Paris',
            'event_type' => 'cumpleanos_infantil',
            'files' => ['geral.jpg'],
        ],
        'quinceanera-paris' => [
            'es' => 'Quince años con temática de París',
            'gl' => 'Quince anos con temática de París',
            'pt' => 'Quinze anos com temática de Paris',
            'event_type' => 'cumpleanos',
            'files' => ['numero.jpg'],
        ],
        'bautizo' => [
            'es' => 'Bautizo en blanco y dorado',
            'gl' => 'Bautizo en branco e dourado',
            'pt' => 'Batizado em branco e dourado',
            'event_type' => 'bautizo',
            'files' => ['geral.jpg'],
        ],
        'palavrinhas' => [
            'es' => 'Primer año, tema Palavrinhas',
            'gl' => 'Primeiro ano, tema Palavrinhas',
            'pt' => 'Primeiro aninho, tema Palavrinhas',
            'event_type' => 'cumpleanos_infantil',
            'files' => ['geral.jpg'],
        ],
        'borboletas' => [
            'es' => 'Primer año, tema mariposas',
            'gl' => 'Primeiro ano, tema bolboretas',
            'pt' => 'Primeiro aninho, tema borboletas',
            'event_type' => 'cumpleanos_infantil',
            'files' => ['geral.jpg'],
        ],
        'masha-urso' => [
            'es' => 'Cumpleaños de Masha y el Oso',
            'gl' => 'Aniversario de Masha e o Oso',
            'pt' => 'Aniversário da Masha e o Urso',
            'event_type' => 'cumpleanos_infantil',
            'files' => ['geral.jpg'],
        ],
        'abelhinha' => [
            'es' => 'Tema abejita y miel',
            'gl' => 'Temática abella e mel',
            'pt' => 'Tema abelhinha e mel',
            'event_type' => 'cumpleanos_infantil',
            'files' => ['geral.jpg'],
        ],
        'mickey' => [
            'es' => 'Cumpleaños de Mickey Mouse',
            'gl' => 'Aniversario de Mickey Mouse',
            'pt' => 'Aniversário do Mickey Mouse',
            'event_type' => 'cumpleanos_infantil',
            'files' => ['geral.jpg'],
        ],
    ];

    public function handle(): int
    {
        return $this->option('limpiar') ? $this->limpiar() : $this->importar();
    }

    private function importar(): int
    {
        $origem = database_path('seed-assets/portfolio');

        if (! is_dir($origem)) {
            $this->error("No existe {$origem}.");

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $criados = 0;
        $saltados = 0;

        DB::transaction(function () use ($origem, $disk, &$criados, &$saltados): void {
            foreach (self::EVENTOS as $slug => $dados) {
                $slugEs = "importado-{$slug}";

                if (Project::query()->where('slug->es', $slugEs)->exists()) {
                    $saltados++;

                    continue;
                }

                $pastaOrigem = "{$origem}/{$slug}";
                $photos = [];

                foreach ($dados['files'] as $ficheiro) {
                    $caminhoOrigem = "{$pastaOrigem}/{$ficheiro}";

                    if (! is_file($caminhoOrigem)) {
                        $this->warn("Falta {$caminhoOrigem}, a saltar esse ficheiro.");

                        continue;
                    }

                    $nomeDestino = Str::uuid()->toString().'.jpg';
                    $caminhoDestino = "trabajos/{$slug}/{$nomeDestino}";
                    $disk->put($caminhoDestino, file_get_contents($caminhoOrigem));
                    $photos[] = $caminhoDestino;
                }

                if ($photos === []) {
                    $this->warn("Nenhuma foto para {$slug}, projeto não criado.");

                    continue;
                }

                Project::create([
                    'title' => [
                        'es' => self::MARK.' '.$dados['es'],
                        'gl' => self::MARK.' '.$dados['gl'],
                        'pt' => self::MARK.' '.$dados['pt'],
                    ],
                    'slug' => [
                        'es' => $slugEs,
                        'gl' => "importado-{$slug}-gl",
                        'pt' => "importado-{$slug}-pt",
                    ],
                    'event_type' => $dados['event_type'],
                    'is_featured' => false,
                    'is_published' => false,
                    'photos' => $photos,
                ]);

                $criados++;
            }
        });

        $this->info("Proyectos creados: {$criados}. Ya existían: {$saltados}.");
        $this->newLine();
        $this->line('Todos en borrador (/admin -> Portfolio (web)). Antes de publicar cada uno:');
        $this->line('  - revisar/traducir título y añadir descripción;');
        $this->line('  - poner fecha, lugar, ciudad;');
        $this->line('  - confirmar que hay autorización del cliente y solo entonces marcar consentimiento y publicar.');

        return self::SUCCESS;
    }

    private function limpiar(): int
    {
        $disk = Storage::disk('public');
        $borrados = 0;

        DB::transaction(function () use ($disk, &$borrados): void {
            foreach (array_keys(self::EVENTOS) as $slug) {
                $project = Project::query()->where('slug->es', "importado-{$slug}")->first();

                if ($project === null) {
                    continue;
                }

                foreach ($project->photos ?? [] as $path) {
                    if (is_string($path)) {
                        $disk->delete($path);
                    }
                }

                $project->delete();
                $borrados++;
            }
        });

        $this->info("Proyectos borrados: {$borrados}.");

        return self::SUCCESS;
    }
}
