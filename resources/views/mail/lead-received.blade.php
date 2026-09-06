<x-mail.layout :preview="__('mails.lead.preview')">
    <p style="margin:0 0 14px 0;">{{ __('mails.greeting', ['name' => $lead->name]) }}</p>

    <p style="margin:0 0 18px 0;">{{ __('mails.lead.body') }}</p>

    {{-- O resumo do que a pessoa escreveu. Serve de confirmação: se ela pôs
         a data errada, é aqui que dá por isso — antes de nós. --}}
    <p style="margin:0 0 8px 0; font-size:13px; letter-spacing:0.08em; text-transform:uppercase; color:#a8823c;">
        {{ __('mails.lead.summary_title') }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 20px 0; font-size:15px;">
        <tr>
            <td style="padding:5px 0; color:#5c4a50; width:38%;">{{ __('mails.lead.type') }}</td>
            <td style="padding:5px 0;">{{ $lead->event_type->label() }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.lead.date') }}</td>
            <td style="padding:5px 0;">
                {{ $lead->event_date?->format('d/m/Y') ?? __('mails.lead.no_date') }}
            </td>
        </tr>
        @if ($lead->guests_count)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.lead.guests') }}</td>
                <td style="padding:5px 0;">{{ $lead->guests_count }}</td>
            </tr>
        @endif
        @if ($lead->venue)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">{{ __('mails.lead.venue') }}</td>
                <td style="padding:5px 0;">{{ $lead->venue }}</td>
            </tr>
        @endif
    </table>

    <p style="margin:0 0 4px 0;">{{ __('mails.lead.next') }}</p>

    <x-mail.button :url="'https://wa.me/'.config('business.whatsapp')">
        {{ __('mails.lead.wa') }}
    </x-mail.button>

    <p style="margin:18px 0 0 0;">{{ __('mails.signature') }}</p>
    <p style="margin:2px 0 0 0; color:#5c4a50;">
        {{ __('mails.signed_by', ['owner' => config('business.owner'), 'business' => config('business.name')]) }}
    </p>
</x-mail.layout>
