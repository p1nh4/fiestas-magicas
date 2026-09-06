<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Item;
use App\Support\Availability\AvailabilityService;
use App\Support\Locales;
use App\Support\Period;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * O catálogo de aluguer visto pelo cliente.
 *
 * Uma coisa é preciso dizer com todas as letras, e a página di-lo: consultar
 * disponibilidade **não reserva nada**. Quem reserva é o orçamento aceite —
 * é aí que a transação corre, o trigger decide e as unidades ficam mesmo
 * presas.
 *
 * Podia ter-se feito um carrinho que segura o material durante uns minutos
 * (o esquema já tem `hold_expires_at` para isso). Não se fez ainda, e por
 * isso não se finge que se fez: uma pessoa que sai daqui convencida de que
 * a peça está guardada e depois descobre que não, fica pior do que se nunca
 * tivesse consultado.
 *
 * A consulta vai em GET, na barra de endereços, de propósito: assim a
 * pessoa pode guardar o link ou mandá-lo a alguém, e não há nada de pessoal
 * lá dentro — só duas datas.
 */
final class RentalController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function index(): View
    {
        return view('public.rentals.index', [
            'items' => Item::query()->rentable()->orderBy('sku')->get(),
        ]);
    }

    public function show(Request $request, string $locale, string $slug): View
    {
        $item = Item::query()
            ->rentable()
            ->where("slug->{$locale}", $slug)
            ->first();

        if ($item === null) {
            throw new NotFoundHttpException;
        }

        // Cada peça tem endereço próprio em cada idioma ("sillas-tiffany",
        // "cadeiras-tiffany"). Trocar só o prefixo dava um 404 — e um
        // hreflang que aponta a um 404 faz o Google descartar o grupo todo.
        Locales::useAlternates(Locales::alternatesFor('rentals.show', $item));

        return view('public.rentals.show', [
            'item' => $item,
            'query' => $this->window($request),
            'result' => $this->check($request, $item),
        ]);
    }

    /**
     * As datas pedidas, tal como vieram — para voltarem a aparecer no
     * formulário depois de recarregar a página.
     *
     * @return array{desde: ?string, hasta: ?string, cantidad: int}
     */
    private function window(Request $request): array
    {
        return [
            'desde' => $request->string('desde')->limit(16)->value() ?: null,
            'hasta' => $request->string('hasta')->limit(16)->value() ?: null,
            'cantidad' => max(1, min(999, (int) $request->integer('cantidad', 1))),
        ];
    }

    /**
     * O resultado da consulta, ou null se ainda não perguntaram nada.
     *
     * @return array{free: int, wanted: int, enough: bool, from: CarbonImmutable, to: CarbonImmutable}|array{error: string}|null
     */
    private function check(Request $request, Item $item): ?array
    {
        $q = $this->window($request);

        if ($q['desde'] === null || $q['hasta'] === null) {
            return null;
        }

        try {
            $from = CarbonImmutable::parse($q['desde'])->startOfDay();
            $to = CarbonImmutable::parse($q['hasta'])->endOfDay();
        } catch (\Throwable) {
            // Datas escritas à mão na barra de endereços. Não é erro do
            // utilizador comum, mas também não é motivo para uma página 500.
            return ['error' => __('rentals.errors.dates')];
        }

        if ($to <= $from) {
            return ['error' => __('rentals.errors.order')];
        }

        if ($from->isPast() && ! $from->isToday()) {
            return ['error' => __('rentals.errors.past')];
        }

        // Um ano à frente chega e sobra. Sem limite, um pedido com
        // `desde=2020&hasta=2200` punha o cálculo a percorrer 65 000 dias.
        if ($from->diffInDays($to) > 366) {
            return ['error' => __('rentals.errors.too_long')];
        }

        // A janela realmente bloqueada leva as folgas da peça. É a mesma
        // conta que o trigger faz — não uma aproximação.
        $window = $this->availability->blockedWindow($item, new Period($from, $to));
        $free = $this->availability->availableQuantity($item, $window);

        return [
            'free' => $free,
            'wanted' => $q['cantidad'],
            'enough' => $free >= $q['cantidad'],
            'from' => $from,
            'to' => $to,
        ];
    }
}
