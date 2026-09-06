<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Support\Mail\Notifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class LeadController extends Controller
{
    public function __construct(private readonly Notifier $notifier) {}

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = Lead::create([
            ...$request->safe()->only([
                'name', 'email', 'phone', 'event_type',
                'event_date', 'guests_count', 'venue', 'message',
            ]),
            'locale' => app()->getLocale(),
            'status' => LeadStatus::New,

            // De onde veio este pedido. Sem isto não se sabe se vale a pena
            // pagar publicidade ou se o Instagram é que traz os clientes.
            'utm_source' => $request->string('utm_source')->limit(80)->value() ?: null,
            'utm_medium' => $request->string('utm_medium')->limit(80)->value() ?: null,
            'utm_campaign' => $request->string('utm_campaign')->limit(120)->value() ?: null,
            'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 400) ?: null,
            'landing_path' => mb_substr($request->path(), 0, 400),

            // O IP nunca fica em claro. O hash chega para ver que vários
            // pedidos vêm da mesma origem, sem guardar um dado pessoal.
            'ip_hash' => Lead::hashIp($request->ip()),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 400),
        ]);

        // Confirmação para quem escreveu e aviso para a Sol.
        //
        // O Notifier engole qualquer falha e escreve no log: um servidor de
        // email em baixo não pode transformar um pedido de orçamento num
        // erro 500. O pedido já está guardado — o email é o extra.
        $this->notifier->leadReceived($lead);

        return redirect()
            ->route('lead.thanks')
            ->with('lead_reference', $lead->uuid);
    }

    public function thanks(): View|RedirectResponse
    {
        // Sem ter acabado de enviar, esta página não faz sentido.
        if (! session()->has('lead_reference')) {
            return redirect()->route('home');
        }

        return view('public.thanks');
    }
}
