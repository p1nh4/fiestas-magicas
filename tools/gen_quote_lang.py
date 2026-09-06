"""lang/{es,gl,pt}/quotes.php — com a mesma verificacao de paridade de chaves."""
import pathlib, sys

OUT = pathlib.Path('lang')

Q = {}

Q['es'] = {
    'kicker': 'Presupuesto',
    'meta': {'title': 'Tu presupuesto :number'},
    'for': 'Para',
    'date': 'Fecha',
    'venue': 'Lugar',
    'valid_until': 'Válido hasta',
    'concept': 'Concepto',
    'qty': 'Cant.',
    'unit': 'Precio',
    'line_total': 'Total',
    'days': '{1} 1 día|[2,*] :count días',
    'subtotal': 'Subtotal',
    'discount': 'Descuento',
    'tax': 'IVA (:rate %)',
    'total': 'Total',
    'deposit': 'Señal (:pct %)',
    'deposit_for': 'Señal · :event',
    'accept_explainer': 'Al aceptar, reservamos la fecha y el material para tu fiesta. Después te pediremos la señal; el resto se paga el día del evento.',
    'accept': 'Aceptar presupuesto',
    'reject': 'No me encaja',
    'ask': 'Preguntar por WhatsApp',
    'accepted_title': 'Presupuesto aceptado',
    'accepted_body': 'La fecha y el material quedan reservados a tu nombre.',
    'rejected_body': 'Has marcado este presupuesto como no válido. Si cambias de idea o quieres otra propuesta, escríbenos.',
    'expired_body': 'Este presupuesto ya no está disponible. Escríbenos y te preparamos uno nuevo.',
    'pay_explainer': 'Para dejar la fecha bloqueada falta la señal de :amount.',
    'pay': 'Pagar :amount',
    'payment_confirmed': 'Señal recibida. ¡Nos vemos en la fiesta!',
    'all_set': 'Todo listo. Nos ponemos en contacto contigo unos días antes para cerrar los detalles.',
    'version': 'Versión :n',
    'errors': {
        'expired': 'Este presupuesto ha caducado. Escríbenos y te preparamos uno nuevo.',
        'status': 'Este presupuesto ya no está a la espera de respuesta.',
        'payment_pending': 'Todavía no nos consta el pago. Si acabas de pagarlo, espera un momento y vuelve a cargar la página.',
    },
}

Q['gl'] = {
    'kicker': 'Orzamento',
    'meta': {'title': 'O teu orzamento :number'},
    'for': 'Para',
    'date': 'Data',
    'venue': 'Lugar',
    'valid_until': 'Válido ata',
    'concept': 'Concepto',
    'qty': 'Cant.',
    'unit': 'Prezo',
    'line_total': 'Total',
    'days': '{1} 1 día|[2,*] :count días',
    'subtotal': 'Subtotal',
    'discount': 'Desconto',
    'tax': 'IVE (:rate %)',
    'total': 'Total',
    'deposit': 'Sinal (:pct %)',
    'deposit_for': 'Sinal · :event',
    'accept_explainer': 'Ao aceptar, reservamos a data e o material para a túa festa. Despois pedímosche o sinal; o resto págase o día do evento.',
    'accept': 'Aceptar orzamento',
    'reject': 'Non me encaixa',
    'ask': 'Preguntar por WhatsApp',
    'accepted_title': 'Orzamento aceptado',
    'accepted_body': 'A data e o material quedan reservados no teu nome.',
    'rejected_body': 'Marcaches este orzamento como non válido. Se cambias de idea ou queres outra proposta, escríbenos.',
    'expired_body': 'Este orzamento xa non está dispoñible. Escríbenos e preparámosche un novo.',
    'pay_explainer': 'Para deixar a data bloqueada falta o sinal de :amount.',
    'pay': 'Pagar :amount',
    'payment_confirmed': 'Sinal recibido. Vémonos na festa!',
    'all_set': 'Todo listo. Poñémonos en contacto contigo uns días antes para pechar os detalles.',
    'version': 'Versión :n',
    'errors': {
        'expired': 'Este orzamento caducou. Escríbenos e preparámosche un novo.',
        'status': 'Este orzamento xa non está á espera de resposta.',
        'payment_pending': 'Aínda non nos consta o pagamento. Se acabas de pagalo, agarda un momento e recarga a páxina.',
    },
}

