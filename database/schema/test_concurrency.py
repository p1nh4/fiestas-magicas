"""Teste de concorrencia: duas reservas simultaneas da ultima peca.

Sem o advisory lock no trigger, ambas as transacoes leem "ha stock" antes de
qualquer uma escrever, e ambas gravam -> overbooking (write skew). Este teste
faz as duas transacoes correrem sobrepostas de proposito.

Correr:
    sudo service postgresql start
    python3 test_concurrency.py            # usa a BD "fiestas"
    python3 test_concurrency.py outra_bd

Sai com 0 se passar. Cria os seus proprios dados; nao precisa de nada na BD
alem do esquema aplicado.
"""
import sys
import threading
import time

import psycopg2

DBNAME = sys.argv[1] if len(sys.argv) > 1 else "fiestas"
DSN = f"host=127.0.0.1 user=fiestas password=fiestas dbname={DBNAME}"

BARRIER = threading.Barrier(2)
results: dict[str, tuple[str, str | None]] = {}


def fixtures(cur) -> tuple[int, int, int]:
    """Cria categoria, cliente, dois eventos e uma peca com stock 1.

    Tudo com nomes unicos por execucao: o teste tem de poder correr as vezes
    que forem precisas sem limpar nada a mao.
    """
    cur.execute("""
        INSERT INTO categories (kind, name, slug)
        VALUES ('item', '{"es":"Prova"}',
                jsonb_build_object('es','prova-'||gen_random_uuid(),
                                   'gl','prova-'||gen_random_uuid(),
                                   'pt','prova-'||gen_random_uuid()))
        RETURNING id
    """)
    category_id = cur.fetchone()[0]

    cur.execute("""
        INSERT INTO clients (name, email)
        VALUES ('Cliente de concorrência', 'concorrencia-'||gen_random_uuid()||'@example.test')
        RETURNING id
    """)
    client_id = cur.fetchone()[0]

    event_ids = []
    for n in (1, 2):
        cur.execute("""
            INSERT INTO events (client_id, reference, title, event_type, starts_at, ends_at)
            VALUES (%s, 'CONC-'||substr(gen_random_uuid()::text, 1, 12), %s, 'otro',
                    now() + interval '200 days', now() + interval '200 days 6 hours')
            RETURNING id
        """, (client_id, f"Festa simultânea {n}"))
        event_ids.append(cur.fetchone()[0])

    cur.execute("""
        INSERT INTO items (category_id, sku, name, slug, stock_qty, price_per_day)
        VALUES (%s,
                'CONC-'||substr(gen_random_uuid()::text, 1, 12),
                '{"es":"Letras iluminadas"}',
                jsonb_build_object('es','letras-'||gen_random_uuid(),
                                   'gl','letras-'||gen_random_uuid(),
                                   'pt','letras-'||gen_random_uuid()),
                1, 60.00)
        RETURNING id
    """, (category_id,))
    item_id = cur.fetchone()[0]

    return item_id, event_ids[0], event_ids[1]


def reserve(tag: str, item_id: int, event_id: int, hold_seconds: float) -> None:
    conn = psycopg2.connect(DSN)
    conn.autocommit = False
    cur = conn.cursor()
    try:
        cur.execute("BEGIN")
        BARRIER.wait(timeout=10)          # as duas arrancam ao mesmo tempo
        cur.execute(
            """
            INSERT INTO reservations (item_id, event_id, quantity, period)
            VALUES (%s, %s, 1,
                    tstzrange(now() + interval '200 days', now() + interval '201 days'))
            """,
            (item_id, event_id),
        )
        time.sleep(hold_seconds)          # segura a transacao aberta
        conn.commit()
        results[tag] = ("COMMIT", None)
    except Exception as e:                # noqa: BLE001
        conn.rollback()
        results[tag] = ("ROLLBACK", str(e).strip().splitlines()[0][:110])
    finally:
        cur.close()
        conn.close()


def main() -> int:
    setup = psycopg2.connect(DSN)
    setup.autocommit = True
    cur = setup.cursor()

    item_id, event_a, event_b = fixtures(cur)

    threads = [
        threading.Thread(target=reserve, args=("A", item_id, event_a, 0.6)),
        threading.Thread(target=reserve, args=("B", item_id, event_b, 0.0)),
    ]
    for t in threads:
        t.start()
    for t in threads:
        t.join()

    cur.execute(
        "SELECT COALESCE(SUM(quantity), 0) FROM reservations "
        "WHERE item_id = %s AND status <> 'cancelled'",
        (item_id,),
    )
    reserved = cur.fetchone()[0]
    cur.execute("SELECT stock_qty FROM items WHERE id = %s", (item_id,))
    stock = cur.fetchone()[0]
    cur.close()
    setup.close()

    print("\n=========== CONCORRENCIA ===========")
    for tag in ("A", "B"):
        outcome, err = results.get(tag, ("NAO CORREU", None))
        print(f"  transacao {tag}: {outcome}" + (f"\n      {err}" if err else ""))
    print(f"\n  stock da peca ........ {stock}")
    print(f"  reservado no fim ..... {reserved}")

    committed = sum(1 for v in results.values() if v[0] == "COMMIT")

    # Distinguir os modos de falha: um teste que diz "overbooking" quando na
    # verdade rebentou por outra razao e pior do que nao existir.
    if reserved > stock:
        verdict, ok = "FALHA: OVERBOOKING — o advisory lock nao segurou", False
    elif committed == 0:
        verdict, ok = ("FALHA: nenhuma transacao passou — o teste rebentou por "
                       "outra razao, ver os erros acima"), False
    elif committed == 2:
        verdict, ok = "FALHA: as duas passaram, mas o stock nao bateu certo", False
    else:
        verdict, ok = "PASSA: exatamente uma reserva vingou, sem overbooking", True

    print(f"\n  {verdict}")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
