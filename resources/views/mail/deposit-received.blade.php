<x-mail.layout :preview="__('mails.deposit.preview')">
    @php
        $event = $payment->event;
        $client = $payment->client ?? $event?->client;
        $pending = $event?->balanceDue();
    @endphp

    <p style="margin:0 0 14px 0;">{{ __('mails.greeting', ['name' => $client?->name ?? '']) }}</p>

    <p style="margin:0 0 18px 0;">{{ __('mails.deposit.body') }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 18px 0; font-size:15px;">
        <tr>
            <td style="padding:5px 0; color:#5c4a50; width:52%;">{{ __('mails.deposit.amount') }}</td>
            <td style="padding:5px 0; text-align:right; font-weight:600;">
                {{ number_format(abs((float) $payment->amount), 2, ',', '.') }} €
            </td>
        </tr>
        @if ($pending !== null && bccomp($pending, '0.00', 2) > 0)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.deposit.pending') }}</td>
                <td style="padding:5px 0; text-align:right;">
                    {{ number_format((float) $pending, 2, ',', '.') }} €
                </td>
            </tr>
        @endif
    </table>

    <p style="margin:0 0 14px 0;">{{ __('mails.deposit.when') }}</p>
    <p style="margin:0 0 18px 0;">{{ __('mails.deposit.next') }}</p>

    <p style="margin:0;">{{ __('mails.signature') }}</p>
    <p style="margin:2px 0 0 0; color:#5c4a50;">
        {{ __('mails.signed_by', ['owner' => config('business.owner'), 'business' => config('business.name')]) }}
    </p>
</x-mail.layout>
