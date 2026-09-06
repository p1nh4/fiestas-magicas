<x-mail.layout :preview="__('mails.reminder.preview')">
    @php
        $client = $event->client;
        $pending = $event->balanceDue();

        // absolute: true e a partir de HOJE, nao ao contrario. O
        // `$event->starts_at->diffInDays(now())` do Carbon 3 devolve a
        // diferenca com sinal — dava "Faltam -3 dias".
        $days = (int) now()->startOfDay()->diffInDays($event->starts_at->startOfDay(), absolute: true);
    @endphp

    <p style="margin:0 0 14px 0;">{{ __('mails.greeting', ['name' => $client?->name ?? '']) }}</p>

    <p style="margin:0 0 18px 0;">{{ __('mails.reminder.body', ['days' => $days]) }}</p>

    {{-- Qual festa. O assunto ja o diz, mas um email lido no painel de
         pre-visualizacao, ou reencaminhado, so mostra o corpo. --}}
    <p style="margin:0 0 18px 0; font-size:18px; font-weight:600;">{{ $event->title }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 18px 0; font-size:15px;">
        <tr>
            <td style="padding:5px 0; color:#5c4a50; width:38%;">{{ __('mails.reminder.when') }}</td>
            <td style="padding:5px 0;">{{ $event->starts_at->format('d/m/Y H:i') }}</td>
        </tr>
        @if ($event->setup_starts_at)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.reminder.setup') }}</td>
                <td style="padding:5px 0;">{{ $event->setup_starts_at->format('d/m/Y H:i') }}</td>
            </tr>
        @endif
        @if ($event->venue_name || $event->venue_city)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.reminder.where') }}</td>
                <td style="padding:5px 0;">{{ trim($event->venue_name.' '.$event->venue_city) }}</td>
            </tr>
        @endif
        @if (bccomp($pending, '0.00', 2) > 0)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.reminder.pending') }}</td>
                <td style="padding:5px 0;">{{ number_format((float) $pending, 2, ',', '.') }} €</td>
            </tr>
        @endif
    </table>

    {{-- As três perguntas que sempre acabam por surgir na véspera. Perguntá-las
         agora poupa uma chamada às onze da noite. --}}
    <p style="margin:0 0 6px 0;">{{ __('mails.reminder.ask_title') }}</p>
    <ul style="margin:0 0 18px 0; padding-left:20px; color:#5c4a50;">
        <li>{{ __('mails.reminder.ask_access') }}</li>
        <li>{{ __('mails.reminder.ask_time') }}</li>
        <li>{{ __('mails.reminder.ask_contact') }}</li>
    </ul>

    <x-mail.button :url="'https://wa.me/'.config('business.whatsapp')">
        {{ __('mails.reminder.wa') }}
    </x-mail.button>

    <p style="margin:18px 0 0 0;">{{ __('mails.signature') }}</p>
    <p style="margin:2px 0 0 0; color:#5c4a50;">
        {{ __('mails.signed_by', ['owner' => config('business.owner'), 'business' => config('business.name')]) }}
    </p>
</x-mail.layout>
