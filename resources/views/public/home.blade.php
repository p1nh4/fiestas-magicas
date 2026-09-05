@php $b = config('business'); @endphp

<x-layouts.public>

    {{-- ==================== HERO ====================
         A foto ocupa o ecrã e o texto vai por cima. Nos sites reais do
         setor a fotografia é o design — quem procura decoração quer ver
         trabalho, não ler. --}}
    <section class="relative isolate bg-surface-2">
        <x-photo
            name="hero-arco-globos"
            :alt="__('site.hero.image_alt')"
            loading="eager"
            class="h-[clamp(370px,64vh,660px)] w-full object-cover"
        />

        <div class="absolute inset-0 bg-linear-to-b from-black/30 via-black/5 to-black/80"></div>

        <div class="absolute inset-x-0 bottom-0 mx-auto flex max-w-[1240px] flex-col items-start gap-4 px-4 pb-8 text-white sm:px-6 lg:px-14 lg:pb-12">
            <p class="kicker text-oro-soft">{{ __('site.hero.kicker') }}</p>

            <h1 class="max-w-[19ch] text-step-3 text-white [text-shadow:0_2px_18px_rgb(20_10_14/0.45)]">
                {{ __('site.hero.title_before') }}
                <em class="font-script text-[1.35em] not-italic text-oro-soft">{{ __('site.hero.title_script') }}</em>
            </h1>

            <p class="max-w-[44ch] text-white/90 [text-shadow:0_1px_10px_rgb(20_10_14/0.5)]">
                {{ __('site.hero.lead') }}
            </p>

            <div class="flex flex-wrap gap-2.5">
                <x-btn variant="wa" size="lg" href="#contacto">
                    <x-ico name="whatsapp" class="w-[1.05rem] h-[1.05rem]" />
                    {{ __('site.hero.cta_primary') }}
                </x-btn>
                <x-btn variant="line" size="lg" href="#celebraciones">
                    {{ __('site.hero.cta_secondary') }}
                </x-btn>
            </div>
        </div>
    </section>

    {{-- ==================== CINTA DE CATEGORIAS ==================== --}}
    <nav class="border-y border-line bg-rosa-wash" aria-label="{{ __('site.nav.celebrations') }}">
        <div class="mx-auto flex max-w-[1240px] gap-2 overflow-x-auto px-4 py-2.5 [scrollbar-width:none] sm:px-6 lg:px-14 [&::-webkit-scrollbar]:hidden">
            @foreach (\App\Enums\EventType::cases() as $type)
                <a href="#celebraciones"
                   class="shrink-0 rounded-full border border-line bg-surface px-3.5 py-1.5 text-[0.86rem] font-semibold text-ink-2 no-underline transition hover:border-rosa hover:text-rosa">
                    {{ $type->label() }}
                </a>
            @endforeach
        </div>
    </nav>

    {{-- ==================== CELEBRAÇÕES ==================== --}}
    <section id="celebraciones" class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 md:py-20 lg:px-14">
        <x-section-head
            :kicker="__('site.services.kicker')"
            :title="__('site.services.title')"
            :lead="__('site.services.lead')"
        />

        @if ($services->isEmpty())
            <p class="text-muted">{{ __('site.services.empty') }}</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services->take(6) as $service)
                    <a href="#contacto"
                       class="group relative block aspect-4/3 overflow-hidden rounded-card bg-surface-2 no-underline">
                        <x-photo
                            :name="data_get($service->seo, 'image', 'cumpleanos')"
                            :alt="$service->name"
                            class="h-full w-full object-cover transition duration-700 ease-[var(--ease-out-soft)] group-hover:scale-105"
                        />
                        <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 bg-linear-to-t from-black/80 to-transparent px-4 pb-3.5 pt-10 text-white">
                            <h3 class="text-step-1 text-white">{{ $service->name }}</h3>
                            <small class="whitespace-nowrap opacity-90">
                                @if ((float) $service->base_price > 0)
                                    {{ __('site.services.from', ['price' => number_format((float) $service->base_price, 0, ',', '.').' €']) }}
                                @else
                                    <span class="todo-mark" title="{{ __('site.todo_hint') }}">{{ __('site.services.on_request') }}</span>
                                @endif
                            </small>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ==================== QUEM SOMOS ==================== --}}
    <section id="nosotras" class="border-y border-line bg-oro-wash">
        <div class="mx-auto grid max-w-[1240px] items-center gap-8 px-4 py-12 sm:px-6 md:grid-cols-[1fr_1.1fr] md:py-20 lg:px-14">
            <div class="aspect-5/4 overflow-hidden rounded-card bg-surface-2">
                <x-photo name="globos" alt="{{ __('site.about.title') }}" />
            </div>

            <div>
                <p class="kicker">{{ __('site.about.kicker') }}</p>
                <h2 class="mb-3 mt-2 text-step-2">{{ __('site.about.title') }}</h2>
                <hr class="mb-4 h-0.5 w-10 border-0 bg-oro">

                <p class="max-w-[56ch] text-ink-2">
                    <span class="todo-mark" title="{{ __('site.todo_hint') }}">{{ __('site.about.placeholder') }}</span>
                </p>

                <dl class="mt-6 grid gap-2 border-t border-line-2 pt-5 text-[0.92rem]">
                    <div class="flex items-baseline gap-3">
                        <dt class="w-32 shrink-0 text-[0.82rem] uppercase tracking-wide text-muted">{{ __('site.about.who') }}</dt>
                        <dd class="m-0 font-semibold">{{ $b['owner'] }}</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="w-32 shrink-0 text-[0.82rem] uppercase tracking-wide text-muted">{{ __('site.about.where') }}</dt>
                        <dd class="m-0 font-semibold">{{ $b['address']['locality'] }} ({{ $b['address']['region'] }})</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="w-32 shrink-0 text-[0.82rem] uppercase tracking-wide text-muted">{{ __('site.about.area') }}</dt>
                        <dd class="m-0 font-semibold">{{ __('site.about.area_value') }}</dd>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <dt class="w-32 shrink-0 text-[0.82rem] uppercase tracking-wide text-muted">{{ __('site.about.hours') }}</dt>
                        <dd class="m-0 font-semibold">
                            @if ($b['opening_hours'])
                                {{ $b['opening_hours'] }}
                            @else
                                <span class="todo-mark" title="{{ __('site.todo_hint') }}">{{ __('site.about.hours_value') }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- ==================== COMO TRABALHAMOS ====================
         Três passos, não quatro. Quatro cartões iguais em cada secção é
         exatamente o ritmo que denuncia um site feito por máquina. --}}
    <section id="como" class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 md:py-20 lg:px-14">
        <x-section-head :kicker="__('site.process.kicker')" :title="__('site.process.title')" />

        <ol class="grid list-none gap-6 p-0 md:grid-cols-3 md:gap-8">
            @foreach ([1, 2, 3] as $n)
                <li class="flex items-start gap-4">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-rosa-soft font-display text-base text-rosa-deep">
                        {{ ['I', 'II', 'III'][$n - 1] }}
                    </span>
                    <div>
                        <h3 class="mb-1 text-step-1">{{ __("site.process.step{$n}_title") }}</h3>
                        <p class="text-[0.94rem] text-ink-2">{{ __("site.process.step{$n}_body") }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ==================== TRABALHOS ====================
         Só aparece se houver trabalhos publicados COM autorização do
         cliente. Sem isso, a secção não existe. --}}
    @if ($projects->isNotEmpty())
        <section id="trabajos" class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 md:py-20 lg:px-14">
            <x-section-head :kicker="__('site.projects.kicker')" :title="__('site.projects.title')" />

            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                @foreach ($projects as $project)
                    <a href="#trabajos" class="group relative block aspect-square overflow-hidden rounded-md bg-surface-2 no-underline">
                        <x-photo
                            :name="data_get($project->seo, 'image', 'mesa-dulce')"
                            :alt="$project->title"
                            class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                        />
                        <span class="absolute bottom-2 left-2 rounded-full bg-surface px-3 py-1 text-[0.78rem] font-bold shadow-soft">
                            {{ $project->title }}
                        </span>
                    </a>
                @endforeach
            </div>

            @if ($b['instagram'])
                <p class="mt-5">
                    <a href="https://instagram.com/{{ $b['instagram'] }}" rel="noopener"
                       class="inline-flex items-center gap-1.5 font-semibold text-rosa no-underline hover:underline">
                        {{ __('site.projects.view_all') }}
                        <x-ico name="arrow-right" class="w-4 h-4" />
                    </a>
                </p>
            @endif
        </section>
    @endif

    {{-- ==================== ALUGUER ==================== --}}
    @if ($rentals->isNotEmpty())
        <section id="alquiler" class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 md:py-20 lg:px-14">
            <div class="grid items-center gap-8 md:grid-cols-[1.1fr_1fr]">
                <div class="aspect-16/10 overflow-hidden rounded-card bg-surface-2">
                    <x-photo name="material" :alt="__('site.rental.title')" />
                </div>

                <div>
                    <p class="kicker">{{ __('site.rental.kicker') }}</p>
                    <h2 class="mb-3 mt-2 text-step-2">{{ __('site.rental.title') }}</h2>
                    <hr class="mb-4 h-0.5 w-10 border-0 bg-oro">
                    <p class="max-w-[50ch] text-ink-2">{{ __('site.rental.lead') }}</p>

                    <dl class="my-6 border-t border-line">
                        @foreach ($rentals as $item)
                            <div class="flex items-baseline gap-3 border-b border-line py-2.5">
                                <dt class="font-semibold">{{ $item->name }}</dt>
                                <span class="flex-1 -translate-y-1 border-b border-dotted border-line-2"></span>
                                <dd class="m-0 font-bold tabular-nums text-oro">
                                    @if ((float) $item->price_per_day > 0)
                                        {{ __('site.rental.per_day', ['price' => number_format((float) $item->price_per_day, 2, ',', '.').' €']) }}
                                    @else
                                        <span class="todo-mark" title="{{ __('site.todo_hint') }}">— €</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    <x-btn variant="line" size="lg" href="#contacto">{{ __('site.rental.cta') }}</x-btn>
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== TESTEMUNHOS ====================
         Só entram testemunhos reais e com consentimento — a base de dados
         exige consent_at. Se não houver nenhum, a secção não aparece.
         Nenhum dos sites do setor que servem de referência tem esta secção
         sequer, por isso não há pressa nenhuma em enchê-la. --}}
    @if ($testimonials->isNotEmpty())
        <section class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 md:py-20 lg:px-14">
            <x-section-head :kicker="__('site.testimonials.kicker')" :title="__('site.testimonials.title')" />

            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($testimonials as $testimonial)
                    <figure class="rounded-card border border-line bg-surface p-6">
                        <blockquote class="text-[0.96rem]">&ldquo;{{ $testimonial->body }}&rdquo;</blockquote>
                        <figcaption class="mt-4 text-[0.82rem] font-bold text-muted">
                            {{ $testimonial->author_name }}
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ==================== PERGUNTAS ==================== --}}
    @if ($faqs->isNotEmpty())
        <section class="mx-auto max-w-[1240px] px-4 py-12 sm:px-6 md:py-16 lg:px-14">
            <x-section-head :kicker="__('site.faq.kicker')" :title="__('site.faq.title')" />

            <div class="max-w-[70ch] divide-y divide-line border-y border-line">
                @foreach ($faqs as $faq)
                    <details class="group py-4">
                        <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold marker:content-none">
                            {{ $faq->question }}
                            <span class="text-rosa transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-2 text-[0.94rem] text-ink-2">{{ $faq->answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ==================== CONTACTO ==================== --}}
    <section id="contacto" class="border-y border-line bg-surface">
        <div class="mx-auto grid max-w-[1240px] gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.15fr_0.85fr] md:py-20 lg:px-14">
            <div>
                <p class="kicker">{{ __('site.contact.kicker') }}</p>
                <h2 class="mb-3 mt-2 text-step-3">{{ __('site.contact.title') }}</h2>
                <hr class="mb-5 h-0.5 w-10 border-0 bg-oro">
                <p class="mb-6 max-w-[50ch] text-ink-2">{{ __('site.contact.lead') }}</p>

                @include('partials.lead-form')
            </div>

            <aside class="grid content-start gap-3">
                <div class="flex items-start gap-3 rounded-card border border-line bg-ground p-4">
                    <x-ico name="phone" class="mt-1 w-[1.15rem] h-[1.15rem] shrink-0 text-rosa" />
                    <div>
                        <b class="block text-[0.82rem] font-bold uppercase tracking-wider text-muted">{{ __('site.contact.phone') }}</b>
                        <a href="tel:{{ $b['phone'] }}" class="no-underline hover:text-rosa">{{ $b['phone_display'] }}</a>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-card border border-line bg-ground p-4">
                    <x-ico name="whatsapp" class="mt-1 w-[1.15rem] h-[1.15rem] shrink-0 text-rosa" />
                    <div>
                        <b class="block text-[0.82rem] font-bold uppercase tracking-wider text-muted">{{ __('site.util.whatsapp') }}</b>
                        <a href="https://wa.me/{{ $b['whatsapp'] }}" rel="noopener" class="no-underline hover:text-rosa">{{ __('site.contact.whatsapp_cta') }}</a>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-card border border-line bg-ground p-4">
                    <x-ico name="pin" class="mt-1 w-[1.15rem] h-[1.15rem] shrink-0 text-rosa" />
                    <div>
                        <b class="block text-[0.82rem] font-bold uppercase tracking-wider text-muted">{{ __('site.contact.where') }}</b>
                        <span>{{ $b['address']['locality'] }} {{ $b['address']['postal_code'] }}<br>{{ $b['address']['region'] }}</span>
                    </div>
                </div>

                @if ($b['instagram'])
                    <div class="flex items-start gap-3 rounded-card border border-line bg-ground p-4">
                        <x-ico name="instagram" class="mt-1 w-[1.15rem] h-[1.15rem] shrink-0 text-rosa" />
                        <div>
                            <b class="block text-[0.82rem] font-bold uppercase tracking-wider text-muted">{{ __('site.util.instagram') }}</b>
                            <a href="https://instagram.com/{{ $b['instagram'] }}" rel="noopener" class="no-underline hover:text-rosa">&#64;{{ $b['instagram'] }}</a>
                        </div>
                    </div>
                @endif

                <div class="flex items-start gap-3 rounded-card border border-line bg-ground p-4">
                    <x-ico name="clock" class="mt-1 w-[1.15rem] h-[1.15rem] shrink-0 text-rosa" />
                    <div>
                        <b class="block text-[0.82rem] font-bold uppercase tracking-wider text-muted">{{ __('site.contact.hours') }}</b>
                        <span class="todo-mark" title="{{ __('site.todo_hint') }}">{{ __('site.about.hours_value') }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>

</x-layouts.public>
