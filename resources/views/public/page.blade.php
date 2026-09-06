{{--
    Páginas de texto: aviso legal, privacidade, cookies.

    Coluna estreita e nada mais. São páginas para ler, não para impressionar
    — e uma política de privacidade que ninguém consegue ler não cumpre o
    que a lei pede dela.
--}}
<x-layouts.public
    :title="$page->seo['title'] ?? $page->title"
    :description="$page->seo['description'] ?? null">

    <section class="mx-auto max-w-[68ch] px-4 py-16 sm:px-6 md:py-24 lg:px-14">
        <x-section-head level="h1" :title="$page->title" />

        <div class="space-y-4 text-ink-2">
            @foreach (preg_split('/\R{2,}/', trim((string) $page->body)) as $block)
                @php $block = trim($block); @endphp

                @if ($block === '')
                    @continue
                @elseif (str_starts_with($block, '## '))
                    <h2 class="text-step-1 pt-4 text-ink">{{ substr($block, 3) }}</h2>
                @elseif (str_starts_with($block, '- '))
                    {{-- Uma lista: linhas seguidas começadas por "- ". --}}
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach (preg_split('/\R/', $block) as $line)
                            @if (str_starts_with(trim($line), '- '))
                                <li>{{ substr(trim($line), 2) }}</li>
                            @endif
                        @endforeach
                    </ul>
                @else
                    <p>{{ $block }}</p>
                @endif
            @endforeach
        </div>
    </section>
</x-layouts.public>
