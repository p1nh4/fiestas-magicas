"""Escreve lang/{es,gl,pt}/site.php e forms.php.

Verifica que as tres arvores tem exatamente as mesmas chaves. Uma chave
que exista em espanhol e falte em galego aparece no site como
"site.hero.title" em vez de texto — e ninguem repara ate um cliente ver.
"""
import pathlib, sys

OUT = pathlib.Path('lang')

SITE = {}

SITE['es'] = {
    'brand': {
        'name': 'DecorArte',
        'sub': 'Fiestas Mágicas',
        'tagline': 'Decoración de fiestas y ceremonias en Baiona, el Val Miñor y el norte de Portugal.',
    },
    'nav': {
        'celebrations': 'Celebraciones',
        'works': 'Trabajos',
        'areas': 'Zonas',
        'about': 'Quiénes somos',
        'process': 'Cómo trabajamos',
        'rental': 'Alquiler',
        'contact': 'Contacto',
        'skip': 'Ir al contenido',
        'main': 'Navegación principal',
        'language': 'Idioma',
    },
    'util': {
        'phone': 'Teléfono',
        'whatsapp': 'WhatsApp',
        'where': 'Sabarís · Baiona (Pontevedra)',
        'instagram': 'Instagram',
    },
    'hero': {
        'kicker': 'Decoración de fiestas · Galicia',
        'title_before': 'Cuéntanos cómo lo',
        'title_script': 'soñaste',
        'lead': 'Y nos encargamos de que cada detalle de tu fiesta sea como lo imaginas. Cumpleaños, bautizos, comuniones y bodas en Baiona, el Val Miñor y toda la provincia de Pontevedra.',
        'cta_primary': 'Pedir presupuesto',
        'cta_secondary': 'Ver trabajos',
        'image_alt': 'Arco de globos en rosa y dorado montado para una celebración',
    },
    'services': {
        'kicker': 'Lo que montamos',
        'title': 'Cada celebración, montada de cero',
        'lead': 'Nos ocupamos del diseño, del material y del montaje en el sitio. Tú eliges la fecha y nos cuentas cómo la habías imaginado.',
        'from': 'desde :price',
        'on_request': 'a consultar',
        'empty': 'Estamos preparando esta sección.',
    },
    'about': {
        'kicker': 'Quiénes somos',
        'title': 'Detrás de DecorArte está Sol Fernández',
        'placeholder': 'Aquí va el texto de Sol: cómo empezó DecorArte, cuánto tiempo lleva montando fiestas y qué es lo que más disfruta hacer. Escrito con sus palabras, no con las de un folleto.',
        'who': 'Quién',
        'where': 'Dónde',
        'area': 'Zona',
        'area_value': 'Val Miñor, Vigo, Baixo Miño y norte de Portugal',
        'contact': 'Contacto',
        'hours': 'Horario',
        'hours_value': 'Por confirmar',
    },
    'process': {
        'kicker': 'Sin sorpresas',
        'title': 'Cómo trabajamos',
        'step1_title': 'Nos lo cuentas',
        'step1_body': 'Por WhatsApp o con el formulario: fecha, sitio, cuántos sois y las fotos que te gustan.',
        'step2_title': 'Te pasamos el presupuesto',
        'step2_body': 'Con los materiales y el precio cerrado. Si te encaja, se reserva la fecha con una señal.',
        'step3_title': 'Montamos y recogemos',
        'step3_body': 'Llegamos antes de que empiece, lo dejamos listo y volvemos a retirarlo todo.',
    },
    'projects': {
        'kicker': 'Fiestas de verdad',
        'title': 'Trabajos recientes',
        'view_all': 'Ver más en Instagram',
        'empty': 'Pronto publicaremos aquí los últimos montajes.',
    },
    'rental': {
        'kicker': 'Solo el material',
        'title': '¿Te la montas tú?',
        'lead': 'Algunas piezas se pueden alquilar sueltas por días. Consulta la disponibilidad de tu fecha y resérvalo.',
        'per_day': ':price / día',
        'cta': 'Consultar disponibilidad',
        'empty': 'Estamos preparando el catálogo de alquiler.',
    },
    'testimonials': {
        'kicker': 'Lo que dicen',
        'title': 'Clientes',
    },
    'faq': {
        'kicker': 'Dudas frecuentes',
        'title': 'Preguntas',
    },
    'contact': {
        'kicker': 'Cuéntanoslo',
        'title': 'Pide tu presupuesto',
        'lead': 'Escríbenos con la fecha y el lugar. Te respondemos por WhatsApp o por teléfono.',
        'phone': 'Teléfono',
        'whatsapp_cta': 'Escribir ahora',
        'where': 'Dónde estamos',
        'hours': 'Horario',
    },
    'thanks': {
        'title': 'Recibido',
        'lead': 'Gracias por contarnos cómo la imaginas. Te respondemos lo antes posible por el medio que nos dejaste.',
        'meanwhile': 'Mientras tanto puedes escribirnos directamente por WhatsApp si prefieres.',
        'back': 'Volver al inicio',
    },
    'footer': {
        'celebrations': 'Celebraciones',
        'contact': 'Contacto',
        'area': 'Zona',
        'legal': 'Aviso legal',
        'privacy': 'Privacidad',
        'cookies': 'Cookies',
        'rights': 'Todos los derechos reservados',
    },
    'meta': {
        'home_title': 'DecorArte · Decoración de fiestas en Baiona y Pontevedra',
        'home_description': 'Decoración de cumpleaños, bautizos, comuniones y bodas en Baiona, el Val Miñor y toda la provincia de Pontevedra. Cuéntanos cómo lo soñaste.',
    },
    'todo_hint': 'Pendiente de confirmar',
}

