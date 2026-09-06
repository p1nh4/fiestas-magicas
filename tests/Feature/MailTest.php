<?php

declare(strict_types=1);

use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Mail\DepositReceivedMail;
use App\Mail\LeadAlertMail;
use App\Mail\LeadReceivedMail;
use App\Mail\QuoteReadyMail;
use App\Models\Client;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Payment;
use App\Support\Mail\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config(['business.alert_email' => 'sol@example.com']);
    $this->notifier = app(Notifier::class);
});

function aLead(array $overrides = []): Lead
{
    return Lead::create($overrides + [
        'name' => 'Marta',
        'email' => 'marta@example.com',
        'phone' => '+34600111222',
        'locale' => 'pt',
        'event_type' => 'comunion',
        'event_date' => now()->addMonths(2)->toDateString(),
        'status' => 'new',
    ]);
}

// ------------------------------------------------------------------- pedido

it('confirma a quem escreveu e avisa a sol', function () {
    $this->notifier->leadReceived(aLead());

    Mail::assertQueued(LeadReceivedMail::class, fn ($m) => $m->hasTo('marta@example.com'));
    Mail::assertQueued(LeadAlertMail::class, fn ($m) => $m->hasTo('sol@example.com'));
});

it('responde no idioma em que a pessoa escreveu', function () {
    $this->notifier->leadReceived(aLead(['locale' => 'pt']));

    Mail::assertQueued(LeadReceivedMail::class, fn ($m) => $m->locale === 'pt');
});

it('o aviso interno vai sempre em espanhol', function () {
    $this->notifier->leadReceived(aLead(['locale' => 'pt']));

    Mail::assertQueued(LeadAlertMail::class, fn ($m) => $m->locale === 'es');
});

/*
| Muita gente deixa o email em branco e só põe o telefone. Não é erro: é
| resposta por WhatsApp. O que não pode acontecer é rebentar.
*/
it('aguenta um pedido so com telefone', function () {
    $this->notifier->leadReceived(aLead(['email' => null]));

    Mail::assertNotQueued(LeadReceivedMail::class);
    Mail::assertQueued(LeadAlertMail::class);
});

/*
| Sem endereço de aviso configurado, não se envia nada — e não se adivinha
| um. Um email para o sítio errado é pior do que email nenhum: dá a sensação
| de que a Sol foi avisada quando não foi.
*/
it('nao inventa destinatario quando nao ha endereco de aviso', function () {
    config(['business.alert_email' => null, 'business.email' => null]);

    $this->notifier->leadReceived(aLead());

    Mail::assertNotQueued(LeadAlertMail::class);
    Mail::assertQueued(LeadReceivedMail::class);
});

/*
| A regra que mais importa neste ficheiro: um SMTP em baixo não pode
| transformar um pedido de orçamento num erro 500. O pedido fica guardado, o
| email fica no log, e alguém trata disso depois.
*/
it('nao rebenta o formulario quando o email falha', function () {
    Log::spy();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP em baixo'));

    $lead = aLead();

    expect(fn () => $this->notifier->leadReceived($lead))->not->toThrow(Throwable::class);
    Log::shouldHaveReceived('error');
});

it('o formulario publico responde mesmo assim', function () {
    $response = $this->post('/es/presupuesto', [
        'name' => 'Marta',
        'email' => 'marta@example.com',
        'phone' => '+34600111222',
        'event_type' => 'comunion',
        'event_date' => now()->addMonths(2)->toDateString(),
        'message' => 'Queremos mesa dulce.',
        'privacy' => '1',
        'website' => '',
    ]);

    $response->assertRedirect();
    expect(Lead::count())->toBe(1);
    Mail::assertQueued(LeadReceivedMail::class);
});

// --------------------------------------------------------------- orçamento

