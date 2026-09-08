"""Gera os enums PHP a partir dos CHECK constraints do schema.sql.

Assim, os valores aceites pelo PHP e os valores aceites pela base de dados
sao literalmente a mesma lista. Se alguem acrescentar um estado no schema e
se esquecer do enum, isto apanha.
"""
import re, pathlib

SRC = 'database/schema/schema.sql'
OUT = pathlib.Path('build/app/Enums')

# constraint no schema -> (classe PHP, chave de traducao)
PLAN = {
    'clients_kind_chk':            ('ClientKind',        'client_kind'),
    'leads_status_chk':            ('LeadStatus',        'lead_status'),
    'events_status_chk':           ('EventStatus',       'event_status'),
    'quotes_status_chk':           ('QuoteStatus',       'quote_status'),
    'reservations_status_chk':     ('ReservationStatus', 'reservation_status'),
    'payments_kind_chk':           ('PaymentKind',       'payment_kind'),
    'payments_method_chk':         ('PaymentMethod',     'payment_method'),
    'payments_status_chk':         ('PaymentStatus',     'payment_status'),
    'documents_type_chk':          ('DocumentType',      'document_type'),
    'services_price_mode_chk':     ('PriceMode',         'price_mode'),
    'categories_kind_chk':         ('CategoryKind',      'category_kind'),
    'users_locale_chk':            ('Locale',            'locale'),
}

# tipos de celebracao: nao estao num CHECK porque a lista cresce com o negocio
EVENT_TYPES = [
    'cumpleanos', 'cumpleanos_infantil', 'bautizo', 'comunion',
    'boda', 'baby_shower', 'empresa', 'otro',
]


def case_name(value: str) -> str:
    return ''.join(p.capitalize() for p in re.split(r'[_\-]', value))


def render(cls: str, tkey: str, values: list[str], extra: str = '') -> str:
    cases = '\n'.join(f"    case {case_name(v)} = '{v}';" for v in values)
    return f'''<?php

declare(strict_types=1);

namespace App\\Enums;

/**
 * Gerado a partir de database/schema/schema.sql — nao editar a mao.
 *
 * Os valores sao exatamente os que a base de dados aceita. O CHECK do
 * Postgres continua a ser a ultima linha de defesa; este enum e para o
 * PHP e para os formularios.
 */
enum {cls}: string
{{
{cases}

    /** Etiqueta traduzida (lang/{{es,gl,pt}}/enums.php). */
    public function label(): string
    {{
        return __('enums.{tkey}.' . $this->value);
    }}

    /** Para os selects do Filament e dos formularios publicos. */
    public static function options(): array
    {{
        return array_column(
            array_map(
                static fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
                self::cases()
            ),
            'label',
            'value'
        );
    }}

    public static function values(): array
    {{
        return array_column(self::cases(), 'value');
    }}
{extra}}}
'''


def main():
    sql = open(SRC, encoding='utf-8').read()
    OUT.mkdir(parents=True, exist_ok=True)
    for old in OUT.glob('*.php'):
        old.unlink()

    lang = {}
    for constraint, (cls, tkey) in PLAN.items():
        m = re.search(
            r'CONSTRAINT\s+' + constraint + r'\s+CHECK\s*\([^)]*?IN\s*\(([^)]*)\)',
            sql, re.S)
        if not m:
            raise SystemExit(f'CHECK nao encontrado no schema: {constraint}')
        values = re.findall(r"'([^']+)'", m.group(1))
        (OUT / f'{cls}.php').write_text(render(cls, tkey, values), encoding='utf-8')
        lang[tkey] = values
        print(f'  {cls:20s} {len(values)} valores  ({", ".join(values)})')

    extra = '''
    /** Cor da pastilha no backoffice. */
    public function color(): string
    {
        return match ($this) {
            self::Boda, self::Comunion => 'warning',
            self::Bautizo              => 'info',
            default                    => 'primary',
        };
    }
'''
    (OUT / 'EventType.php').write_text(
        render('EventType', 'event_type', EVENT_TYPES, extra), encoding='utf-8')
    lang['event_type'] = EVENT_TYPES
    print(f'  {"EventType":20s} {len(EVENT_TYPES)} valores')

    pathlib.Path('build/_enum_values.json').write_text(
        __import__('json').dumps(lang, indent=2, ensure_ascii=False), encoding='utf-8')
    print(f'\n{len(PLAN) + 1} enums gerados')


if __name__ == '__main__':
    main()
