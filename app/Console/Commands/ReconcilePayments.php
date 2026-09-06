<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\SettlePayment;
use Illuminate\Console\Command;
use Throwable;

/**
 * Vai perguntar ao fornecedor pelos pagamentos que ficaram por confirmar.
 *
 * O buraco que isto tapa: o sistema só dava um sinal por pago quando a
 * pessoa VOLTAVA da passarela ao site. Quem paga e fecha o separador nunca
 * volta — e é o que acontece a metade das pessoas num telemóvel. O
 * dinheiro entrava, e cá dentro o evento continuava a dizer "pendente de
 * cobro" e a cliente nunca recebia recibo. A Sol acabava a perguntar-lhe
 * por um dinheiro que ela já tinha pago.
 *
 * A alternativa clássica é um webhook. Não se fez, e a razão é honesta: um
 * webhook obriga a ter uma URL pública sempre de pé, a verificar
 * assinaturas e a aguentar reenvios — e enquanto o site vive num portátil
 * atrás de um túnel, essa URL não existe metade do tempo. Perguntar de
 * hora a hora é mais lento e é muito mais difícil de pôr a funcionar mal.
 * Quando houver servidor a sério, um webhook entra por cima disto sem
 * mexer em mais nada: os dois acabam no mesmo `SettlePayment`.
 *
 * Só olha para os últimos dias. Um pagamento pendente há três semanas não
 * é um pagamento que a Stripe vá confirmar — é lixo, e vale mais alguém
 * olhar para ele do que ficar a perguntar por ele para sempre.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--dias=7 : Cuántos días atrás mirar}';

    protected $description = 'Pregunta a la pasarela por los pagos que quedaron sin confirmar';

    public function handle(PaymentGateway $gateway, SettlePayment $settle): int
    {
        $dias = max(1, (int) $this->option('dias'));

        /*
         * So os pagamentos desta passarela.
         *
         * Sem o filtro por `provider`, no dia em que a chave da Stripe
         * entrar todas as referencias `fake_...` que ficaram para tras
         * passavam a ser perguntadas a Stripe — excecoes no log de hora a
         * hora. E ao contrario e pior: se a chave desaparecer, a passarela
         * falsa responde sempre "nao pago" a referencias reais, e pagamentos
         * verdadeiros nunca se liquidam, sem erro nenhum.
         */
        $pendentes = Payment::query()
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('provider_reference')
            ->where('provider', $gateway->name())
            ->where('created_at', '>=', now()->subDays($dias))
            ->with('event')
            ->get();

        $liquidados = 0;
        $falhas = 0;

        foreach ($pendentes as $payment) {
            try {
                if ($settle->settleIfPaid($payment, $gateway)) {
                    $liquidados++;
                    $this->line("  {$payment->uuid} → pagado");
                }
            } catch (Throwable $e) {
                // Uma referência que a passarela já não conhece, ou a
                // passarela em baixo, não pode parar os outros. Fica no log
                // e tenta-se outra vez daqui a uma hora.
                $falhas++;
                logger()->warning('payments:reconcile falhou num pagamento', [
                    'payment' => $payment->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        if ($liquidados > 0) {
            $this->info("{$liquidados} pagos confirmados con la pasarela.");
        }

        if ($falhas > 0) {
            $this->warn("{$falhas} no se pudieron comprobar. Están en el log.");
        }

        return self::SUCCESS;
    }
}
