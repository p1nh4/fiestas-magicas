"""lang/{es,gl,pt}/mails.php — com a mesma verificacao de paridade de chaves.

Os emails que o cliente recebe. O aviso interno para a Sol NAO esta aqui:
vai sempre em espanhol, porque e ela que o le, e traduzi-lo para tres
idiomas seria trabalho a mais para ninguem.
"""
import pathlib
import sys

OUT = pathlib.Path('lang')

M = {}

M['es'] = {
    'greeting': 'Hola :name,',
    'signature': 'Un abrazo,',
    'signed_by': ':owner · :business',

    'lead': {
        'subject': 'Hemos recibido tu petición',
        'preview': 'Te contestamos en cuanto veamos bien las fechas.',
        'body': 'Gracias por escribirnos. Ya tenemos tu petición y le echamos un vistazo en cuanto podamos.',
        'summary_title': 'Esto es lo que nos has contado',
        'type': 'Celebración',
        'date': 'Fecha',
        'guests': 'Invitados',
        'venue': 'Lugar',
        'no_date': 'todavía sin fecha',
        'next': 'Si mientras tanto te surge algo, contéstanos a este correo o escríbenos por WhatsApp: es lo más rápido.',
        'wa': 'Escribir por WhatsApp',
    },

    'quote': {
        'subject': 'Tu presupuesto para :event',
        'preview': 'Ábrelo cuando quieras: el enlace es solo tuyo.',
        'body': 'Aquí tienes el presupuesto de tu fiesta. Ábrelo con el botón; no hace falta crear ninguna cuenta.',
        'cta': 'Ver el presupuesto',
        'total': 'Total',
        'deposit': 'Señal para reservar la fecha',
        'valid': 'Válido hasta el :date',
        'note': 'Si algo no te encaja, dínoslo y te preparamos otra propuesta. No hay ningún compromiso hasta que lo aceptes.',
        'link_warning': 'Este enlace es personal. Cualquiera que lo tenga puede ver y aceptar el presupuesto, así que no lo reenvíes a quien no deba.',
    },

    'deposit': {
        'subject': 'Señal recibida — la fecha es tuya',
        'preview': 'Ya está reservado. Nos vemos en la fiesta.',
        'body': 'Hemos recibido la señal. La fecha y el material quedan reservados a tu nombre.',
        'amount': 'Señal recibida',
        'pending': 'Queda por pagar',
        'when': 'El resto se paga el día del evento.',
        'next': 'Unos días antes te escribimos para cerrar los últimos detalles: horas de montaje, accesos y quién nos abre la puerta.',
    },
    'reminder': {
        'subject': 'Ya casi: :event',
        'preview': 'Repasamos los últimos detalles.',
        'body': 'Quedan :days días. Te escribimos para repasar los últimos detalles y que no quede nada al aire.',
        'when': 'La fiesta empieza',
        'setup': 'Llegamos a montar',
        'where': 'Dónde',
        'pending': 'Queda por pagar',
        'ask_title': 'Tres cosas que nos ayudan mucho a saber de antemano:',
        'ask_access': '¿Cómo entramos? ¿Hay que avisar a alguien, hay escaleras, se puede aparcar cerca?',
        'ask_time': '¿A qué hora podemos empezar a montar?',
        'ask_contact': '¿Quién estará allí ese día, por si no te localizamos a ti?',
        'wa': 'Contestar por WhatsApp',
    },
}

M['gl'] = {
    'greeting': 'Ola :name,',
    'signature': 'Unha aperta,',
    'signed_by': ':owner · :business',

    'lead': {
        'subject': 'Recibimos a túa petición',
        'preview': 'Contestámosche en canto vexamos ben as datas.',
        'body': 'Grazas por escribirnos. Xa temos a túa petición e botámoslle unha ollada en canto poidamos.',
        'summary_title': 'Isto é o que nos contaches',
        'type': 'Celebración',
        'date': 'Data',
        'guests': 'Convidados',
        'venue': 'Lugar',
        'no_date': 'aínda sen data',
        'next': 'Se mentres tanto che xorde algo, contéstanos a este correo ou escríbenos por WhatsApp: é o máis rápido.',
        'wa': 'Escribir por WhatsApp',
    },

    'quote': {
        'subject': 'O teu orzamento para :event',
        'preview': 'Ábreo cando queiras: a ligazón é só túa.',
        'body': 'Aquí tes o orzamento da túa festa. Ábreo co botón; non fai falta crear ningunha conta.',
        'cta': 'Ver o orzamento',
        'total': 'Total',
        'deposit': 'Sinal para reservar a data',
        'valid': 'Válido ata o :date',
        'note': 'Se algo non che encaixa, dinos e preparámosche outra proposta. Non hai ningún compromiso ata que o aceptes.',
        'link_warning': 'Esta ligazón é persoal. Calquera que a teña pode ver e aceptar o orzamento, así que non a reenvíes a quen non deba.',
    },

    'deposit': {
        'subject': 'Sinal recibido — a data é túa',
        'preview': 'Xa está reservado. Vémonos na festa.',
        'body': 'Recibimos o sinal. A data e o material quedan reservados no teu nome.',
        'amount': 'Sinal recibido',
        'pending': 'Queda por pagar',
        'when': 'O resto págase o día do evento.',
        'next': 'Uns días antes escribímosche para pechar os últimos detalles: horas de montaxe, accesos e quen nos abre a porta.',
    },
    'reminder': {
        'subject': 'Xa case: :event',
        'preview': 'Repasamos os últimos detalles.',
        'body': 'Quedan :days días. Escribímosche para repasar os últimos detalles e que non quede nada no aire.',
        'when': 'A festa empeza',
        'setup': 'Chegamos a montar',
        'where': 'Onde',
        'pending': 'Queda por pagar',
        'ask_title': 'Tres cousas que nos axudan moito a saber de antemán:',
        'ask_access': 'Como entramos? Hai que avisar a alguén, hai escaleiras, pódese aparcar preto?',
        'ask_time': 'A que hora podemos empezar a montar?',
        'ask_contact': 'Quen estará alí ese día, por se non te localizamos a ti?',
        'wa': 'Contestar por WhatsApp',
    },
}