SITE['gl'] = {
    'brand': {
        'name': 'DecorArte',
        'sub': 'Fiestas Mágicas',
        'tagline': 'Decoración de festas e cerimonias en Baiona, o Val Miñor e o norte de Portugal.',
    },
    'nav': {
        'celebrations': 'Celebracións',
        'works': 'Traballos',
        'areas': 'Zonas',
        'about': 'Quen somos',
        'process': 'Como traballamos',
        'rental': 'Aluguer',
        'contact': 'Contacto',
        'skip': 'Ir ao contido',
        'main': 'Navegación principal',
        'language': 'Idioma',
    },
    'util': {
        'phone': 'Teléfono',
        'whatsapp': 'WhatsApp',
        'where': 'Sabarís · Baiona (Pontevedra)',
        'instagram': 'Instagram',
    },
    'hero': {
        'kicker': 'Decoración de festas · Galicia',
        'title_before': 'Cóntanos como o',
        'title_script': 'soñaches',
        'lead': 'E encargámonos de que cada detalle da túa festa sexa como o imaxinas. Aniversarios, bautizos, comuñóns e vodas en Baiona, o Val Miñor e toda a provincia de Pontevedra.',
        'cta_primary': 'Pedir orzamento',
        'cta_secondary': 'Ver traballos',
        'image_alt': 'Arco de globos en rosa e dourado montado para unha celebración',
    },
    'services': {
        'kicker': 'O que montamos',
        'title': 'Cada celebración, montada desde cero',
        'lead': 'Ocupámonos do deseño, do material e da montaxe no sitio. Ti escolles a data e cóntasnos como a imaxinaras.',
        'from': 'desde :price',
        'on_request': 'a consultar',
        'empty': 'Estamos a preparar esta sección.',
    },
    'about': {
        'kicker': 'Quen somos',
        'title': 'Detrás de DecorArte está Sol Fernández',
        'placeholder': 'Aquí vai o texto de Sol: como empezou DecorArte, canto tempo leva montando festas e o que máis lle gusta facer. Escrito coas súas palabras, non coas dun folleto.',
        'who': 'Quen',
        'where': 'Onde',
        'area': 'Zona',
        'area_value': 'Val Miñor, Vigo, Baixo Miño e norte de Portugal',
        'contact': 'Contacto',
        'hours': 'Horario',
        'hours_value': 'Por confirmar',
    },
    'process': {
        'kicker': 'Sen sorpresas',
        'title': 'Como traballamos',
        'step1_title': 'Cóntasnolo',
        'step1_body': 'Por WhatsApp ou co formulario: data, sitio, cantos sodes e as fotos que che gustan.',
        'step2_title': 'Pasámosche o orzamento',
        'step2_body': 'Cos materiais e o prezo pechado. Se che encaixa, resérvase a data cun sinal.',
        'step3_title': 'Montamos e recollemos',
        'step3_body': 'Chegamos antes de que empece, deixámolo listo e volvemos retiralo todo.',
    },
    'projects': {
        'kicker': 'Festas de verdade',
        'title': 'Traballos recentes',
        'view_all': 'Ver máis en Instagram',
        'empty': 'Axiña publicaremos aquí as últimas montaxes.',
    },
    'rental': {
        'kicker': 'Só o material',
        'title': 'Móntala ti?',
        'lead': 'Algunhas pezas pódense alugar soltas por días. Consulta a dispoñibilidade da túa data e resérvao.',
        'per_day': ':price / día',
        'cta': 'Consultar dispoñibilidade',
        'empty': 'Estamos a preparar o catálogo de aluguer.',
    },
    'testimonials': {
        'kicker': 'O que din',
        'title': 'Clientes',
    },
    'faq': {
        'kicker': 'Dúbidas frecuentes',
        'title': 'Preguntas',
    },
    'contact': {
        'kicker': 'Cóntanolo',
        'title': 'Pide o teu orzamento',
        'lead': 'Escríbenos coa data e o lugar. Respondémosche por WhatsApp ou por teléfono.',
        'phone': 'Teléfono',
        'whatsapp_cta': 'Escribir agora',
        'where': 'Onde estamos',
        'hours': 'Horario',
    },
    'thanks': {
        'title': 'Recibido',
        'lead': 'Grazas por contarnos como a imaxinas. Respondémosche canto antes polo medio que nos deixaches.',
        'meanwhile': 'Mentres tanto podes escribirnos directamente por WhatsApp se o prefires.',
        'back': 'Volver ao inicio',
    },
    'footer': {
        'celebrations': 'Celebracións',
        'contact': 'Contacto',
        'area': 'Zona',
        'legal': 'Aviso legal',
        'privacy': 'Privacidade',
        'cookies': 'Cookies',
        'rights': 'Todos os dereitos reservados',
    },
    'meta': {
        'home_title': 'DecorArte · Decoración de festas en Baiona e Pontevedra',
        'home_description': 'Decoración de aniversarios, bautizos, comuñóns e vodas en Baiona, o Val Miñor e toda a provincia de Pontevedra. Cóntanos como o soñaches.',
    },
    'todo_hint': 'Pendente de confirmar',
}

