<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Aviso legal, privacidade e cookies.
 *
 * LEIA-SE ISTO ANTES DE PUBLICAR.
 *
 * Não sou advogado e isto não é aconselhamento jurídico. O que aqui está é
 * um rascunho escrito a partir do que o código REALMENTE faz — que dados
 * são recolhidos, para quê, e para onde vão — e isso é a parte que um
 * modelo copiado da internet costuma ter errada.
 *
 * O que falta é o que só a Sol pode dar: nome, NIF, morada e email do
 * responsável. Está marcado com PENDIENTE, as páginas nascem despublicadas,
 * e há um teste que falha se alguma for publicada com PENDIENTE lá dentro.
 *
 * Enquanto não estiverem publicadas, o formulário mostra "política de
 * privacidad" sem ligação, em vez de um link morto. É pior pedir
 * consentimento apontando para o vazio do que não apontar para lado nenhum.
 *
 * Formato do corpo: linhas em branco separam parágrafos, "## " é um título
 * e "- " é um item de lista. Sem HTML — é a Sol que escreve aqui.
 */
final class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            // firstOrCreate: se ela já reescreveu o texto, este seeder não
            // lho apaga por voltar a correr.
            Page::firstOrCreate(['key' => $page['key']], [
                'title' => $page['title'],
                'slug' => $page['slug'],
                'body' => $page['body'],
                'is_published' => false,
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function pages(): array
    {
        return [
            [
                'key' => Page::PRIVACY,
                'title' => [
                    'es' => 'Política de privacidad',
                    'gl' => 'Política de privacidade',
                    'pt' => 'Política de privacidade',
                ],
                'slug' => [
                    'es' => 'politica-de-privacidad',
                    'gl' => 'politica-de-privacidade',
                    'pt' => 'politica-de-privacidade',
                ],
                'body' => [
                    'es' => $this->privacyEs(),
                    'gl' => $this->privacyGl(),
                    'pt' => $this->privacyPt(),
                ],
            ],
            [
                'key' => Page::COOKIES,
                'title' => [
                    'es' => 'Cookies',
                    'gl' => 'Cookies',
                    'pt' => 'Cookies',
                ],
                'slug' => ['es' => 'cookies', 'gl' => 'cookies', 'pt' => 'cookies'],
                'body' => [
                    'es' => $this->cookiesEs(),
                    'gl' => $this->cookiesGl(),
                    'pt' => $this->cookiesPt(),
                ],
            ],
            [
                'key' => Page::LEGAL,
                'title' => [
                    'es' => 'Aviso legal',
                    'gl' => 'Aviso legal',
                    'pt' => 'Aviso legal',
                ],
                'slug' => ['es' => 'aviso-legal', 'gl' => 'aviso-legal', 'pt' => 'aviso-legal'],
                'body' => [
                    'es' => $this->legalEs(),
                    'gl' => $this->legalGl(),
                    'pt' => $this->legalPt(),
                ],
            ],
        ];
    }

    // ------------------------------------------------------------ privacidad

    private function privacyEs(): string
    {
        return <<<'TXT'
        ## Quién trata tus datos

        PENDIENTE: nombre y apellidos o razón social, NIF, dirección postal y
        correo electrónico de contacto. Sin esto la página no puede publicarse.

        ## Qué datos recogemos y cuándo

        Cuando rellenas el formulario de presupuesto guardamos lo que escribes:
        nombre, correo electrónico, teléfono, tipo de celebración, fecha,
        número de invitados, lugar y el mensaje.

        Junto a eso guardamos, de forma automática, de dónde vino la visita
        (la campaña o el enlace que te trajo, y la página por la que entraste)
        y el navegador que usaste. Esto sirve para saber qué merece la pena y
        qué no; no se usa para perfilarte.

        Tu dirección IP NO se guarda en claro. Se guarda solo un resumen
        criptográfico, que sirve para detectar envíos masivos desde el mismo
        sitio y no permite recuperar la IP original.

        Si acabas contratando, guardamos además lo necesario para el evento y
        para la factura.

        ## Para qué

        - Para responderte y prepararte un presupuesto.
        - Para organizar tu fiesta si lo contratas.
        - Para cumplir con nuestras obligaciones fiscales y contables.

        No te mandamos publicidad por haber pedido un presupuesto. Para eso
        hace falta que nos des permiso aparte, y puedes retirarlo cuando
        quieras.

        ## Con qué base legal

        - Tu consentimiento, cuando rellenas el formulario.
        - La ejecución del contrato, cuando ya hay una fiesta contratada.
        - Una obligación legal, para la facturación.

        ## Quién más los ve

        - La empresa que aloja el sitio web y el correo.
        - Cloudflare, que hace de intermediario entre tu navegador y nuestro
          servidor.
        - La pasarela de pago, solo si pagas con tarjeta. Nosotros no vemos ni
          guardamos el número de tu tarjeta en ningún momento.

        No vendemos ni cedemos tus datos a nadie más.

        ## Cuánto tiempo

        PENDIENTE: confirmar los plazos. Como referencia: las peticiones que no
        llegan a nada se borran pasado un tiempo razonable, y lo que tiene que
        ver con facturas se conserva los años que exige la ley.

        ## Qué puedes hacer

        Puedes pedirnos acceder a tus datos, corregirlos, borrarlos, limitar su
        uso, oponerte a que los usemos o llevártelos a otro sitio. Escríbenos
        al correo de arriba y te contestamos.

        Si crees que no lo hemos hecho bien, puedes reclamar ante la Agencia
        Española de Protección de Datos (www.aepd.es).
        TXT;
    }

    private function privacyGl(): string
    {
        return <<<'TXT'
        ## Quen trata os teus datos

        PENDIENTE: nome e apelidos ou razón social, NIF, enderezo postal e
        correo electrónico de contacto. Sen isto a páxina non se pode publicar.

        ## Que datos recollemos e cando

        Cando enches o formulario de orzamento gardamos o que escribes: nome,
        correo electrónico, teléfono, tipo de celebración, data, número de
        convidados, lugar e a mensaxe.

        Xunto a iso gardamos, de forma automática, de onde veu a visita (a
        campaña ou a ligazón que te trouxo, e a páxina pola que entraches) e o
        navegador que usaches. Isto serve para saber que paga a pena e que non;
        non se usa para perfilarte.

        O teu enderezo IP NON se garda en claro. Gárdase só un resumo
        criptográfico, que serve para detectar envíos masivos desde o mesmo
        sitio e non permite recuperar a IP orixinal.

        Se acabas contratando, gardamos ademais o necesario para o evento e
        para a factura.

        ## Para que

        - Para contestarche e prepararche un orzamento.
        - Para organizar a túa festa se a contratas.
        - Para cumprir coas nosas obrigas fiscais e contables.

        Non che mandamos publicidade por teres pedido un orzamento. Para iso
        fai falta que nos deas permiso á parte, e podes retiralo cando queiras.

        ## Con que base legal

        - O teu consentimento, cando enches o formulario.
        - A execución do contrato, cando xa hai unha festa contratada.
        - Unha obriga legal, para a facturación.

        ## Quen máis os ve

        - A empresa que aloxa o sitio web e o correo.
        - Cloudflare, que fai de intermediario entre o teu navegador e o noso
          servidor.
        - A pasarela de pagamento, só se pagas con tarxeta. Nós non vemos nin
          gardamos o número da túa tarxeta en ningún momento.

        Non vendemos nin cedemos os teus datos a ninguén máis.

        ## Canto tempo

        PENDIENTE: confirmar os prazos. Como referencia: as peticións que non
        chegan a nada bórranse pasado un tempo razoable, e o que ten que ver
        con facturas consérvase os anos que esixe a lei.

        ## Que podes facer

        Podes pedirnos acceder aos teus datos, corrixilos, borralos, limitar o
        seu uso, oporte a que os usemos ou levalos a outro sitio. Escríbenos ao
        correo de arriba e contestámosche.

        Se cres que non o fixemos ben, podes reclamar ante a Axencia Española
        de Protección de Datos (www.aepd.es).
        TXT;
    }

    private function privacyPt(): string
    {
        return <<<'TXT'
        ## Quem trata os teus dados

        PENDIENTE: nome ou denominação social, NIF, morada e endereço de correio
        eletrónico de contacto. Sem isto a página não pode ser publicada.

        ## Que dados recolhemos e quando

        Quando preenches o formulário de orçamento guardamos o que escreves:
        nome, email, telefone, tipo de celebração, data, número de convidados,
        local e a mensagem.

        Além disso guardamos, automaticamente, de onde veio a visita (a campanha
        ou a ligação que te trouxe, e a página por onde entraste) e o navegador
        que usaste. Serve para saber o que compensa e o que não; não é usado
        para te traçar um perfil.

        O teu endereço IP NÃO é guardado em claro. Guarda-se apenas um resumo
        criptográfico, que serve para detetar envios em massa a partir do mesmo
        sítio e não permite recuperar o IP original.

        Se acabares por contratar, guardamos ainda o necessário para o evento e
        para a fatura.

        ## Para quê

        - Para te responder e preparar um orçamento.
        - Para organizar a tua festa, se a contratares.
        - Para cumprir as obrigações fiscais e contabilísticas.

        Não te enviamos publicidade por teres pedido um orçamento. Para isso é
        preciso dares autorização à parte, e podes retirá-la quando quiseres.

        ## Com que fundamento

        - O teu consentimento, quando preenches o formulário.
        - A execução do contrato, quando já há uma festa contratada.
        - Uma obrigação legal, para a faturação.

        ## Quem mais os vê

        - A empresa que aloja o site e o correio.
        - A Cloudflare, que faz de intermediária entre o teu navegador e o
          nosso servidor.
        - A plataforma de pagamento, apenas se pagares com cartão. Nós nunca
          vemos nem guardamos o número do teu cartão.

        Não vendemos nem cedemos os teus dados a mais ninguém.

        ## Durante quanto tempo

        PENDIENTE: confirmar os prazos. Como referência: os pedidos que não dão
        em nada são apagados passado um tempo razoável, e o que diz respeito a
        faturas conserva-se os anos que a lei exige.

        ## O que podes fazer

        Podes pedir-nos acesso aos teus dados, correção, apagamento, limitação
        do uso, oposição, ou levá-los para outro lado. Escreve para o email
        acima e respondemos.

        A empresa está em Espanha, por isso a autoridade competente é a Agencia
        Española de Protección de Datos (www.aepd.es). Se preferires, em
        Portugal existe a CNPD (www.cnpd.pt).
        TXT;
    }

    // ---------------------------------------------------------------- cookies

    private function cookiesEs(): string
    {
        return <<<'TXT'
        ## Qué usamos

        Este sitio no usa cookies de analítica ni de publicidad. No hay Google
        Analytics, no hay píxeles de redes sociales y no te seguimos por otras
        webs.

        Solo se usan las cookies necesarias para que el sitio funcione:

        - Una cookie de sesión, que recuerda lo que estás haciendo mientras
          navegas.
        - Una cookie de seguridad, que impide que otra web envíe formularios en
          tu nombre.

        Ninguna de las dos sirve para identificarte fuera de este sitio y ambas
        desaparecen al cerrar el navegador o poco después.

        ## Las tipografías

        Las tipografías se sirven desde Bunny Fonts en lugar de Google Fonts,
        precisamente para que tu visita no se comunique a un tercero que
        construya un perfil con ella.

        ## Por eso no verás un aviso de cookies

        Los avisos de cookies existen para pedirte permiso antes de instalar
        cookies que no son necesarias. Aquí no hay ninguna, así que no hay nada
        que pedirte.
        TXT;
    }

    private function cookiesGl(): string
    {
        return <<<'TXT'
        ## Que usamos

        Este sitio non usa cookies de analítica nin de publicidade. Non hai
        Google Analytics, non hai píxeles de redes sociais e non te seguimos
        por outras webs.

        Só se usan as cookies necesarias para que o sitio funcione:

        - Unha cookie de sesión, que lembra o que estás a facer mentres navegas.
        - Unha cookie de seguridade, que impide que outra web envíe formularios
          no teu nome.

        Ningunha das dúas serve para identificarte fóra deste sitio e ambas
        desaparecen ao pechar o navegador ou pouco despois.

        ## As tipografías

        As tipografías sérvense desde Bunny Fonts no canto de Google Fonts,
        precisamente para que a túa visita non se comunique a un terceiro que
        constrúa un perfil con ela.

        ## Por iso non verás un aviso de cookies

        Os avisos de cookies existen para pedirche permiso antes de instalar
        cookies que non son necesarias. Aquí non hai ningunha, así que non hai
        nada que pedirche.
        TXT;
    }

    private function cookiesPt(): string
    {
        return <<<'TXT'
        ## O que usamos

        Este site não usa cookies de análise nem de publicidade. Não há Google
        Analytics, não há píxeis de redes sociais e não te seguimos por outros
        sites.

        Só se usam as cookies necessárias para o site funcionar:

        - Uma cookie de sessão, que se lembra do que estás a fazer enquanto
          navegas.
        - Uma cookie de segurança, que impede que outro site envie formulários
          em teu nome.

        Nenhuma das duas serve para te identificar fora deste site e ambas
        desaparecem ao fechar o navegador ou pouco depois.

        ## As letras

        As tipografias são servidas pelo Bunny Fonts em vez do Google Fonts,
        precisamente para que a tua visita não seja comunicada a um terceiro
        que construa um perfil com ela.

        ## É por isso que não verás um aviso de cookies

        Os avisos de cookies existem para pedir autorização antes de instalar
        cookies que não são necessárias. Aqui não há nenhuma, portanto não há
        nada a pedir.
        TXT;
    }

    // ------------------------------------------------------------ aviso legal

    private function legalEs(): string
    {
        return <<<'TXT'
        ## Titular del sitio

        PENDIENTE: nombre y apellidos o razón social, NIF, dirección postal,
        teléfono y correo electrónico. La ley española obliga a que estos datos
        estén visibles y sean reales.

        ## Qué es este sitio

        Un sitio para dar a conocer un servicio de decoración de fiestas y para
        que puedas pedir un presupuesto. Los precios que aparezcan son
        orientativos hasta que haya un presupuesto aceptado por escrito.

        ## Las fotos

        Las fotografías de trabajos publicados están aquí con permiso de los
        clientes que aparecen en ellas. Si te reconoces en alguna y prefieres
        que no esté, escríbenos y la quitamos.

        ## Enlaces a otros sitios

        No respondemos de lo que haya en sitios de terceros a los que se pueda
        llegar desde aquí.

        ## Legislación aplicable

        Se aplica la legislación española.
        TXT;
    }

    private function legalGl(): string
    {
        return <<<'TXT'
        ## Titular do sitio

        PENDIENTE: nome e apelidos ou razón social, NIF, enderezo postal,
        teléfono e correo electrónico. A lei española obriga a que estes datos
        estean visibles e sexan reais.

        ## Que é este sitio

        Un sitio para dar a coñecer un servizo de decoración de festas e para
        que poidas pedir un orzamento. Os prezos que aparezan son orientativos
        ata que haxa un orzamento aceptado por escrito.

        ## As fotos

        As fotografías de traballos publicados están aquí con permiso dos
        clientes que aparecen nelas. Se te recoñeces nalgunha e prefires que
        non estea, escríbenos e quitámola.

        ## Ligazóns a outros sitios

        Non respondemos do que haxa en sitios de terceiros aos que se poida
        chegar desde aquí.

        ## Lexislación aplicable

        Aplícase a lexislación española.
        TXT;
    }

    private function legalPt(): string
    {
        return <<<'TXT'
        ## Titular do site

        PENDIENTE: nome ou denominação social, NIF, morada, telefone e email. A
        lei espanhola obriga a que estes dados estejam visíveis e sejam reais.

        ## O que é este site

        Um site para dar a conhecer um serviço de decoração de festas e para
        poderes pedir um orçamento. Os preços que apareçam são indicativos até
        haver um orçamento aceite por escrito.

        ## As fotografias

        As fotografias de trabalhos publicados estão aqui com autorização dos
        clientes que nelas aparecem. Se te reconheceres nalguma e preferires
        que não esteja, escreve-nos e retiramo-la.

        ## Ligações para outros sites

        Não respondemos pelo que houver em sites de terceiros a que se possa
        chegar a partir daqui.

        ## Legislação aplicável

        Aplica-se a legislação espanhola.
        TXT;
    }
}
