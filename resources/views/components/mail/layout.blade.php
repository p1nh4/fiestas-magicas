{{--
    Esqueleto dos emails.

    Nada de Tailwind aqui: o Gmail, o Outlook e o Apple Mail não carregam
    folhas de estilo externas e vários deitam fora o que estiver dentro de
    <style>. Por isso é tudo em atributos `style` à mão, com tabelas — que é
    feio de escrever e é a única coisa que funciona em todo o lado.

    As cores são as mesmas do site (resources/css/app.css). Escritas aqui
    porque um email não tem acesso às variáveis CSS do site.
--}}
@props(['preview' => null])

<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::hreflang(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('business.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#fdf7f5; color:#2e2126; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif; font-size:16px; line-height:1.6;">

    @if ($preview)
        {{-- Linha de pré-visualização: é o texto que aparece na caixa de
             entrada a seguir ao assunto. Escondida no corpo do email. --}}
        <div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ $preview }}</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fdf7f5;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; background-color:#ffffff; border:1px solid #f4dbe1; border-radius:14px;">

                    <tr>
                        <td style="padding:28px 32px 0 32px;">
                            <p style="margin:0; font-size:12px; letter-spacing:0.12em; text-transform:uppercase; color:#a8823c;">
                                {{ config('business.name') }}
                            </p>
                            <p style="margin:2px 0 0 0; font-size:13px; color:#5c4a50;">
                                {{ config('business.sub') }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 28px 32px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 32px 28px 32px;">
                            <hr style="border:0; border-top:1px solid #f4dbe1; margin:0 0 16px 0;">
                            <p style="margin:0; font-size:13px; color:#5c4a50;">
                                {{ config('business.address.locality') }} ·
                                <a href="tel:{{ config('business.phone') }}" style="color:#a9536a; text-decoration:none;">{{ config('business.phone_display') }}</a>
                            </p>
                            <p style="margin:6px 0 0 0; font-size:13px;">
                                <a href="https://wa.me/{{ config('business.whatsapp') }}" style="color:#a9536a;">WhatsApp</a>
                                @if (config('business.instagram'))
                                    &nbsp;·&nbsp;
                                    <a href="https://instagram.com/{{ config('business.instagram') }}" style="color:#a9536a;">Instagram</a>
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>

                {{--
                    Sem link de cancelar subscrição de propósito: isto são
                    emails transacionais — resposta a um pedido que a pessoa
                    fez — e não publicidade. Pôr "cancelar subscrição" num
                    email destes só ensina a pessoa a marcá-lo como spam.
                    A newsletter, essa, terá o seu.
                --}}

            </td>
        </tr>
    </table>
</body>
</html>