SITE['pt'] = {
    'brand': {
        'name': 'DecorArte',
        'sub': 'Fiestas Mágicas',
        'tagline': 'Decoração de festas e cerimónias em Baiona, no Val Miñor e no norte de Portugal.',
    },
    'nav': {
        'celebrations': 'Celebrações',
        'works': 'Trabalhos',
        'areas': 'Zonas',
        'about': 'Quem somos',
        'process': 'Como trabalhamos',
        'rental': 'Aluguer',
        'contact': 'Contacto',
        'skip': 'Ir para o conteúdo',
        'main': 'Navegação principal',
        'language': 'Idioma',
    },
    'util': {
        'phone': 'Telefone',
        'whatsapp': 'WhatsApp',
        'where': 'Sabarís · Baiona (Pontevedra)',
        'instagram': 'Instagram',
    },
    'hero': {
        'kicker': 'Decoração de festas · Galiza',
        'title_before': 'Conta-nos como a',
        'title_script': 'sonhaste',
        'lead': 'E tratamos de que cada pormenor da tua festa fique como o imaginas. Aniversários, batizados, comunhões e casamentos em Baiona, no Val Miñor e em toda a província de Pontevedra.',
        'cta_primary': 'Pedir orçamento',
        'cta_secondary': 'Ver trabalhos',
        'image_alt': 'Arco de balões em rosa e dourado montado para uma celebração',
    },
    'services': {
        'kicker': 'O que montamos',
        'title': 'Cada celebração, montada de raiz',
        'lead': 'Tratamos do desenho, do material e da montagem no local. Escolhes a data e contas-nos como a tinhas imaginado.',
        'from': 'desde :price',
        'on_request': 'sob consulta',
        'empty': 'Estamos a preparar esta secção.',
    },
    'about': {
        'kicker': 'Quem somos',
        'title': 'Por trás da DecorArte está a Sol Fernández',
        'placeholder': 'Aqui vai o texto da Sol: como começou a DecorArte, há quanto tempo monta festas e o que mais gosta de fazer. Escrito com as palavras dela, não com as de um folheto.',
        'who': 'Quem',
        'where': 'Onde',
        'area': 'Zona',
        'area_value': 'Val Miñor, Vigo, Baixo Miño e norte de Portugal',
        'contact': 'Contacto',
        'hours': 'Horário',
        'hours_value': 'Por confirmar',
    },
    'process': {
        'kicker': 'Sem surpresas',
        'title': 'Como trabalhamos',
        'step1_title': 'Contas-nos',
        'step1_body': 'Por WhatsApp ou pelo formulário: data, local, quantos são e as fotos de que gostas.',
        'step2_title': 'Enviamos o orçamento',
        'step2_body': 'Com os materiais e o preço fechado. Se servir, a data fica reservada com um sinal.',
        'step3_title': 'Montamos e levantamos',
        'step3_body': 'Chegamos antes de começar, deixamos tudo pronto e voltamos para levantar.',
    },
    'projects': {
        'kicker': 'Festas a sério',
        'title': 'Trabalhos recentes',
        'view_all': 'Ver mais no Instagram',
        'empty': 'Em breve publicamos aqui as últimas montagens.',
    },
    'rental': {
        'kicker': 'Só o material',
        'title': 'Montas tu?',
        'lead': 'Algumas peças alugam-se à parte, por dias. Vê a disponibilidade da tua data e reserva.',
        'per_day': ':price / dia',
        'cta': 'Ver disponibilidade',
        'empty': 'Estamos a preparar o catálogo de aluguer.',
    },
    'testimonials': {
        'kicker': 'O que dizem',
        'title': 'Clientes',
    },
    'faq': {
        'kicker': 'Dúvidas frequentes',
        'title': 'Perguntas',
    },
    'contact': {
        'kicker': 'Conta-nos',
        'title': 'Pede o teu orçamento',
        'lead': 'Escreve-nos com a data e o local. Respondemos por WhatsApp ou por telefone.',
        'phone': 'Telefone',
        'whatsapp_cta': 'Escrever agora',
        'where': 'Onde estamos',
        'hours': 'Horário',
    },
    'thanks': {
        'title': 'Recebido',
        'lead': 'Obrigado por nos contares como a imaginas. Respondemos assim que pudermos, pelo meio que nos deixaste.',
        'meanwhile': 'Entretanto podes escrever-nos diretamente por WhatsApp, se preferires.',
        'back': 'Voltar ao início',
    },
    'footer': {
        'celebrations': 'Celebrações',
        'contact': 'Contacto',
        'area': 'Zona',
        'legal': 'Aviso legal',
        'privacy': 'Privacidade',
        'cookies': 'Cookies',
        'rights': 'Todos os direitos reservados',
    },
    'meta': {
        'home_title': 'DecorArte · Decoração de festas em Baiona e Pontevedra',
        'home_description': 'Decoração de aniversários, batizados, comunhões e casamentos em Baiona, no Val Miñor e em toda a província de Pontevedra. Conta-nos como a sonhaste.',
    },
    'todo_hint': 'Por confirmar',
}

