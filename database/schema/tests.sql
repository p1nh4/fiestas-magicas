-- =============================================================================
--  Testes do esquema — correm contra um Postgres real.
--  Objetivo: provar que a base de dados sozinha impede overbooking e
--  adulteração de documentos, mesmo que a aplicação tenha um bug.
--  As datas são relativas a now() de propósito: um teste com datas fixas
--  passa a mentir assim que essas datas ficam no passado.
-- =============================================================================

\set ON_ERROR_STOP on
SET client_min_messages = warning;

CREATE TEMP TABLE t_results (n serial, label text, passed boolean, detail text);

-- espera que o SQL passe
CREATE OR REPLACE FUNCTION t_ok(p_sql text, p_label text) RETURNS void
LANGUAGE plpgsql AS $$
BEGIN
    BEGIN
        EXECUTE p_sql;
        INSERT INTO t_results(label, passed, detail) VALUES (p_label, true, 'aceite');
    EXCEPTION WHEN others THEN
        INSERT INTO t_results(label, passed, detail) VALUES (p_label, false, 'recusou: ' || SQLERRM);
    END;
END $$;

-- espera que o SQL falhe
CREATE OR REPLACE FUNCTION t_fails(p_sql text, p_label text) RETURNS void
LANGUAGE plpgsql AS $$
BEGIN
    BEGIN
        EXECUTE p_sql;
        INSERT INTO t_results(label, passed, detail) VALUES (p_label, false, 'ACEITOU quando devia recusar');
    EXCEPTION WHEN others THEN
        INSERT INTO t_results(label, passed, detail) VALUES (p_label, true, 'recusou: ' || left(SQLERRM, 90));
    END;
END $$;

-- ---------------------------------------------------------------- dados base
INSERT INTO categories (id, kind, name, slug) VALUES
  (1, 'item', '{"es":"Mobiliario"}', '{"es":"mobiliario","gl":"mobiliario","pt":"mobiliario"}');

INSERT INTO items (id, category_id, sku, name, slug, stock_qty, price_per_day) VALUES
  (1, 1, 'SILLA-TIF', '{"es":"Silla Tiffany"}',   '{"es":"silla-tiffany","gl":"cadeira-tiffany","pt":"cadeira-tiffany"}', 40, 3.50),
  (2, 1, 'PHOTOCALL', '{"es":"Photocall floral"}','{"es":"photocall-floral","gl":"photocall-floral","pt":"photocall-floral"}', 1, 75.00);

INSERT INTO clients (id, name, email, phone) VALUES (1, 'Cliente de prova', 'prova@example.com', '600000000');

INSERT INTO events (id, client_id, reference, title, event_type, starts_at, ends_at) VALUES
  (1, 1, 'FM-2026-0001', 'Comunión sábado',  'comunion',  now() + interval '60 days 4 hours', now() + interval '60 days 12 hours'),
  (2, 1, 'FM-2026-0002', 'Bautizo sábado',   'bautizo',   now() + interval '60 days 5 hours', now() + interval '60 days 11 hours'),
  (3, 1, 'FM-2026-0003', 'Cumpleaños otro día','cumpleanos',now() + interval '120 days 4 hours',now() + interval '120 days 12 hours');

SELECT setval('categories_id_seq', 10), setval('items_id_seq', 10),
       setval('clients_id_seq', 10), setval('events_id_seq', 10);

-- =============================================================================
--  A. Stock agregado (40 cadeiras)
-- =============================================================================

SELECT t_ok($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (1, 1, 1, 30, tstzrange(now() + interval '60 days',now() + interval '61 days 12 hours'))
$$, 'A1 · 30 de 40 cadeiras reservadas');

SELECT t_fails($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (2, 1, 2, 15, tstzrange(now() + interval '60 days 1 hour',now() + interval '61 days 4 hours'))
$$, 'A2 · mais 15 sobrepostas (45 > 40) tem de ser recusado');

