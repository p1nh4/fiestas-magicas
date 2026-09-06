{{-- Botão de email: uma tabela, porque um <a> com padding não é clicável
     todo no Outlook. --}}
@props(['url'])

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0;">
    <tr>
        <td align="center" bgcolor="#a9536a" style="border-radius:999px;">
            <a href="{{ $url }}"
               style="display:inline-block; padding:13px 26px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:999px;">
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>