FORMS = {}

FORMS['es'] = {
    'labels': {
        'name': 'Tu nombre',
        'contact': 'Cómo te avisamos',
        'email': 'Email',
        'phone': 'Teléfono o WhatsApp',
        'event_type': 'Tipo de celebración',
        'event_date': 'Fecha de la fiesta',
        'guests_count': 'Nº de invitados',
        'venue': 'Lugar',
        'message': 'Cuéntanos cómo la imaginas',
        'privacy': 'He leído y acepto la :link',
        'privacy_link': 'política de privacidad',
        'submit': 'Enviar solicitud',
    },
    'placeholders': {
        'name': 'María',
        'email': 'maria@ejemplo.com',
        'phone': '600 000 000',
        'venue': 'Salón, casa, playa…',
        'guests_count': '40',
        'message': 'Colores, temática, si has visto algo que te gustó…',
    },
    'help': {
        'contact': 'Con uno de los dos basta.',
        'date': 'Si aún no la tienes cerrada, déjalo en blanco.',
        'optional': 'opcional',
    },
    'attributes': {
        'name': 'nombre',
        'email': 'email',
        'phone': 'teléfono',
        'event_type': 'tipo de celebración',
        'event_date': 'fecha',
        'guests_count': 'número de invitados',
        'venue': 'lugar',
        'message': 'mensaje',
        'privacy': 'política de privacidad',
    },
    'errors': {
        'contact_required': 'Déjanos un email o un teléfono para poder responderte.',
        'phone_format': 'Ese teléfono no parece válido. Solo números, espacios y el prefijo.',
        'privacy': 'Necesitamos tu permiso para guardar estos datos y responderte.',
        'date_past': 'Esa fecha ya pasó. ¿Querías otro año?',
        'spam': 'No hemos podido procesar el formulario. Escríbenos por WhatsApp.',
        'required': 'Este campo es obligatorio.',
        'email_format': 'Ese email no parece válido. ¿Falta la @ o el punto?',
        'title': 'Revisa estos campos',
    },
}

