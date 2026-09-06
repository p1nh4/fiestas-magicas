<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PageController extends Controller
{
    public function show(string $locale, string $slug): View
    {
        $page = Page::query()
            ->published()
            ->where("slug->{$locale}", $slug)
            ->first();

        if ($page === null) {
            throw new NotFoundHttpException();
        }

        return view('public.page', ['page' => $page]);
    }
}
