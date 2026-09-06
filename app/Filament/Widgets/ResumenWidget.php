<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EventStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Payment;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * A primeira coisa que a Sol vê ao entrar.
 *
 * Quatro números, e todos respondem à mesma pergunta: **o que tenho de
 * fazer hoje?** Não são estatísticas para admirar — cada um tem uma ação
 * por trás, e por isso cada um leva a uma lista já filtrada.
 *
 * O que ficou de fora, de propósito: faturação do mês, número de clientes,
 * gráficos de evolução. São números bonitos que não mudam o que se faz a
 * seguir, e um painel cheio deles ensina a ignorá-lo.
 */
class ResumenWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            $this->pedidosPorResponder(),
            $this->presupuestosPorEnviar(),
            $this->fiestasProximas(),
            $this->dineroPendiente(),
        ];
    }

    /** Pedidos que chegaram do site e ninguém tocou. É o mais urgente. */
    private function pedidosPorResponder(): Stat
    {
        $n = Lead::query()->where('status', LeadStatus::New->value)->count();

        return Stat::make('Sin responder', (string) $n)
            ->description($n === 0 ? 'Todo contestado' : 'Peticiones nuevas de la web')
            ->descriptionIcon('heroicon-o-inbox')
            ->color($n > 0 ? 'warning' : 'success')
            ->url(LeadResource::getUrl());
    }

    /** Orçamentos em rascunho: trabalho já feito que ainda não saiu. */
    private function presupuestosPorEnviar(): Stat
    {
        $n = Quote::query()->where('status', QuoteStatus::Draft->value)->count();

        return Stat::make('Por enviar', (string) $n)
            ->description($n === 0 ? 'Ninguno pendiente' : 'Presupuestos en borrador')
            ->descriptionIcon('heroicon-o-document-text')
            ->color($n > 0 ? 'primary' : 'gray')
            ->url(QuoteResource::getUrl());
    }

    /** Festas confirmadas nos próximos 30 dias. */
    private function fiestasProximas(): Stat
    {
        $n = Event::query()
            ->whereIn('status', [EventStatus::Confirmed->value, EventStatus::InProgress->value])
            ->whereBetween('starts_at', [now(), now()->addDays(30)])
            ->count();

        return Stat::make('Próximas fiestas', (string) $n)
            ->description('Confirmadas en 30 días')
            ->descriptionIcon('heroicon-o-calendar-days')
            ->color('success')
            ->url(EventResource::getUrl());
    }

    /**
     * Dinheiro por receber.
     *
     * Soma feita pela base de dados e não em PHP: com trinta eventos não
     * faria diferença, mas trazer trinta linhas para somar quatro números
     * é o hábito que, com trezentas, torna o painel lento.
     */
    private function dineroPendiente(): Stat
    {
        $pending = (float) Payment::query()
            ->where('status', PaymentStatus::Pending->value)
            ->sum('amount');

        return Stat::make('Pendiente de cobro', number_format($pending, 2, ',', '.').' €')
            ->description($pending > 0 ? 'Señales y pagos sin confirmar' : 'Nada pendiente')
            ->descriptionIcon('heroicon-o-banknotes')
            ->color($pending > 0 ? 'warning' : 'success')
            ->url(PaymentResource::getUrl());
    }
}