SELECT t_ok($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (3, 1, 2, 10, tstzrange(now() + interval '60 days 1 hour',now() + interval '61 days 4 hours'))
$$, 'A3 · mais 10 sobrepostas (exatamente 40) cabe');

SELECT t_fails($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (4, 1, 2, 1, tstzrange(now() + interval '60 days 10 hours',now() + interval '60 days 15 hours'))
$$, 'A4 · mais 1 cadeira já não cabe');

SELECT t_ok($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (5, 1, 3, 40, tstzrange(now() + interval '120 days',now() + interval '121 days 12 hours'))
$$, 'A5 · 40 cadeiras noutra data (sem sobreposição)');

-- =============================================================================
--  B. Fronteiras dos intervalos
-- =============================================================================

SELECT t_ok($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (6, 1, 3, 40, tstzrange(now() + interval '121 days 12 hours',now() + interval '122 days 12 hours'))
$$, 'B1 · intervalo encostado [a,b)+[b,c) não é sobreposição');

SELECT t_fails($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (7, 1, 3, 1, tstzrange(now() + interval '121 days 11 hours 59 minutes',now() + interval '122 days 12 hours'))
$$, 'B2 · um minuto de sobreposição já conta');

SELECT t_fails($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (8, 1, 3, 1, tstzrange(now() + interval '130 days',now() + interval '130 days'))
$$, 'B3 · intervalo vazio é rejeitado pelo CHECK');

-- =============================================================================
--  C. Peça única (photocall, stock 1)
-- =============================================================================

SELECT t_ok($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (10, 2, 1, 1, tstzrange(now() + interval '60 days',now() + interval '61 days 4 hours'))
$$, 'C1 · photocall reservado para a comunhão');

SELECT t_fails($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (11, 2, 2, 1, tstzrange(now() + interval '60 days 10 hours',now() + interval '61 days'))
$$, 'C2 · o mesmo photocall no mesmo dia é recusado');

-- =============================================================================
--  D. Cancelamento liberta stock
-- =============================================================================

SELECT t_ok($$ UPDATE reservations SET status = 'cancelled' WHERE id = 3 $$,
            'D1 · cancelar as 10 cadeiras');

SELECT t_ok($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (12, 1, 2, 10, tstzrange(now() + interval '60 days 1 hour',now() + interval '61 days 4 hours'))
$$, 'D2 · as 10 cadeiras voltam a caber depois do cancelamento');

-- =============================================================================
--  E. Alterar uma reserva existente também é validado
-- =============================================================================

SELECT t_fails($$ UPDATE reservations SET quantity = 25 WHERE id = 12 $$,
               'E1 · aumentar uma reserva para além do stock é recusado');

SELECT t_ok($$ UPDATE reservations SET quantity = 5 WHERE id = 12 $$,
            'E2 · reduzir uma reserva é aceite');

-- =============================================================================
--  F. Bloqueios manuais (manutenção) consomem stock
-- =============================================================================

SELECT t_ok($$
  INSERT INTO reservations (id, item_id, quantity, period, blocked_reason)
  VALUES (20, 2, 1, tstzrange(now() + interval '150 days',now() + interval '164 days'), 'Estrutura partida, em reparação')
$$, 'F1 · bloqueio de manutenção sem evento');

SELECT t_fails($$
  INSERT INTO reservations (id, item_id, event_id, quantity, period)
  VALUES (21, 2, 3, 1, tstzrange(now() + interval '154 days',now() + interval '155 days'))
$$, 'F2 · não se aluga uma peça que está em reparação');

SELECT t_fails($$
  INSERT INTO reservations (id, item_id, quantity, period)
  VALUES (22, 1, 1, tstzrange(now() + interval '180 days',now() + interval '181 days'))
$$, 'F3 · reserva sem evento e sem motivo é recusada');

-- =============================================================================
--  G. Baixar o stock não pode criar overbooking retroativo
-- =============================================================================