M['pt'] = {
    'greeting': 'Olá :name,',
    'signature': 'Um abraço,',
    'signed_by': ':owner · :business',

    'lead': {
        'subject': 'Recebemos o teu pedido',
        'preview': 'Respondemos assim que virmos bem as datas.',
        'body': 'Obrigada por nos escreveres. Já temos o teu pedido e vamos vê-lo assim que pudermos.',
        'summary_title': 'Isto foi o que nos contaste',
        'type': 'Celebração',
        'date': 'Data',
        'guests': 'Convidados',
        'venue': 'Local',
        'no_date': 'ainda sem data',
        'next': 'Se entretanto te surgir alguma coisa, responde a este email ou escreve por WhatsApp: é o mais rápido.',
        'wa': 'Escrever por WhatsApp',
    },

    'quote': {
        'subject': 'O teu orçamento para :event',
        'preview': 'Abre quando quiseres: a ligação é só tua.',
        'body': 'Aqui tens o orçamento da tua festa. Abre no botão; não é preciso criar conta nenhuma.',
        'cta': 'Ver o orçamento',
        'total': 'Total',
        'deposit': 'Sinal para reservar a data',
        'valid': 'Válido até :date',
        'note': 'Se alguma coisa não te servir, diz-nos e preparamos outra proposta. Não há compromisso nenhum até aceitares.',
        'link_warning': 'Esta ligação é pessoal. Quem a tiver pode ver e aceitar o orçamento, por isso não a reencaminhes a quem não deve.',
    },

    'deposit': {
        'subject': 'Sinal recebido — a data é tua',
        'preview': 'Está reservado. Até à festa.',
        'body': 'Recebemos o sinal. A data e o material ficam reservados em teu nome.',
        'amount': 'Sinal recebido',
        'pending': 'Falta pagar',
        'when': 'O resto paga-se no dia do evento.',
        'next': 'Uns dias antes escrevemos para fechar os últimos pormenores: horas de montagem, acessos e quem nos abre a porta.',
    },
    'reminder': {
        'subject': 'Está quase: :event',
        'preview': 'Vamos rever os últimos pormenores.',
        'body': 'Faltam :days dias. Escrevemos para rever os últimos pormenores e não ficar nada por combinar.',
        'when': 'A festa começa',
        'setup': 'Chegamos para montar',
        'where': 'Onde',
        'pending': 'Falta pagar',
        'ask_title': 'Três coisas que ajudam muito a saber de antemão:',
        'ask_access': 'Como entramos? É preciso avisar alguém, há escadas, dá para estacionar perto?',
        'ask_time': 'A que horas podemos começar a montar?',
        'ask_contact': 'Quem estará lá nesse dia, caso não te consigamos apanhar?',
        'wa': 'Responder por WhatsApp',
    },
}


def keys(d, p=''):
    out = set()
    for k, v in d.items():
        path = f'{p}{k}'
        out |= keys(v, path + '.') if isinstance(v, dict) else {path}
    return out


ref = keys(M['es'])
problems = []
for loc, tree in M.items():
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


for loc, tree in M.items():
    p = OUT / loc / 'mails.php'
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(
        "<?php\n\ndeclare(strict_types=1);\n\n"
        "/*\n * Emails que o cliente recebe. Gerado por gen_mails_lang.py,\n"
        " * que falha se faltar uma chave num idioma.\n *\n"
        " * O aviso interno para a Sol nao esta aqui: vai sempre em espanhol,\n"
        " * porque e ela que o le.\n */\n\n"
        f"return [\n{php(tree)}\n];\n", encoding='utf-8')

print(f'mails.php ×3 idiomas ({len(ref)} chaves cada) — nenhuma em falta')
