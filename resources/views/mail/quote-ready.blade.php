<x-mail.layout :preview="__('mails.quote.preview')">
    <p style="margin:0 0 14px 0;">
        {{ __('mails.greeting', ['name' => $quote->event?->client?->name ?? '']) }}
    </p>

    <p style="margin:0 0 18px 0;">{{ __('mails.quote.body') }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 4px 0; font-size:15px;">
        <tr>
            <td style="padding:5px 0; color:#5c4a50; width:52%;">{{ __('mails.quote.total') }}</td>
            <td style="padding:5px 0; text-align:right; font-weight:600;">
                {{ number_format((float) $quote->total, 2, ',', '.') }} €
            </td>
        </tr>
        <tr>
            <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.quote.deposit') }}</td>
            <td style="padding:5px 0; text-align:right;">
                {{ number_format((float) $quote->depositAmount(), 2, ',', '.') }} €
            </td>
        </tr>
    </table>

    @if ($quote->valid_until)
        <p style="margin:0; font-size:14px; color:#5c4a50;">
            {{ __('mails.quote.valid', ['date' => $quote->valid_until->format('d/m/Y')]) }}
        </p>
    @endif

    <x-mail.button :url="$url">{{ __('mails.quote.cta') }}</x-mail.button>

    <p style="margin:0 0 14px 0;">{{ __('mails.quote.note') }}</p>

    {{-- O link É a credencial: quem o tiver pode aceitar o orçamento. Vale
         mais dizê-lo em duas linhas do que descobrir depois que alguém o
         reencaminhou para o grupo da família. --}}
    <p style="margin:0 0 18px 0; padding:12px 14px; background-color:#faf3e6; border-radius:8px; font-size:14px; color:#5c4a50;">
        {{ __('mails.quote.link_warning') }}
    </p>

    <p style="margin:0;">{{ __('mails.signature') }}</p>
    <p style="margin:2px 0 0 0; color:#5c4a50;">
        {{ __('mails.signed_by', ['owner' => config('business.owner'), 'business' => config('business.name')]) }}
    </p>
</x-mail.layout>
