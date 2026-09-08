"""Escreve lang/{es,gl,pt}/enums.php e availability.php,
e verifica que nenhum valor de enum fica sem traducao em nenhum idioma."""
import json, pathlib, sys

OUT = pathlib.Path('build/lang')
VALUES = json.loads(pathlib.Path('build/_enum_values.json').read_text(encoding='utf-8'))

T = {
 'es': {
  'client_kind': {'person': 'Particular', 'company': 'Empresa'},
  'lead_status': {'new': 'Nuevo', 'contacted': 'Contactado', 'quoted': 'Presupuestado',
                  'won': 'Ganado', 'lost': 'Perdido', 'spam': 'Spam'},
  'event_status': {'draft': 'Borrador', 'quoted': 'Presupuestado', 'confirmed': 'Confirmado',
                   'in_progress': 'En curso', 'done': 'Finalizado', 'cancelled': 'Cancelado'},
  'quote_status': {'draft': 'Borrador', 'sent': 'Enviado', 'viewed': 'Visto',
                   'accepted': 'Aceptado', 'rejected': 'Rechazado', 'expired': 'Caducado'},
  'reservation_status': {'hold': 'Reserva temporal', 'confirmed': 'Confirmada', 'cancelled': 'Cancelada'},
  'payment_kind': {'deposit': 'Señal', 'balance': 'Resto', 'extra': 'Extra', 'refund': 'Devolución'},
  'payment_method': {'card': 'Tarjeta', 'bizum': 'Bizum', 'transfer': 'Transferencia',
                     'cash': 'Efectivo', 'other': 'Otro'},
  'payment_status': {'pending': 'Pendiente', 'paid': 'Pagado', 'failed': 'Fallido', 'refunded': 'Devuelto'},
  'document_type': {'quote': 'Presupuesto', 'proforma': 'Proforma', 'receipt': 'Recibo',
                    'invoice': 'Factura', 'credit_note': 'Abono'},
  'price_mode': {'fixed': 'Precio fijo', 'per_guest': 'Por invitado', 'per_hour': 'Por hora',
                 'quote': 'A presupuestar'},
  'category_kind': {'service': 'Servicio', 'item': 'Material'},
  'locale': {'es': 'Español', 'gl': 'Galego', 'pt': 'Português'},
  'event_type': {'cumpleanos': 'Cumpleaños', 'cumpleanos_infantil': 'Cumpleaños infantil',
                 'bautizo': 'Bautizo', 'comunion': 'Comunión', 'boda': 'Boda',
                 'baby_shower': 'Baby shower', 'empresa': 'Evento de empresa', 'otro': 'Otro'},
 },
 'gl': {
  'client_kind': {'person': 'Particular', 'company': 'Empresa'},
  'lead_status': {'new': 'Novo', 'contacted': 'Contactado', 'quoted': 'Orzamentado',
                  'won': 'Gañado', 'lost': 'Perdido', 'spam': 'Spam'},
  'event_status': {'draft': 'Borrador', 'quoted': 'Orzamentado', 'confirmed': 'Confirmado',
                   'in_progress': 'En curso', 'done': 'Rematado', 'cancelled': 'Cancelado'},
  'quote_status': {'draft': 'Borrador', 'sent': 'Enviado', 'viewed': 'Visto',
                   'accepted': 'Aceptado', 'rejected': 'Rexeitado', 'expired': 'Caducado'},
  'reservation_status': {'hold': 'Reserva temporal', 'confirmed': 'Confirmada', 'cancelled': 'Cancelada'},
  'payment_kind': {'deposit': 'Sinal', 'balance': 'Resto', 'extra': 'Extra', 'refund': 'Devolución'},
  'payment_method': {'card': 'Tarxeta', 'bizum': 'Bizum', 'transfer': 'Transferencia',
                     'cash': 'Efectivo', 'other': 'Outro'},
  'payment_status': {'pending': 'Pendente', 'paid': 'Pagado', 'failed': 'Fallido', 'refunded': 'Devolto'},
  'document_type': {'quote': 'Orzamento', 'proforma': 'Proforma', 'receipt': 'Recibo',
                    'invoice': 'Factura', 'credit_note': 'Abono'},
  'price_mode': {'fixed': 'Prezo fixo', 'per_guest': 'Por convidado', 'per_hour': 'Por hora',
                 'quote': 'A orzamentar'},
  'category_kind': {'service': 'Servizo', 'item': 'Material'},
  'locale': {'es': 'Castelán', 'gl': 'Galego', 'pt': 'Portugués'},
  'event_type': {'cumpleanos': 'Aniversario', 'cumpleanos_infantil': 'Aniversario infantil',
                 'bautizo': 'Bautizo', 'comunion': 'Comuñón', 'boda': 'Voda',
                 'baby_shower': 'Baby shower', 'empresa': 'Evento de empresa', 'otro': 'Outro'},
 },
 'pt': {
  'client_kind': {'person': 'Particular', 'company': 'Empresa'},
  'lead_status': {'new': 'Novo', 'contacted': 'Contactado', 'quoted': 'Orçamentado',
                  'won': 'Ganho', 'lost': 'Perdido', 'spam': 'Spam'},
  'event_status': {'draft': 'Rascunho', 'quoted': 'Orçamentado', 'confirmed': 'Confirmado',
                   'in_progress': 'Em curso', 'done': 'Concluído', 'cancelled': 'Cancelado'},
  'quote_status': {'draft': 'Rascunho', 'sent': 'Enviado', 'viewed': 'Visto',
                   'accepted': 'Aceite', 'rejected': 'Recusado', 'expired': 'Expirado'},
  'reservation_status': {'hold': 'Reserva temporária', 'confirmed': 'Confirmada', 'cancelled': 'Cancelada'},
  'payment_kind': {'deposit': 'Sinal', 'balance': 'Restante', 'extra': 'Extra', 'refund': 'Reembolso'},
  'payment_method': {'card': 'Cartão', 'bizum': 'Bizum', 'transfer': 'Transferência',
                     'cash': 'Numerário', 'other': 'Outro'},
  'payment_status': {'pending': 'Pendente', 'paid': 'Pago', 'failed': 'Falhou', 'refunded': 'Reembolsado'},
  'document_type': {'quote': 'Orçamento', 'proforma': 'Proforma', 'receipt': 'Recibo',
                    'invoice': 'Fatura', 'credit_note': 'Nota de crédito'},
  'price_mode': {'fixed': 'Preço fixo', 'per_guest': 'Por convidado', 'per_hour': 'Por hora',
                 'quote': 'Sob orçamento'},
  'category_kind': {'service': 'Serviço', 'item': 'Material'},
  'locale': {'es': 'Espanhol', 'gl': 'Galego', 'pt': 'Português'},
  'event_type': {'cumpleanos': 'Aniversário', 'cumpleanos_infantil': 'Aniversário infantil',
                 'bautizo': 'Batizado', 'comunion': 'Comunhão', 'boda': 'Casamento',
                 'baby_shower': 'Chá de bebé', 'empresa': 'Evento de empresa', 'otro': 'Outro'},
 },
}

