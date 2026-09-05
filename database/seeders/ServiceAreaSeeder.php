<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ServiceArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * As zonas onde a empresa trabalha.
 *
 * Regra igual à do CatalogSeeder: aqui **não se escreve o texto das
 * páginas**. Semeiam-se só os factos — nome, província, país, distância —
 * e todas ficam em rascunho.
 *
 * Não é preguiça: é a única forma de isto ajudar o site em vez de o
 * prejudicar. Onze páginas geradas a partir do mesmo molde, com o nome do
 * concelho trocado, são doorway pages. O Google não as ignora — desce o
 * domínio inteiro por causa delas. A Sol escreve duas ou três a sério e
 * publica essas; as outras aparecem como texto na lista, sem página.
 *
 * As distâncias são aproximadas, medidas por estrada a partir de Baiona.
 * Servem para dar uma ideia a quem lê, não para faturar deslocação — para
 * isso há o campo `distance_km` do evento, preenchido caso a caso.
 */
final class ServiceAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Baiona',           'province' => 'Pontevedra',       'country' => 'ES', 'km' => 0.0,  'min' => 0],
            ['name' => 'Nigrán',           'province' => 'Pontevedra',       'country' => 'ES', 'km' => 7.5,  'min' => 12],
            ['name' => 'Gondomar',         'province' => 'Pontevedra',       'country' => 'ES', 'km' => 11.0, 'min' => 16],
            ['name' => 'Vigo',             'province' => 'Pontevedra',       'country' => 'ES', 'km' => 21.0, 'min' => 26],
            ['name' => 'Tui',              'province' => 'Pontevedra',       'country' => 'ES', 'km' => 30.0, 'min' => 30],
            ['name' => 'A Guarda',         'province' => 'Pontevedra',       'country' => 'ES', 'km' => 30.0, 'min' => 35],
            ['name' => 'O Porriño',        'province' => 'Pontevedra',       'country' => 'ES', 'km' => 28.0, 'min' => 28],
            ['name' => 'Ponteareas',       'province' => 'Pontevedra',       'country' => 'ES', 'km' => 40.0, 'min' => 38],
            ['name' => 'Pontevedra',       'province' => 'Pontevedra',       'country' => 'ES', 'km' => 45.0, 'min' => 40],
            ['name' => 'Valença',          'province' => 'Viana do Castelo', 'country' => 'PT', 'km' => 32.0, 'min' => 33],
            ['name' => 'Viana do Castelo', 'province' => 'Viana do Castelo', 'country' => 'PT', 'km' => 55.0, 'min' => 50],
        ];

        foreach ($areas as $position => $area) {
            // firstOrCreate e NAO updateOrCreate.
            //
            // A diferenca importa: com updateOrCreate, voltar a correr o
            // seeder repunha is_published a false e apagava o endereco que
            // a Sol tivesse corrigido. Uma pagina que ela publicou e
            // escreveu desaparecia do site sem ninguem perceber porque.
            // Este seeder semeia o que falta e nao toca no que ja existe.
            ServiceArea::firstOrCreate(
                ['name' => $area['name']],
                [
                    // O mesmo topónimo nos três idiomas: são nomes próprios.
                    // Onde mude mesmo, a Sol corrige no backoffice — e este
                    // seeder nunca mais lhe mexe.
                    'slug' => array_fill_keys(
                        ['es', 'gl', 'pt'],
                        Str::slug($area['name']),
                    ),
                    'province' => $area['province'],
                    'country' => $area['country'],
                    'distance_km' => $area['km'],
                    'travel_minutes' => $area['min'],
                    'position' => $position,

                    // Nasce sem texto e por publicar. É a Sol que escreve o
                    // que torna cada página diferente das outras — e enquanto
                    // não escrever, a base de dados recusa publicá-la.
                    'is_published' => false,
                ],
            );
        }
    }
}