FORMS['gl'] = {
    'labels': {
        'name': 'O teu nome',
        'contact': 'Como te avisamos',
        'email': 'Email',
        'phone': 'Teléfono ou WhatsApp',
        'event_type': 'Tipo de celebración',
        'event_date': 'Data da festa',
        'guests_count': 'Nº de convidados',
        'venue': 'Lugar',
        'message': 'Cóntanos como a imaxinas',
        'privacy': 'Lin e acepto a :link',
        'privacy_link': 'política de privacidade',
        'submit': 'Enviar solicitude',
    },
    'placeholders': {
        'name': 'María',
        'email': 'maria@exemplo.com',
        'phone': '600 000 000',
        'venue': 'Salón, casa, praia…',
        'guests_count': '40',
        'message': 'Cores, temática, se viches algo que che gustou…',
    },
    'help': {
        'contact': 'Cun dos dous abonda.',
        'date': 'Se aínda non a tes pechada, déixao en branco.',
        'optional': 'opcional',
    },
    'attributes': {
        'name': 'nome',
        'email': 'email',
        'phone': 'teléfono',
        'event_type': 'tipo de celebración',
        'event_date': 'data',
        'guests_count': 'número de convidados',
        'venue': 'lugar',
        'message': 'mensaxe',
        'privacy': 'política de privacidade',
    },
    'errors': {
        'contact_required': 'Déixanos un email ou un teléfono para poder responderche.',
        'phone_format': 'Ese teléfono non parece válido. Só números, espazos e o prefixo.',
        'privacy': 'Precisamos do teu permiso para gardar estes datos e responderche.',
        'date_past': 'Esa data xa pasou. Querías outro ano?',
        'spam': 'Non puidemos procesar o formulario. Escríbenos por WhatsApp.',
        'required': 'Este campo é obrigatorio.',
        'email_format': 'Ese email non parece válido. Falta a @ ou o punto?',
        'title': 'Revisa estes campos',
    },
}