Q['pt'] = {
    'kicker': 'Orçamento',
    'meta': {'title': 'O teu orçamento :number'},
    'for': 'Para',
    'date': 'Data',
    'venue': 'Local',
    'valid_until': 'Válido até',
    'concept': 'Descrição',
    'qty': 'Qt.',
    'unit': 'Preço',
    'line_total': 'Total',
    'days': '{1} 1 dia|[2,*] :count dias',
    'subtotal': 'Subtotal',
    'discount': 'Desconto',
    'tax': 'IVA (:rate %)',
    'total': 'Total',
    'deposit': 'Sinal (:pct %)',
    'deposit_for': 'Sinal · :event',
    'accept_explainer': 'Ao aceitar, reservamos a data e o material para a tua festa. Depois pedimos o sinal; o resto paga-se no dia do evento.',
    'accept': 'Aceitar orçamento',
    'reject': 'Não me serve',
    'ask': 'Perguntar por WhatsApp',
    'accepted_title': 'Orçamento aceite',
    'accepted_body': 'A data e o material ficam reservados em teu nome.',
    'rejected_body': 'Marcaste este orçamento como não válido. Se mudares de ideias ou quiseres outra proposta, escreve-nos.',
    'expired_body': 'Este orçamento já não está disponível. Escreve-nos e preparamos um novo.',
    'pay_explainer': 'Para deixar a data bloqueada falta o sinal de :amount.',
    'pay': 'Pagar :amount',
    'payment_confirmed': 'Sinal recebido. Até à festa!',
    'all_set': 'Está tudo tratado. Entramos em contacto uns dias antes para fechar os pormenores.',
    'version': 'Versão :n',
    'errors': {
        'expired': 'Este orçamento expirou. Escreve-nos e preparamos um novo.',
        'status': 'Este orçamento já não está à espera de resposta.',
        'payment_pending': 'Ainda não temos registo do pagamento. Se acabaste de pagar, espera um momento e recarrega a página.',
    },
}


def keys(d, p=''):
    out = set()
    for k, v in d.items():
        path = f'{p}{k}'
        out |= keys(v, path + '.') if isinstance(v, dict) else {path}
    return out


ref = keys(Q['es'])
problems = []
for loc, tree in Q.items():
    got = keys(tree)
    problems += [f'falta {loc}.{k}' for k in sorted(ref - got)]
    problems += [f'{loc}.{k} a mais' for k in sorted(got - ref)]

if problems:
    print('PROBLEMAS:')
    for p in problems:
        print('  -', p)
    sys.exit(1)


def php(d, indent=1):
    pad = '    ' * indent
    lines = []
    for k, v in d.items():
        if isinstance(v, dict):
            lines.append(f"{pad}'{k}' => [\n{php(v, indent + 1)}\n{pad}],")
        else:
            esc = v.replace('\\', '\\\\').replace("'", "\\'")
            lines.append(f"{pad}'{k}' => '{esc}',")
    return '\n'.join(lines)


for loc, tree in Q.items():
    p = OUT / loc / 'quotes.php'
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(
        "<?php\n\ndeclare(strict_types=1);\n\n"
        "/*\n * Texto do orçamento visto pelo cliente. Gerado por\n"
        " * gen_quote_lang.py, que falha se faltar uma chave num idioma.\n */\n\n"
        f"return [\n{php(tree)}\n];\n", encoding='utf-8')

print(f'quotes.php ×3 idiomas ({len(ref)} chaves cada) — nenhuma em falta')
