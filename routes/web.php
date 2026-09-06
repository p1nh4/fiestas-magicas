<?php

declare(strict_types=1);

use App\Http\Controllers\AreaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site público
|--------------------------------------------------------------------------
| Tudo vive debaixo de /{locale}. A raiz redireciona para o idioma do
| navegador. Não há uma versão do site sem idioma na URL: duas URLs para a
| mesma página é conteúdo duplicado aos olhos do Google.
*/

Route::get('/', function (Request $request) {
    return redirect()->route('home', ['locale' => Locales::negotiate($request)]);
})->name('root');

// SEO e PWA vivem fora do prefixo de idioma: são um por site, não um por idioma.
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::prefix('{locale}')
    ->middleware(SetLocale::class)
    ->whereIn('locale', Locales::SUPPORTED)
    ->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::post('/presupuesto', [LeadController::class, 'store'])
            ->middleware('throttle:6,1')   // 6 pedidos por minuto por IP
            ->name('lead.store');

        Route::get('/gracias', [LeadController::class, 'thanks'])->name('lead.thanks');

        /*
        | Zonas de trabalho.
        |
        | Ninguém escreve "decoración de fiestas" no Google — escreve
        | "decoración de globos en Nigrán". É para essas buscas que estas
        | páginas existem.
        |
        | O endereço muda com o idioma (o slug está guardado por idioma),
        | por isso a resolução é feita no controlador e não por binding
        | automático: o mesmo concelho tem três URLs, uma por mercado, e
        | cada uma tem de responder no seu.
        */
        Route::get('/zonas', [AreaController::class, 'index'])->name('areas.index');
        Route::get('/zonas/{slug}', [AreaController::class, 'show'])->name('areas.show');

        /*
        | Catálogo de aluguer.
        |
        | A consulta de disponibilidade vai em GET, com as datas na barra de
        | endereços: assim a pessoa pode guardar ou partilhar o link, e não
        | há nada de pessoal lá dentro. Como qualquer GET, não muda nada —
        | consultar não reserva; quem reserva é o orçamento aceite.
        */
        Route::get('/alquiler', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/alquiler/{slug}', [RentalController::class, 'show'])->name('rentals.show');

        /*
        | Orçamento do cliente.
        |
        | Sem login: o token de 64 caracteres na URL é a credencial. Obrigar
        | uma mãe a criar conta para ver um orçamento é a melhor forma de o
        | perder. Em troca o token nunca aparece em listagens nem em JSON, e
        | as ações que mudam estado são POST com CSRF.
        */
        Route::prefix('presupuesto/{token}')
            ->whereAlphaNumeric('token')
            ->group(function () {
                Route::get('/', [QuoteController::class, 'show'])->name('quote.show');
                Route::get('/pagado', [QuoteController::class, 'paid'])->name('quote.paid');

                Route::middleware('throttle:20,1')->group(function () {
                    Route::post('/aceptar', [QuoteController::class, 'accept'])->name('quote.accept');
                    Route::post('/rechazar', [QuoteController::class, 'reject'])->name('quote.reject');
                    Route::post('/pagar', [QuoteController::class, 'pay'])->name('quote.pay');
                });
            });

        /*
        | Páginas de texto: aviso legal, privacidade, cookies.
        |
        | Esta rota apanha tudo o que sobrar dentro do idioma, por isso TEM
        | de ficar em último lugar: registada antes, engolia /zonas e
        | /alquiler. Se um dia se acrescentar uma secção nova, tem de ficar
        | acima desta linha.
        */
        Route::get('/{slug}', [PageController::class, 'show'])
            ->where('slug', '[a-z0-9\\-]+')
            ->name('page.show');

    });
