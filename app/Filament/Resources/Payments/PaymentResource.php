<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Pagamentos: o que entrou e o que falta.
 *
 * Sem formulário de criação nem de edição. Um pagamento por cartão nasce da
 * passarela; um pagamento em dinheiro ou por transferência marca-se com um
 * botão na linha, que escreve a data e o método. Um formulário livre sobre
 * dinheiro é a forma mais rápida de ter contas que não batem certo com
 * nada — e quando a faturação legal entrar (Veri*factu), estas linhas
 * passam a ser encadeadas por hash e a imutabilidade deixa de ser uma
 * escolha.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Trabajo';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'pago';

    protected static ?string $pluralModelLabel = 'pagos';

    protected static ?string $navigationLabel = 'Pagos';

    /** Quantos estão à espera de confirmação. */
    /**
     * Traz as relacoes que a tabela mostra, numa consulta so.
     *
     * A coluna mostra a referencia do evento.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['event']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->where('status', PaymentStatus::Pending->value)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}
