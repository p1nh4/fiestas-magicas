@php
    $b = config('business');
    $event = $quote->event;
    $accepted = $quote->status === \App\Enums\QuoteStatus::Accepted;
    $rejected = $quote->status === \App\Enums\QuoteStatus::Rejected;
    $open = in_array($quote->status, [\App\Enums\QuoteStatus::Sent, \App\Enums\QuoteStatus::Viewed], true)
            && ! $quote->isExpired();
    $money = fn ($v) => number_format((float) $v, 2, ',', '.').' €';
@endphp

<x-layouts.public :title="__('quotes.meta.title', ['number' => 'v'.$quote->version])">
    <section class="mx-auto max-w-[900px] px-4 py-10 sm:px-6 md:py-16 lg:px-14">

        {{-- ---------- avisos ---------- --}}
        @if (session('quote_error'))
            <div role="alert" class="mb-6 rounded-card border border-rosa bg-rosa-wash px-4 py-3 text-rosa-deep">
                {{ session('quote_error') }}
            </div>
        @endif

        @if (session('quote_accepted'))
            <div role="status" class="mb-6 rounded-card border border-whatsapp bg-white px-4 py-3">
                <p class="font-semibold text-whatsapp">{{ __('quotes.accepted_title') }}</p>
                <p class="text-ink-2">{{ __('quotes.accepted_body') }}</p>
            </div>
        @endif

        @if (session('payment_confirmed'))
            <div role="status" class="mb-6 rounded-card border border-whatsapp bg-white px-4 py-3 font-semibold text-whatsapp">
                {{ __('quotes.payment_confirmed') }}
            </div>
        @endif

        {{-- ---------- cabeçalho ---------- --}}
        <p class="kicker">{{ __('quotes.kicker') }}</p>
        <h1 class="mt-2 text-step-3">{{ $event->title }}</h1>
        <hr class="mb-6 mt-3 h-0.5 w-10 border-0 bg-oro">

        <dl class="mb-8 grid gap-2 text-[0.94rem] sm:grid-cols-2">
            <div class="flex gap-2">
                <dt class="text-muted">{{ __('quotes.for') }}</dt>
                <dd class="m-0 font-semibold">{{ $event->client?->name }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-muted">{{ __('quotes.date') }}</dt>
                <dd class="m-0 font-semibold">{{ $event->starts_at->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}</dd>
            </div>
            @if ($event->venue_name)
                <div class="flex gap-2">
                    <dt class="text-muted">{{ __('quotes.venue') }}</dt>
                    <dd class="m-0 font-semibold">{{ $event->venue_name }}</dd>
                </div>
            @endif
            <div class="flex gap-2">
                <dt class="text-muted">{{ __('quotes.valid_until') }}</dt>
                <dd class="m-0 font-semibold">
                    {{ $quote->valid_until?->isoFormat('D [de] MMMM [de] YYYY') ?? '—' }}
                </dd>
            </div>
        </dl>

        {{-- ---------- linhas ---------- --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[34rem] border-collapse text-[0.94rem]">
                <thead>
                    <tr class="border-b border-line-2 text-left text-[0.78rem] uppercase tracking-wider text-muted">
                        <th class="py-2 font-semibold">{{ __('quotes.concept') }}</th>
                        <th class="py-2 text-right font-semibold">{{ __('quotes.qty') }}</th>
                        <th class="py-2 text-right font-semibold">{{ __('quotes.unit') }}</th>
                        <th class="py-2 text-right font-semibold">{{ __('quotes.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quote->lines as $line)
                        <tr class="border-b border-line">
                            <td class="py-2.5">
                                {{ $line->description }}
                                @if ($line->days > 1)
                                    <span class="text-muted">· {{ trans_choice('quotes.days', $line->days, ['count' => $line->days]) }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 text-right tabular-nums">{{ rtrim(rtrim(number_format((float) $line->quantity, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="py-2.5 text-right tabular-nums">{{ $money($line->unit_price) }}</td>
                            <td class="py-2.5 text-right tabular-nums font-semibold">{{ $money($line->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ---------- totais ---------- --}}
        <dl class="ml-auto mt-6 grid max-w-[22rem] gap-1.5 text-[0.94rem]">
            <div class="flex justify-between">
                <dt class="text-muted">{{ __('quotes.subtotal') }}</dt>
                <dd class="m-0 tabular-nums">{{ $money($quote->subtotal) }}</dd>
            </div>
            @if ((float) $quote->discount_amount > 0)
                <div class="flex justify-between">
                    <dt class="text-muted">{{ __('quotes.discount') }}</dt>
                    <dd class="m-0 tabular-nums">− {{ $money($quote->discount_amount) }}</dd>
                </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-muted">{{ __('quotes.tax', ['rate' => rtrim(rtrim(number_format((float) $quote->tax_rate, 2, ',', '.'), '0'), ',')]) }}</dt>
                <dd class="m-0 tabular-nums">{{ $money($quote->tax_amount) }}</dd>
            </div>
            <div class="mt-1 flex justify-between border-t border-line-2 pt-2 text-step-1 font-semibold">
                <dt>{{ __('quotes.total') }}</dt>
                <dd class="m-0 tabular-nums">{{ $money($quote->total) }}</dd>
            </div>
            <div class="flex justify-between text-oro">
                <dt class="font-semibold">{{ __('quotes.deposit', ['pct' => (int) $quote->deposit_pct]) }}</dt>
                <dd class="m-0 font-semibold tabular-nums">{{ $money($quote->depositAmount()) }}</dd>
            </div>
        </dl>

        {{-- ---------- ações ---------- --}}
        <div class="mt-10 border-t border-line pt-8">
            @if ($open)
                <p class="mb-4 max-w-[54ch] text-ink-2">{{ __('quotes.accept_explainer') }}</p>

                <div class="flex flex-wrap gap-2.5">
                    <form method="POST" action="{{ route('quote.accept', ['token' => $quote->public_token]) }}">
                        @csrf
                        <x-btn variant="rosa" size="lg" type="submit">{{ __('quotes.accept') }}</x-btn>
                    </form>

                    <x-btn variant="line" size="lg" href="https://wa.me/{{ $b['whatsapp'] }}" rel="noopener">
                        <x-ico name="whatsapp" class="w-[1.05rem] h-[1.05rem]" />
                        {{ __('quotes.ask') }}
                    </x-btn>

                    <form method="POST" action="{{ route('quote.reject', ['token' => $quote->public_token]) }}">
                        @csrf
                        <x-btn variant="ghost" size="lg" type="submit">{{ __('quotes.reject') }}</x-btn>
                    </form>
                </div>

            @elseif ($accepted)
                @php
                    $deposit = $event->payments
                        ->where('kind', \App\Enums\PaymentKind::Deposit)
                        ->sortByDesc('created_at')
                        ->first();
                @endphp

                @if ($deposit && $deposit->status === \App\Enums\PaymentStatus::Pending)
                    <p class="mb-4 max-w-[54ch] text-ink-2">
                        {{ __('quotes.pay_explainer', ['amount' => $money($deposit->amount)]) }}
                    </p>
                    <form method="POST" action="{{ route('quote.pay', ['token' => $quote->public_token]) }}">
                        @csrf
                        <x-btn variant="rosa" size="lg" type="submit">
                            {{ __('quotes.pay', ['amount' => $money($deposit->amount)]) }}
                        </x-btn>
                    </form>
                @else
                    <p class="max-w-[54ch] text-ink-2">{{ __('quotes.all_set') }}</p>
                @endif

            @elseif ($rejected)
                <p class="max-w-[54ch] text-ink-2">{{ __('quotes.rejected_body') }}</p>

            @else
                <p class="max-w-[54ch] text-ink-2">{{ __('quotes.expired_body') }}</p>
                <x-btn variant="line" size="lg" href="https://wa.me/{{ $b['whatsapp'] }}" rel="noopener" class="mt-4">
                    <x-ico name="whatsapp" class="w-[1.05rem] h-[1.05rem]" />
                    {{ __('quotes.ask') }}
                </x-btn>
            @endif
        </div>

        <p class="mt-8 text-[0.82rem] text-muted">{{ __('quotes.version', ['n' => $quote->version]) }}</p>
    </section>
</x-layouts.public>
