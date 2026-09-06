{{--
    Formulário normal: POST, recarregamento da página, validação no
    StoreLeadRequest.

    Sem Livewire, e a decisão é deliberada. O Livewire está no projeto e é a
    escolha certa onde está — o backoffice inteiro é Filament, que é Livewire
    por baixo. Aqui não: com Livewire, submeter passa a depender de o JS ter
    carregado, e a maior parte destas visitas chega pelo browser embutido do
    Instagram, com ligação má. Um formulário que às vezes não submete perde
    clientes; um que não valida à medida que se escreve só chateia.

    O `resources/js/app.js` acrescenta a validação inline por cima disto, sem
    tocar no caminho de submissão: se o JS não carregar, não se perde nada
    além do conforto. Segurança não muda em nada — quem decide é sempre o
    servidor.
--}}

@if ($errors->any())
    <div role="alert" class="mb-5 rounded-card border border-rosa bg-rosa-wash px-4 py-3">
        <p class="font-semibold text-rosa-deep">{{ __('forms.errors.title') }}</p>
        <ul class="mt-1 list-disc pl-5 text-[0.9rem] text-ink-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- As mensagens da validação inline (resources/js/app.js) saem daqui, do
     lang/, e não do JavaScript: o site tem três idiomas e uma frase
     escrita num .js só saberia um. Sem JS, este atributo não faz nada. --}}
<form method="POST" action="{{ route('lead.store') }}" class="grid gap-4"
      data-validacion="{{ json_encode([
          'required' => __('forms.errors.required'),
          'email_format' => __('forms.errors.email_format'),
          'phone_format' => __('forms.errors.phone_format'),
          'contact_required' => __('forms.errors.contact_required'),
          'privacy' => __('forms.errors.privacy'),
          'date_past' => __('forms.errors.date_past'),
      ], JSON_UNESCAPED_UNICODE) }}">
    @csrf

    {{-- Armadilha para robots. Um humano nunca vê isto nem o preenche.
         Fica fora do ecrã em vez de display:none porque alguns robots
         ignoram campos escondidos por CSS mas preenchem estes. --}}
    <div class="absolute -left-[9999px]" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    {{-- Origem da visita. Sem isto não há forma de saber se vale a pena
         pagar publicidade ou se é o Instagram que traz os clientes. --}}
    @foreach (['utm_source', 'utm_medium', 'utm_campaign'] as $utm)
        @if (request()->filled($utm))
            <input type="hidden" name="{{ $utm }}" value="{{ request()->string($utm)->limit(120) }}">
        @endif
    @endforeach

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-1">
            <label for="f-name" class="text-[0.82rem] font-semibold text-ink-2">{{ __('forms.labels.name') }}</label>
            <input id="f-name" name="name" type="text" autocomplete="name" required
                   value="{{ old('name') }}"
                   placeholder="{{ __('forms.placeholders.name') }}"
                   @error('name') aria-invalid="true" aria-describedby="e-name" @enderror
                   class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">
            @error('name') <p id="e-name" class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-1">
            <label for="f-phone" class="text-[0.82rem] font-semibold text-ink-2">{{ __('forms.labels.phone') }}</label>
            <input id="f-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel"
                   value="{{ old('phone') }}"
                   placeholder="{{ __('forms.placeholders.phone') }}"
                   @error('phone') aria-invalid="true" aria-describedby="e-phone" @enderror
                   class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">
            @error('phone') <p id="e-phone" class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-1">
        <label for="f-email" class="text-[0.82rem] font-semibold text-ink-2">
            {{ __('forms.labels.email') }}
            <span class="font-normal text-muted">({{ __('forms.help.contact') }})</span>
        </label>
        <input id="f-email" name="email" type="email" autocomplete="email" inputmode="email"
               value="{{ old('email') }}"
               placeholder="{{ __('forms.placeholders.email') }}"
               @error('email') aria-invalid="true" aria-describedby="e-email" @enderror
               class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">
        @error('email') <p id="e-email" class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-1">
            <label for="f-type" class="text-[0.82rem] font-semibold text-ink-2">{{ __('forms.labels.event_type') }}</label>
            <select id="f-type" name="event_type" required
                    class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">
                @foreach (\App\Enums\EventType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('event_type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('event_type') <p class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-1">
            <label for="f-date" class="text-[0.82rem] font-semibold text-ink-2">
                {{ __('forms.labels.event_date') }}
                <span class="font-normal text-muted">({{ __('forms.help.optional') }})</span>
            </label>
            <input id="f-date" name="event_date" type="date" min="{{ now()->toDateString() }}"
                   value="{{ old('event_date') }}"
                   class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">
            @error('event_date') <p class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-1">
            <label for="f-venue" class="text-[0.82rem] font-semibold text-ink-2">{{ __('forms.labels.venue') }}</label>
            <input id="f-venue" name="venue" type="text" value="{{ old('venue') }}"
                   placeholder="{{ __('forms.placeholders.venue') }}"
                   class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">
            @error('venue') <p class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-1">
            <label for="f-guests" class="text-[0.82rem] font-semibold text-ink-2">{{ __('forms.labels.guests_count') }}</label>
            <input id="f-guests" name="guests_count" type="number" min="1" max="2000" inputmode="numeric"
                   value="{{ old('guests_count') }}"
                   placeholder="{{ __('forms.placeholders.guests_count') }}"
                   class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">
            @error('guests_count') <p class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-1">
        <label for="f-message" class="text-[0.82rem] font-semibold text-ink-2">{{ __('forms.labels.message') }}</label>
        <textarea id="f-message" name="message" rows="4"
                  placeholder="{{ __('forms.placeholders.message') }}"
                  class="w-full rounded border border-line-2 bg-ground px-3 py-2.5 text-[0.96rem] text-ink">{{ old('message') }}</textarea>
        @error('message') <p class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror
    </div>

    {{-- Nunca pré-marcada: o RGPD exige um ato afirmativo de quem consente. --}}
    <div class="flex items-start gap-2.5">
        <input id="f-privacy" name="privacy" type="checkbox" value="1" required
               @checked(old('privacy'))
               class="mt-1 h-4 w-4 shrink-0 accent-[var(--color-rosa)]">
        {{--
            O href="#" que aqui estava era um problema a serio: pedia-se
            consentimento RGPD apontando para o vazio. Agora ou ha politica
            publicada e o texto e uma ligacao, ou nao ha e fica so texto —
            nunca uma promessa falsa.
        --}}
        @php
            $privacyUrl = \App\Models\Page::urlFor(\App\Models\Page::PRIVACY);
            $privacyText = e(__('forms.labels.privacy_link'));
        @endphp

        <label for="f-privacy" class="text-[0.86rem] text-ink-2">
            {!! __('forms.labels.privacy', [
                'link' => $privacyUrl
                    ? '<a href="'.e($privacyUrl).'" target="_blank" rel="noopener" class="text-rosa underline">'.$privacyText.'</a>'
                    : $privacyText,
            ]) !!}
        </label>
    </div>
    @error('privacy') <p class="text-[0.8rem] text-rosa-deep">{{ $message }}</p> @enderror

    <x-btn variant="rosa" size="lg" type="submit" class="justify-self-start">
        {{ __('forms.labels.submit') }}
    </x-btn>
</form>