AVAILABILITY = {
 'es': {
   'out_of_stock': ('{0}No queda ninguno disponible de «:item» en esas fechas.'
                    '|{1}Solo queda 1 unidad de «:item» y pediste :requested.'
                    '|[2,*]Solo quedan :available unidades de «:item» y pediste :requested.'),
   'available': '{0}Agotado|{1}Queda 1|[2,*]Quedan :count',
   'unit_day': ':price € / día',
 },
 'gl': {
   'out_of_stock': ('{0}Non queda ningún dispoñible de «:item» nesas datas.'
                    '|{1}Só queda 1 unidade de «:item» e pediches :requested.'
                    '|[2,*]Só quedan :available unidades de «:item» e pediches :requested.'),
   'available': '{0}Esgotado|{1}Queda 1|[2,*]Quedan :count',
   'unit_day': ':price € / día',
 },
 'pt': {
   'out_of_stock': ('{0}Não há nenhum disponível de «:item» nessas datas.'
                    '|{1}Só resta 1 unidade de «:item» e pediste :requested.'
                    '|[2,*]Só restam :available unidades de «:item» e pediste :requested.'),
   'available': '{0}Esgotado|{1}Resta 1|[2,*]Restam :count',
   'unit_day': ':price € / dia',
 },
}


def php_array(data: dict, indent: int = 1) -> str:
    pad = '    ' * indent
    out = []
    for k, v in data.items():
        if isinstance(v, dict):
            out.append(f"{pad}'{k}' => [\n{php_array(v, indent + 1)}\n{pad}],")
        else:
            out.append(f"{pad}'{k}' => '{v.replace(chr(92), chr(92)*2).replace(chr(39), chr(92) + chr(39))}',")
    return '\n'.join(out)


def write(locale: str, name: str, data: dict, note: str) -> None:
    p = OUT / locale / f'{name}.php'
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(
        f"<?php\n\ndeclare(strict_types=1);\n\n/*\n * {note}\n */\n\n"
        f"return [\n{php_array(data)}\n];\n",
        encoding='utf-8')


# ---- verificacao: nenhum valor de enum pode ficar sem traducao
problems = []
for locale, groups in T.items():
    for key, values in VALUES.items():
        if key not in groups:
            problems.append(f'{locale}: falta o grupo "{key}"')
            continue
        for v in values:
            if v not in groups[key]:
                problems.append(f'{locale}: falta a tradução de {key}.{v}')
        for v in groups[key]:
            if v not in values:
                problems.append(f'{locale}: {key}.{v} traduzido mas não existe no enum')

if problems:
    print('TRADUÇÕES EM FALTA:')
    for p in problems:
        print('  -', p)
    sys.exit(1)

for locale in T:
    write(locale, 'enums', T[locale],
          'Etiquetas dos enums. Gerado por gen_lang.py e verificado contra os '
          'valores do schema — nenhum valor pode ficar sem tradução.')
    write(locale, 'availability', AVAILABILITY[locale],
          'Mensagens de disponibilidade e stock.')
    print(f'  lang/{locale}/enums.php  +  lang/{locale}/availability.php')

total = sum(len(v) for v in VALUES.values())
print(f'\n{total} valores de enum × {len(T)} idiomas = {total * len(T)} traduções, nenhuma em falta')
