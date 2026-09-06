<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Payment;
use App\Models\Quote;
use App\Support\Availability\OutOfStockException;
use App\Support\Mail\Notifier;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\SettlePayment;
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
    public function __construct(
        private readonly QuoteAcceptance $acceptance,
        private readonly Notifier $notifier,
    ) {}

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
    public function pay(string $locale, string $token, PaymentGateway $gateway, SettlePayment $settle): RedirectResponse
    {
        $quote = $this->find($token);
        $payment = $this->pendingDeposit($quote);

        if ($payment === null) {
            return redirect()->route('quote.show', ['token' => $token]);
        }

        /*
         * Antes de abrir uma sessao nova, perguntar pela anterior.
         *
         * Cada passagem por aqui SOBRESCREVE o `provider_reference`. Quem
         * pagou no telemovel, fechou o separador e voltou ao link antes de o
         * `payments:reconcile` correr via "pendente", carregava outra vez em
         * Pagar — e a referencia do pagamento que ELA JA TINHA FEITO era
         * apagada. A partir dai ninguem voltava a perguntar por ela: nem esta
         * pagina, nem o reconcile. O dinheiro ficava na conta da Sol e
         * invisivel para o sistema, que e exatamente o buraco que o
         * `SettlePayment` existe para tapar.
         *
         * Perguntar primeiro custa uma chamada e fecha o caso mais comum.
         */
        if ($payment->provider_reference !== null && $settle->settleIfPaid($payment, $gateway)) {
            return redirect()->route('quote.show', ['token' => $token])
                ->with('payment_confirmed', true);
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
    public function paid(string $locale, string $token, PaymentGateway $gateway, SettlePayment $settle): RedirectResponse
    {
        $quote = $this->find($token);
        $payment = $this->pendingDeposit($quote);

        // Marcar, somar ao evento e mandar o recibo passou a viver no
        // `SettlePayment`, numa transação — porque agora há um segundo
        // caminho até aqui (`payments:reconcile`), e a mesma regra escrita
        // em dois sítios é a mesma regra a divergir num deles.
        if ($payment !== null && $settle->settleIfPaid($payment, $gateway)) {
            return redirect()->route('quote.show', ['token' => $token])
                ->with('payment_confirmed', true);
        }

        // Já estar pago também traz aqui — e é bom que traga: quem paga,
        // fecha o separador, e volta ao link depois de o `reconcile` ter
        // corrido, tem de ver que está pago e não uma mensagem de espera.
        if ($quote->event?->payments()->settled()->exists()) {
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

    private function pendingDeposit(Quote $quote): ?Payment
    {
        return $quote->event
            ?->payments()
            ->where('kind', PaymentKind::Deposit->value)
            ->where('status', PaymentStatus::Pending->value)
            ->latest()
            ->first();
    }
}
