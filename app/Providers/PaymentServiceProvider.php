<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Payments\FakeGateway;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\StripeGateway;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

final class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Sem chave da Stripe configurada, usa-se a passarela falsa.
     *
     * É deliberado: enquanto a empresa não tiver conta, o site tem de
     * arrancar e o fluxo tem de poder ser percorrido. O que não pode
     * acontecer é a falsa entrar em produção sem ninguém dar por isso —
     * daí o aviso no boot().
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            $secret = config('services.stripe.secret');

            if (blank($secret)) {
                return new FakeGateway();
            }

            return new StripeGateway(new StripeClient($secret));
        });
    }

    public function boot(): void
    {
        if ($this->app->isProduction() && blank(config('services.stripe.secret'))) {
            logger()->warning(
                'Não há chave da Stripe configurada: os pagamentos estão a usar a passarela falsa.'
            );
        }
    }
}