SELECT t_fails($$ UPDATE items SET stock_qty = 20 WHERE id = 1 $$,
               'G1 · baixar de 40 para 20 com 35 já reservadas é recusado');

SELECT t_ok($$ UPDATE items SET stock_qty = 60 WHERE id = 1 $$,
            'G2 · aumentar o stock é sempre aceite');

-- =============================================================================
--  H. Documentos selados são imutáveis
-- =============================================================================

INSERT INTO document_series (id, code, doc_type, prefix, year) VALUES (1, 'FAC', 'invoice', 'FAC', 2026);
INSERT INTO documents (id, series_id, sequence, number, doc_type, client_id, total, content_hash, chain_index)
VALUES (1, 1, 1, 'FAC/2026/0001', 'invoice', 1, 484.00, repeat('a', 64), 1);

SELECT t_ok($$ UPDATE documents SET pdf_path = '/docs/fac-2026-0001.pdf' WHERE id = 1 $$,
            'H1 · documento por selar ainda se pode completar');

SELECT t_ok($$ UPDATE documents SET locked_at = now() WHERE id = 1 $$,
            'H2 · selar o documento');

SELECT t_fails($$ UPDATE documents SET total = 10.00 WHERE id = 1 $$,
               'H3 · alterar o total de um documento selado é recusado');

SELECT t_fails($$ DELETE FROM documents WHERE id = 1 $$,
               'H4 · apagar um documento selado é recusado');

SELECT t_fails($$
  INSERT INTO documents (series_id, sequence, number, doc_type, client_id, total)
  VALUES (1, 1, 'FAC/2026/0001-BIS', 'invoice', 1, 100.00)
$$, 'H5 · repetir o número de sequência na mesma série é recusado');

-- =============================================================================
--  I. Regras de negócio soltas
-- =============================================================================

SELECT t_fails($$
  INSERT INTO events (client_id, reference, title, event_type, starts_at, ends_at)
  VALUES (1, 'FM-2026-9999', 'Fim antes do início', 'boda', now() + interval '60 days 12 hours', now() + interval '60 days 4 hours')
$$, 'I1 · evento que acaba antes de começar é recusado');

SELECT t_fails($$ INSERT INTO clients (name) VALUES ('Sem contacto nenhum') $$,
               'I2 · cliente sem email nem telefone é recusado');

SELECT t_fails($$
  INSERT INTO projects (title, slug, event_type, is_published)
  VALUES ('{"es":"Boda"}', '{"es":"boda-x"}', 'boda', true)
$$, 'I3 · publicar fotos sem consentimento do cliente é recusado');

SELECT t_fails($$
  INSERT INTO payments (client_id, kind, method, amount) VALUES (1, 'refund', 'card', 50.00)
$$, 'I4 · reembolso com valor positivo é recusado');

SELECT t_ok($$
  INSERT INTO payments (client_id, kind, method, amount) VALUES (1, 'refund', 'card', -50.00)
$$, 'I5 · reembolso com valor negativo é aceite');

-- =============================================================================
--  Resultado
-- =============================================================================


-- ---------------------------------------------------------------- zonas (SEO local)
-- A regra que protege o site: uma zona sem texto próprio NÃO se publica.
-- Onze páginas iguais com o nome do sítio trocado são doorway pages, e o
-- Google desvaloriza o domínio todo por causa delas. Se este bloco falhar,
-- alguém tornou possível publicá-las a partir do backoffice.

SELECT t_ok($$
    INSERT INTO service_areas (name, slug, province, country, distance_km, position)
    VALUES ('Nigrán', '{"es":"nigran","gl":"nigran","pt":"nigran"}', 'Pontevedra', 'ES', 7.5, 1)
$$, 'Z1 aceita uma zona em rascunho, sem texto');

SELECT t_fails($$
    UPDATE service_areas SET is_published = true WHERE name = 'Nigrán'
$$, 'Z2 recusa publicar uma zona sem texto próprio');

