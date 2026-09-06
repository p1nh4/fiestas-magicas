{{-- Aviso interno. Sempre em espanhol: é a Sol que o lê. --}}
<x-mail.layout preview="Nueva petición desde la web.">
    <p style="margin:0 0 14px 0; font-size:18px; font-weight:600;">
        {{ $lead->name }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 18px 0; font-size:15px;">
        <tr>
            <td style="padding:5px 0; color:#5c4a50; width:34%;">Celebración</td>
            <td style="padding:5px 0;">{{ $lead->event_type->label() }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0; color:#5c4a50;">Fecha</td>
            <td style="padding:5px 0;">{{ $lead->event_date?->format('d/m/Y') ?? 'sin fecha' }}</td>
        </tr>
        @if ($lead->guests_count)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">Invitados</td>
                <td style="padding:5px 0;">{{ $lead->guests_count }}</td>
            </tr>
        @endif
        @if ($lead->venue)
            <tr>
                <td style="padding:5px 0; color:#5c4a50;">Lugar</td>
                <td style="padding:5px 0;">{{ $lead->venue }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:5px 0; color:#5c4a50;">Teléfono</td>
            <td style="padding:5px 0;">
                @if ($lead->phone)
                    <a href="tel:{{ $lead->phone }}" style="color:#a9536a;">{{ $lead->phone }}</a>
                    &nbsp;·&nbsp;
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $lead->phone) }}" style="color:#a9536a;">WhatsApp</a>
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:5px 0; color:#5c4a50;">Email</td>
            <td style="padding:5px 0;">
                @if ($lead->email)
                    <a href="mailto:{{ $lead->email }}" style="color:#a9536a;">{{ $lead->email }}</a>
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:5px 0; color:#5c4a50;">Idioma</td>
            <td style="padding:5px 0;">{{ $lead->locale->label() }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0; color:#5c4a50;">Origen</td>
            <td style="padding:5px 0;">{{ $lead->utm_source ?: 'directo' }}{{ $lead->utm_medium ? ' · '.$lead->utm_medium : '' }}</td>
        </tr>
    </table>

    @if ($lead->message)
        <p style="margin:0 0 6px 0; font-size:13px; letter-spacing:0.08em; text-transform:uppercase; color:#a8823c;">
            Lo que ha escrito
        </p>
        <p style="margin:0 0 18px 0; padding:12px 14px; background-color:#fcf1f3; border-radius:8px; white-space:pre-line;">{{ $lead->message }}</p>
    @endif

    {{-- Nota: o IP não aparece aqui, e não aparece de propósito. Está
         guardado só em hash (RGPD) e num email não serviria para nada. --}}

    <x-mail.button :url="url('/admin/leads')">Abrir en el panel</x-mail.button>
</x-mail.layout>
