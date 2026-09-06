{{--
    A guia da Sol.

    Escrita em espanhol e em segunda pessoa, porque é ela que a lê. Não
    explica o que cada ecrã tem — isso vê-se — mas o que fazer primeiro, as
    regras que o sistema impõe e que sem explicação parecem teimosia dele, e
    o que fazer quando algo sai da ordem prevista.

    A versão anterior dizia o mesmo em parágrafos seguidos, todos do mesmo
    tamanho e da mesma cor. Estava certa e não se lia: quem abre uma página
    de ajuda não a lê do princípio ao fim, procura a sua dúvida. Daí os
    cartões, os ícones e o "¿Qué hago si...?" — a mesma matéria, arrumada
    para ser varrida com os olhos.

    Sem estado, sem formulários, sem consultas à base de dados além dos
    endereços dos atalhos. Uma página de ajuda que pode falhar é uma página
    de ajuda que falha quando mais falta faz.
--}}
@php
    use App\Filament\Resources\EventDesigns\EventDesignResource;
    use App\Filament\Resources\Events\EventResource;
    use App\Filament\Resources\Items\ItemResource;
    use App\Filament\Resources\Leads\LeadResource;
    use App\Filament\Resources\Payments\PaymentResource;
    use App\Filament\Resources\Projects\ProjectResource;
    use App\Filament\Resources\Quotes\QuoteResource;
    use App\Filament\Resources\Reservations\ReservationResource;

    $atajos = [
        ['Solicitudes', 'heroicon-o-inbox', LeadResource::getUrl()],
        ['Eventos', 'heroicon-o-calendar-days', EventResource::getUrl()],
        ['Proyectos', 'heroicon-o-sparkles', EventDesignResource::getUrl()],
        ['Presupuestos', 'heroicon-o-document-text', QuoteResource::getUrl()],
        ['Pagos', 'heroicon-o-banknotes', PaymentResource::getUrl()],
        ['Material reservado', 'heroicon-o-cube', ReservationResource::getUrl()],
    ];

    $pasos = [
        ['heroicon-o-inbox', 'Solicitudes',
         'Llega sola, del formulario de la web. Ábrela y pulsa <strong>Convertir en cliente</strong>: te crea la ficha y un evento en borrador, sin volver a escribir nada.'],
        ['heroicon-o-calendar-days', 'Eventos',
         'Fecha, sitio y horas. Ojo: las que reservan el material son las de <strong>montaje y recogida</strong>, no las de la fiesta.'],
        ['heroicon-o-sparkles', 'Proyectos',
         'Aquí piensas la fiesta: tema, colores, fotos de referencia y las piezas que quieres llevar. Todavía no reserva nada.'],
        ['heroicon-o-document-text', 'Presupuestos',
         'Desde el proyecto, <strong>Pasar al presupuesto</strong> vuelca la lista con sus precios. Repasa, añade servicios y <strong>Enviar</strong>.'],
        ['heroicon-o-link', 'El enlace',
         'Copia el enlace y mándaselo por WhatsApp. Lo abre sin crear cuenta ni contraseña, y acepta ahí mismo.'],
        ['heroicon-o-check-circle', 'Al aceptar',
         'El evento se confirma y el material queda apartado, todo de golpe. Tú no tienes que hacer nada.'],
        ['heroicon-o-banknotes', 'Pagos',
         'La señal por tarjeta se marca sola. La que te dan en mano o por Bizum la marcas tú con un botón.'],
    ];

    $dudas = [
        ['Una clienta quiere cambiar algo de un presupuesto que ya envié',
         'Ábrelo y crea la <strong>versión siguiente</strong>. La anterior se queda tal cual, y le mandas el enlace nuevo. No es burocracia: lo que ella vio cuando aceptó es la única prueba de lo que acordasteis.'],
        ['Me ha pagado la señal en mano, por Bizum o por transferencia',
         'Ve a <strong>Pagos</strong> y márcalo como pagado. El botón escribe la fecha y el método; no hay formulario que rellenar, y así no se puede apuntar dos veces el mismo dinero.'],
        ['Quiero publicar las fotos de una fiesta',
         '<strong>Trabajos</strong> → nueva entrada. Sube las fotos (la primera es la portada, y es la que se ve al compartir el enlace) y rellena la <strong>fecha del permiso</strong> de la clienta. Sin esa fecha no se publica: son fotos de la casa y de los hijos de otra persona.'],
        ['Dos clientas quieren la misma pieza el mismo día',
         'Se la lleva la primera que <strong>acepte</strong> el presupuesto; a la segunda el sistema ya no se lo deja aceptar. Antes de prometer nada, usa <strong>¿Hay material?</strong> dentro del proyecto: te responde para esas fechas.'],
        ['Una clienta no me contesta',
         'No hagas nada. A los quince días el presupuesto se marca solo como caducado y desaparece de la lista de los que esperan respuesta. Si vuelve, le haces uno nuevo.'],
        ['Me he equivocado al convertir una solicitud',
         'El cliente y el evento nacen en borrador: archívalos y sigue. Nada se borra del todo, así que si te arrepientes se recuperan.'],
    ];

    $reglas = [
        ['Cambiar un presupuesto ya enviado', 'Se crea la versión siguiente. Lo que la clienta vio cuando aceptó tiene que quedar tal cual.'],
        ['Publicar un trabajo sin permiso', 'Sin la fecha del permiso guardada, la base de datos rechaza publicarlo.'],
        ['Publicar una zona sin texto propio', 'Once páginas iguales con el nombre cambiado hacen que Google se fíe menos de la web entera.'],
        ['Crear una solicitud a mano', 'Una solicitud es alguien que escribió desde la web. Si las inventas, dejas de saber de dónde vienen tus clientas.'],
        ['Tocar los totales', 'Salen siempre de las líneas. Si el total no cuadra, lo que se arregla es una línea.'],
        ['Apartar material a mano', 'El material se aparta cuando la clienta acepta. Para bloquear una pieza rota hay un botón en Material.'],
    ];

    $visible = [
        ['Servicios', 'La portada y una página por servicio'],
        ['Trabajos', 'La galería de trabajos y la página de cada fiesta'],
        ['Material', 'El catálogo de alquiler, con consulta de fechas'],
        ['Zonas', 'Una página por concejo'],
        ['Preguntas', 'Las preguntas frecuentes de la portada'],
        ['Opiniones', 'Los testimonios, solo los que tienen consentimiento'],
        ['Páginas', 'Aviso legal, privacidad y cookies'],
        ['Clientes, Eventos, Proyectos, Presupuestos, Pagos', 'Nada. Son tuyos.'],
    ];