SELECT t_fails($$
    UPDATE service_areas
       SET intro = '{"es":"Decoramos fiestas en Nigrán."}'::jsonb, is_published = true
     WHERE name = 'Nigrán'
$$, 'Z3 recusa publicar com uma frase de encher');

SELECT t_ok($$
    UPDATE service_areas
       SET intro = jsonb_build_object('es', repeat('Texto propio de la zona. ', 12)),
           is_published = true
     WHERE name = 'Nigrán'
$$, 'Z4 aceita publicar quando há texto a sério');

SELECT t_fails($$
    INSERT INTO service_areas (name, slug)
    VALUES ('Gondomar', '{"es":"nigran","gl":"gondomar","pt":"gondomar"}')
$$, 'Z5 recusa duas zonas com o mesmo endereço');

SELECT t_fails($$
    INSERT INTO service_areas (name, slug, country)
    VALUES ('Braga', '{"es":"braga","gl":"braga","pt":"braga"}', 'FR')
$$, 'Z6 recusa um país fora de ES/PT');

SELECT t_ok($$
    INSERT INTO service_areas (name, slug, country, distance_km)
    VALUES ('Viana do Castelo', '{"es":"viana-do-castelo","gl":"viana-do-castelo","pt":"viana-do-castelo"}', 'PT', 55.0)
$$, 'Z7 aceita uma zona no norte de Portugal');


-- ---------------------------------------------------------------- desenhos
-- Um desenho por festa, e cada peça uma vez só. As duas regras existem para
-- evitar ambiguidade: dois desenhos concorrentes, ou a mesma peça listada
-- duas vezes, são sempre um engano — e um engano que só se descobre no dia
-- de carregar a carrinha.

SELECT t_ok($$
    INSERT INTO event_designs (event_id, theme, palette)
    VALUES (1, 'Sirenas', '["#c96f86","#a8823c"]')
$$, 'E1 aceita um desenho para uma festa');

SELECT t_fails($$
    INSERT INTO event_designs (event_id, theme) VALUES (1, 'Otro tema')
$$, 'E2 recusa um segundo desenho para a mesma festa');

SELECT t_ok($$
    INSERT INTO event_design_items (design_id, item_id, quantity)
    VALUES ((SELECT id FROM event_designs WHERE event_id = 1), 1, 40)
$$, 'E3 aceita material no desenho');

SELECT t_fails($$
    INSERT INTO event_design_items (design_id, item_id, quantity)
    VALUES ((SELECT id FROM event_designs WHERE event_id = 1), 1, 10)
$$, 'E4 recusa a mesma peça duas vezes no mesmo desenho');

SELECT t_fails($$
    INSERT INTO event_design_items (design_id, item_id, quantity)
    VALUES ((SELECT id FROM event_designs WHERE event_id = 1), 2, 0)
$$, 'E5 recusa quantidade zero');

-- O desenho é uma INTENÇÃO, não uma reserva: pôr material aqui não pode
-- prender stock nenhum. Se um dia isto falhar, alguém ligou as duas coisas
-- e passou a bloquear material por causa de ideias que ainda podem mudar.
SELECT t_ok($$
    INSERT INTO event_design_items (design_id, item_id, quantity)
    VALUES ((SELECT id FROM event_designs WHERE event_id = 1), 2, 999)
$$, 'E6 o desenho nao esta limitado pelo stock — nao e uma reserva');

\echo ''
\echo '================= RESULTADO ================='
SELECT rpad(label, 62, ' ') AS teste,
       CASE WHEN passed THEN 'PASSA' ELSE 'FALHA' END AS estado
FROM t_results ORDER BY n;

\echo ''
SELECT count(*) FILTER (WHERE passed)       AS passaram,
       count(*) FILTER (WHERE NOT passed)   AS falharam,
       count(*)                             AS total
FROM t_results;

\echo ''
\echo '--- detalhe dos que falharam (se houver) ---'
SELECT label, detail FROM t_results WHERE NOT passed ORDER BY n;
