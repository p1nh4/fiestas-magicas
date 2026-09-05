<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Support\Availability\OutOfStockException;
use App\Support\Payments\PaymentGateway;
use App\Support\Quotes\QuoteAcceptance;
use App\Support\Quotes\QuoteNotAcceptable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * O orcamento visto pelo cliente.
 *
 * Sem login: o acesso e o proprio link, com um token de 64 caracteres. Para
 * uma empresa de uma pessoa e a escolha certa — obrigar uma mae a criar
 * conta para ver um orcamento e a melhor forma de o perder. Em troca, o
 * token nunca aparece em nenhuma listagem e o Quote esconde-o do JSON.
 */
final class QuoteController extends Controller
{
    public function __construct(private readonly QuoteAcceptance $acceptance) {}

    public function show(string $locale, string $token): View
    {
        $quote = $this->find($token);

        $this->acceptance->markViewed($quote);

        return view('public.quote', [
            'quote' => $quote->fresh()->load(['lines', 'event.client']),
        ]);
    }

    public function accept(Request $request, string $locale, string $token): RedirectResponse
    {
        $quote = $this->find($token);

        try {
            $this->acceptance->accept($quote, $request->ip());
        } catch (QuoteNotAcceptable $e) {
            return back()->with('quote_error', $e->forHumans());
        } catch (OutOfStockException $e) {
            // Entre o envio e a aceitação alguém reservou o material. É o
            // caso que a transação existe para apanhar.
            return back()->with('quote_error', $e->forHumans());
        }

        return redirect()->route('quote.show', ['token' => $token])
            ->with('quote_accepted', true);
    }

    public function reject(string $locale, string $token): RedirectResponse
    {
        $quote = $this->find($token);

        try {
            $this->acceptance->reject($quote);
        } catch (QuoteNotAcceptable $e) {
            return back()->with('quote_error', $e->forHumans());
        }

        return redirect()->route('quote.show', ['token' => $token]);
    }

    /** Manda a pessoa para a passarela de pagamento do sinal. */
    public function pay(string $locale, string $token, PaymentGateway $gateway): RedirectResponse
    {
        $quote = $this->find($token);
        $payment = $this->pendingDeposit($quote);

        if ($payment === null) {
            return redirect()->route('quote.show', ['token' => $token]);
        }

        $session = $gateway->checkout(
            $payment,
            route('quote.paid', ['token' => $token]),
            route('quote.show', ['token' => $token]),
        );

        $payment->update([
            'provider' => $gateway->name(),
            'provider_reference' => $session->reference,
        ]);

        return redirect()->away($session->redirectUrl);
    }

    /**
     * Volta da passarela.
     *
     * Nunca se marca como pago por a pessoa ter voltado a esta URL — isso
     * seria dar por pago qualquer um que escrevesse o endereço à mão.
     * Pergunta-se ao fornecedor.
     */
    public function paid(string $locale, string $token, PaymentGateway $gateway): RedirectResponse
    {
        $quote = $this->find($token);
        $payment = $this->pendingDeposit($quote);

        if ($payment?->provider_reference !== null && $gateway->confirm($payment->provider_reference)) {
            $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

            $quote->event->increment('paid_amount', (float) $payment->amount);

            return redirect()->route('quote.show', ['token' => $token])
                ->with('payment_confirmed', true);
        }

        return redirect()->route('quote.show', ['token' => $token])
            ->with('quote_error', __('quotes.errors.payment_pending'));
    }

    private function find(string $token): Quote
    {
        return Quote::query()
            ->where('public_token', $token)
            ->whereNotIn('status', [QuoteStatus::Draft->value])
            ->with('event')
            ->firstOrFail();
    }

    private function pendingDeposit(Quote $quote): ?\App\Models\Payment
    {
        return $quote->event
            ?->payments()
            ->where('kind', \App\Enums\PaymentKind::Deposit->value)
            ->where('status', PaymentStatus::Pending->value)
            ->latest()
            ->first();
    }
}