@endphp

<x-filament-panels::page>
    <div class="space-y-8">

        {{-- Bienvenida --}}
        <div class="rounded-xl bg-primary-50 p-6 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/20">
            <div class="flex items-start gap-4">
                <span class="hidden shrink-0 rounded-lg bg-white/70 p-2.5 text-primary-600 sm:block dark:bg-white/5 dark:text-primary-400">
                    @svg('heroicon-o-lifebuoy', 'h-6 w-6')
                </span>
                <div class="space-y-1.5">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Hola, Sol</h2>
                    <p class="max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                        Esta página no se va a ninguna parte: vuelve siempre que tengas una duda.
                        Abajo tienes el camino completo de una petición hasta cobrarla, y después
                        las dudas que salen de verdad cuando algo no va como estaba previsto.
                        No hace falta acordarse de nada — todo deja rastro, y lo que quedó a
                        medias aparece en la portada.
                    </p>
                </div>
            </div>
        </div>

        {{-- Atajos --}}
        <section class="space-y-3">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Ir directamente a</h2>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($atajos as [$etiqueta, $icono, $url])
                    <a href="{{ $url }}"
                       class="group flex flex-col items-center gap-2 rounded-lg bg-white p-3 text-center ring-1 ring-gray-950/5 transition hover:ring-primary-500 dark:bg-white/5 dark:ring-white/10 dark:hover:ring-primary-400">
                        <span class="text-gray-400 transition group-hover:text-primary-600 dark:group-hover:text-primary-400">
                            @svg($icono, 'h-5 w-5')
                        </span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $etiqueta }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- El camino --}}
        <section class="space-y-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">De una petición a una fiesta cobrada</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Siete pasos, siempre en este orden.</p>
            </div>

            <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($pasos as $i => [$icono, $titulo, $texto])
                    <li class="relative flex flex-col gap-2 rounded-lg bg-white p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">
                                {{ $i + 1 }}
                            </span>
                            <span class="text-gray-400">@svg($icono, 'h-5 w-5')</span>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $titulo }}</h3>
                        </div>
                        <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{!! $texto !!}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ¿Qué hago si...? --}}
        <section class="space-y-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">¿Qué hago si…?</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pulsa en la que te pase. Son las que salen de verdad.</p>
            </div>

            <div class="divide-y divide-gray-100 overflow-hidden rounded-lg bg-white ring-1 ring-gray-950/5 dark:divide-white/5 dark:bg-white/5 dark:ring-white/10">
                {{-- <details> e não JavaScript: abre e fecha sozinho, e continua
                     a abrir se um dia o painel ficar sem assets. --}}
                @foreach ($dudas as [$pregunta, $respuesta])
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center gap-3 p-4 text-sm font-medium text-gray-950 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">
                            <span class="shrink-0 text-gray-400 transition group-open:rotate-90">
                                @svg('heroicon-o-chevron-right', 'h-4 w-4')
                            </span>
                            {{ $pregunta }}
                        </summary>
                        <p class="px-4 pb-4 pl-11 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            {!! $respuesta !!}
                        </p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- Reglas --}}
        <section class="space-y-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Cosas que el sistema no te va a dejar hacer</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">No son fallos. Cada una está ahí porque lo contrario sale caro.</p>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($reglas as [$que, $porque])
                    <div class="flex gap-3 rounded-lg bg-amber-50 p-3.5 ring-1 ring-amber-200/70 dark:bg-amber-400/10 dark:ring-amber-400/20">
                        <span class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400">
                            @svg('heroicon-o-exclamation-triangle', 'h-5 w-5')
                        </span>
                        <div>
                            <h3 class="text-sm font-medium text-gray-950 dark:text-white">{{ $que }}</h3>
                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">{{ $porque }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Qué ve la clienta --}}
        <section class="space-y-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Qué de esto ve la clienta</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">La mitad de lo que hay aquí es tuyo y no sale de aquí.</p>
            </div>

            <div class="overflow-hidden rounded-lg bg-white ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Aquí</th>
                            <th class="px-4 py-2.5 font-medium">En la web</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($visible as [$aqui, $alla])
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-gray-950 dark:text-white">{{ $aqui }}</td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ $alla }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Casi todo lo de la web tiene un interruptor de <strong>publicado</strong>.
                Mientras esté apagado puedes escribirlo con calma: nadie lo ve.
            </p>
        </section>

        {{-- Idiomas + si algo va mal --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="space-y-2.5 rounded-lg bg-white p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">@svg('heroicon-o-globe-alt', 'h-5 w-5')</span>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Los tres idiomas</h2>
                </div>
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    Lo que se publica se escribe en español, gallego y portugués: son tres
                    mercados, no una traducción de cortesía. El español es obligatorio; si
                    dejas otro en blanco, esa página no existe en ese idioma — y es mejor así
                    que una página a medias.
                </p>
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    Los correos van en el idioma en que te escribió la clienta. De eso no
                    tienes que ocuparte.
                </p>
            </section>

            <section class="space-y-2.5 rounded-lg bg-white p-5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">@svg('heroicon-o-question-mark-circle', 'h-5 w-5')</span>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Si algo va mal</h2>
                </div>
                <ul class="space-y-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    <li class="flex gap-2"><span class="text-gray-300 dark:text-gray-600">—</span> Nada se borra del todo: clientes y eventos se archivan y se recuperan.</li>
                    <li class="flex gap-2"><span class="text-gray-300 dark:text-gray-600">—</span> Si un botón te dice que no hay material suficiente, es un aviso, no una prohibición. Tú decides.</li>
                    <li class="flex gap-2"><span class="text-gray-300 dark:text-gray-600">—</span> Si la web se ve rara después de cambiar algo, espera un minuto y recarga.</li>
                    <li class="flex gap-2"><span class="text-gray-300 dark:text-gray-600">—</span> Y si algo no encaja con cómo trabajas, díselo a David: esto se cambia.</li>
                </ul>
            </section>
        </div>
    </div>
</x-filament-panels::page>