function anEventWithClient(?string $email = 'cliente@example.com', string $locale = 'gl'): Event
{
    $client = Client::create([
        'name' => 'Uxía',
        'email' => $email,
        'phone' => '+34600999888',
        'locale' => $locale,
    ]);

    return Event::create([
        'client_id' => $client->id,
        'reference' => 'FM-TEST-9',
        'title' => 'Comunión de Uxía',
        'event_type' => 'comunion',
        'locale' => $locale,
        'starts_at' => now()->addDays(40),
        'ends_at' => now()->addDays(40)->addHours(6),
    ]);
}

it('manda o orcamento com o link, no idioma do evento', function () {
    $event = anEventWithClient();
    $quote = app(App\Support\Quotes\QuoteBuilder::class)->draftFor($event);

    $this->notifier->quoteSent($quote, 'https://exemplo.test/gl/presupuesto/abc');

    Mail::assertQueued(QuoteReadyMail::class, function ($m) {
        return $m->hasTo('cliente@example.com')
            && $m->locale === 'gl'
            && $m->url === 'https://exemplo.test/gl/presupuesto/abc';
    });
});

it('nao manda orcamento a um cliente sem email', function () {
    $event = anEventWithClient(email: null);
    $quote = app(App\Support\Quotes\QuoteBuilder::class)->draftFor($event);

    $this->notifier->quoteSent($quote, 'https://exemplo.test/es/presupuesto/abc');

    Mail::assertNotQueued(QuoteReadyMail::class);
});

// ------------------------------------------------------------------- sinal

it('manda o recibo do sinal', function () {
    $event = anEventWithClient(locale: 'pt');

    $payment = Payment::create([
        'event_id' => $event->id,
        'client_id' => $event->client_id,
        'kind' => PaymentKind::Deposit,
        'method' => 'card',
        'status' => PaymentStatus::Paid,
        'amount' => 150.00,
        'paid_at' => now(),
    ]);

    $this->notifier->depositReceived($payment);

    Mail::assertQueued(DepositReceivedMail::class, function ($m) {
        return $m->hasTo('cliente@example.com') && $m->locale === 'pt';
    });
});

// ------------------------------------------------------------------- corpo

/*
| Os emails são o único sítio do sistema em que o HTML não passa pelo
| navegador nem pelos testes das views. Renderizar cada um pelo menos uma vez
| é o que apanha um `$variavel` que não existe — que num email só se descobre
| quando alguém não o recebe.
*/
it('todos os emails renderizam sem rebentar', function () {
    $lead = aLead();
    $event = anEventWithClient();
    $quote = app(App\Support\Quotes\QuoteBuilder::class)->draftFor($event);
    $payment = Payment::create([
        'event_id' => $event->id,
        'client_id' => $event->client_id,
        'kind' => PaymentKind::Deposit,
        'method' => 'card',
        'status' => PaymentStatus::Paid,
        'amount' => 150.00,
    ]);

    $mails = [
        new LeadReceivedMail($lead),
        new LeadAlertMail($lead),
        new QuoteReadyMail($quote, 'https://exemplo.test/es/presupuesto/abc'),
        new DepositReceivedMail($payment),
    ];

    foreach ($mails as $mail) {
        expect($mail->render())->toBeString()->not->toBeEmpty();
    }
});

it('o email do orcamento leva o link e avisa que e pessoal', function () {
    $event = anEventWithClient(locale: 'es');
    $quote = app(App\Support\Quotes\QuoteBuilder::class)->draftFor($event);

    $html = (new QuoteReadyMail($quote, 'https://exemplo.test/es/presupuesto/abc'))->render();

    expect($html)->toContain('https://exemplo.test/es/presupuesto/abc')
        ->and($html)->toContain(__('mails.quote.link_warning', [], 'es'));
});

/*
| Estes são emails transacionais — resposta a um pedido que a pessoa fez —
| e não publicidade. Um "cancelar subscrição" num email destes só ensina
| quem o recebe a marcá-lo como spam. A newsletter, essa, terá o seu.
*/
it('nao poe cancelar subscricao em emails transacionais', function () {
    $html = (new LeadReceivedMail(aLead()))->render();

    expect(strtolower($html))
        ->not->toContain('unsubscribe')
        ->not->toContain('darse de baja')
        ->not->toContain('cancelar subscri');
});
