<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Item;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function index(): View
    {
        return view('public.home', [
            'services' => Service::query()
                ->active()
                ->orderBy('position')
                ->get(),

            // Só trabalhos com autorização do cliente — o scope published()
            // e o CHECK da base de dados garantem-no dos dois lados.
            'projects' => Project::query()
                ->published()
                ->limit(8)
                ->get(),

            'rentals' => Item::query()
                ->rentable()
                ->orderBy('sku')
                ->limit(4)
                ->get(),

            'faqs' => Faq::query()
                ->where('is_published', true)
                ->orderBy('position')
                ->get(),

            // Se não houver testemunhos reais e autorizados, a secção
            // simplesmente não aparece. Nunca se preenche com texto de encher.
            'testimonials' => Testimonial::query()
                ->published()
                ->latest()
                ->limit(3)
                ->get(),
        ]);
    }
}
