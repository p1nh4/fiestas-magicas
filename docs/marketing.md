# Aparecer nas buscas locais

O que o código faz sozinho, o que tem de ser feito à mão, e a armadilha que
pode deitar tudo a perder.

## O problema

Ninguém escreve "decoración de fiestas" no Google. Escreve **"decoración de
globos en Nigrán"**, "photocall comunión Vigo", "decoração de festas
Valença". São buscas com o nome do sítio lá dentro, feitas por quem já
decidiu que quer contratar alguém.

Um site com uma única página não aparece nessas buscas. É para isso que
existe a secção de zonas.

## A armadilha

É a forma mais fácil de estragar o site inteiro, e a tentação é enorme:
gerar as onze páginas a partir de um molde, trocando o nome do concelho.

Isso chama-se **doorway pages** e o Google tem um nome próprio para isso
porque é um problema conhecido. Não se limita a ignorá-las: **desvaloriza o
domínio todo**. O site fica pior do que estava antes de as ter.

Por isso a regra vive no `CHECK` da base de dados e não numa validação de
formulário: uma zona só se publica com texto próprio escrito à mão. Não é
uma sugestão que se ignora com pressa.

**Duas ou três páginas boas valem mais do que onze medíocres.** Começa por
Nigrán, Gondomar e Vigo — os concelhos onde ela já trabalhou.

## O que escrever numa página de zona

Coisas que só são verdade naquele sítio:

- salões, quintas, pazos ou igrejas onde já montou lá
- quanto tempo demora a chegar, e o que isso significa (montagem na véspera
  ou no próprio dia)
- festas concretas que fez ali — se houver fotos com autorização do
  cliente, aparecem sozinhas na página
- particularidades: um salão com pé-direito baixo onde os arcos altos não
  entram, uma igreja com regras de decoração

Coisas que **não** servem: "somos una empresa de decoración con años de
experiencia en Nigrán". Isso é o molde, e o molde é o problema.

---

## O que tem de ser feito à mão

### 1. Google Business Profile

É o que mais rende e não custa nada. Sem isto, a empresa não aparece no
mapa nem no painel lateral.

1. Criar o perfil em business.google.com com a morada de Sabarís
2. Categoria principal: *Servicio de decoración para eventos*
3. Zona de serviço: os concelhos todos (é aqui que se declara que se
   desloca, mesmo sem morada lá)
4. Ligar o site — e apontar para a página da zona correspondente quando
   houver
5. Fotos: as mesmas dos trabalhos publicados
6. Pedir avaliações a clientes reais. **Nunca inventar** — o site tem
   testes que impedem testemunhos sem consentimento datado, e o Google
   deteta e pune padrões de avaliações falsas

O perfil é um só, mas responde em espanhol, galego e português. Vale a pena
responder a cada avaliação no idioma em que foi escrita.

### 2. Instagram → site, com rasto

O formulário já guarda `utm_source`, `utm_medium` e `utm_campaign`. Só falta
usá-los nos links.

Na bio do Instagram:

```
https://DOMINIO.TLD/es?utm_source=instagram&utm_medium=bio
```

Num story sobre uma festa em Nigrán:

```
https://DOMINIO.TLD/es/zonas/nigran?utm_source=instagram&utm_medium=story
```

Convenção, para os números não virarem lixo ao fim de três meses:

| campo | valores |
|---|---|
| `utm_source` | `instagram`, `facebook`, `google`, `whatsapp`, `flyer` |
| `utm_medium` | `bio`, `story`, `post`, `reel`, `anuncio`, `qr` |
| `utm_campaign` | `comuniones-2027`, `bodas-verano`… |

Minúsculas e sem acentos, sempre. `Instagram` e `instagram` contam como duas
origens diferentes e não há forma de as juntar depois.

Num flyer ou cartão, um QR code para o link com `utm_medium=qr` diz quantas
pessoas do papel chegaram mesmo ao site.

### 3. Ler os números

No backoffice, em **Solicitudes**, a coluna *Origen* mostra a origem de cada
pedido (está escondida por omissão — liga-a no botão das colunas).

Para o resumo do mês:

```sql
SELECT coalesce(utm_source, 'directo') AS origen,
       coalesce(utm_medium, '—')       AS medio,
       count(*)                         AS pedidos,
       count(*) FILTER (WHERE status = 'won') AS fiestas
FROM leads
WHERE created_at >= now() - interval '90 days'
GROUP BY 1, 2
ORDER BY pedidos DESC;
```

A coluna que interessa é a última. Uma origem que traz trinta pedidos e zero
festas está a gastar tempo, não a trazer clientes.

---

## O que o código já faz

- **hreflang e canonical** nas três versões de cada página, incluindo as
  zonas, onde o endereço muda mesmo entre idiomas
- **sitemap.xml** com as zonas publicadas — e só essas: anunciar uma página
  que dá 404 ensina o Google a desconfiar do resto
- **JSON-LD `LocalBusiness`** com `areaServed`, sem horários nem avaliações
  inventadas
- **robots.txt fechado fora de produção**, para o site de testes não roubar
  posições ao verdadeiro
- **`utm` guardado em cada pedido**, com o IP só em hash (RGPD)

## O que falta e não é código

- as fotos reais (numa empresa de decoração, a foto é o produto)
- o texto das zonas, escrito pela Sol
- o perfil do Google
- pedir avaliações a clientes que já ficaram contentes
