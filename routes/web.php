<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
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
    });