FORMS['pt'] = {
    'labels': {
        'name': 'O teu nome',
        'contact': 'Como te avisamos',
        'email': 'Email',
        'phone': 'Telefone ou WhatsApp',
        'event_type': 'Tipo de celebração',
        'event_date': 'Data da festa',
        'guests_count': 'N.º de convidados',
        'venue': 'Local',
        'message': 'Conta-nos como a imaginas',
        'privacy': 'Li e aceito a :link',
        'privacy_link': 'política de privacidade',
        'submit': 'Enviar pedido',
    },
    'placeholders': {
        'name': 'Maria',
        'email': 'maria@exemplo.com',
        'phone': '910 000 000',
        'venue': 'Salão, casa, praia…',
        'guests_count': '40',
        'message': 'Cores, tema, se viste algo de que gostaste…',
    },
    'help': {
        'contact': 'Basta um dos dois.',
        'date': 'Se ainda não estiver fechada, deixa em branco.',
        'optional': 'opcional',
    },
    'attributes': {
        'name': 'nome',
        'email': 'email',
        'phone': 'telefone',
        'event_type': 'tipo de celebração',
        'event_date': 'data',
        'guests_count': 'número de convidados',
        'venue': 'local',
        'message': 'mensagem',
        'privacy': 'política de privacidade',
    },
    'errors': {
        'contact_required': 'Deixa-nos um email ou um telefone para podermos responder.',
        'phone_format': 'Esse telefone não parece válido. Só números, espaços e o indicativo.',
        'privacy': 'Precisamos da tua autorização para guardar estes dados e responder.',
        'date_past': 'Essa data já passou. Querias outro ano?',
        'spam': 'Não foi possível processar o formulário. Escreve-nos por WhatsApp.',
        'required': 'Este campo é obrigatório.',
        'email_format': 'Esse email não parece válido. Falta a @ ou o ponto?',
        'title': 'Revê estes campos',
    },
}


def keys(d, prefix=''):
    out = set()
    for k, v in d.items():
        path = f'{prefix}{k}'
        if isinstance(v, dict):
            out |= keys(v, path + '.')
        else:
            out.add(path)
    return out


def check(name, trees):
    ref_locale = 'es'
    ref = keys(trees[ref_locale])
    problems = []
    for locale, tree in trees.items():
        got = keys(tree)
        for missing in sorted(ref - got):
            problems.append(f'{name}: falta {locale}.{missing}')
        for extra in sorted(got - ref):
            problems.append(f'{name}: {locale}.{extra} existe mas não está em {ref_locale}')
    return problems, len(ref)


def php(data, indent=1):
    pad = '    ' * indent
    lines = []
    for k, v in data.items():
        if isinstance(v, dict):
            lines.append(f"{pad}'{k}' => [\n{php(v, indent + 1)}\n{pad}],")
        else:
            esc = v.replace('\\', '\\\\').replace("'", "\\'")
            lines.append(f"{pad}'{k}' => '{esc}',")
    return '\n'.join(lines)


problems = []
for name, trees in (('site', SITE), ('forms', FORMS)):
    p, n = check(name, trees)
    problems += p

if problems:
    print('PROBLEMAS DE TRADUÇÃO:')
    for p in problems:
        print('  -', p)
    sys.exit(1)

for name, trees in (('site', SITE), ('forms', FORMS)):
    total = len(keys(trees['es']))
    for locale, tree in trees.items():
        path = OUT / locale / f'{name}.php'
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(
            "<?php\n\ndeclare(strict_types=1);\n\n"
            f"/*\n * Texto do site. Gerado por gen_site_lang.py, que falha se\n"
            f" * alguma chave faltar num dos três idiomas.\n */\n\n"
            f"return [\n{php(tree)}\n];\n",
            encoding='utf-8')
    print(f'  {name}.php  ×3 idiomas  ({total} chaves cada)')

print('\nnenhuma chave em falta')
