<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('event.title')
                    ->label('Fiesta')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Payment $record): ?string => $record->event?->reference),
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('kind')
                    ->label('Concepto')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Importe')
                    ->money('EUR')
                    ->alignEnd()
                    ->sortable()
                    // Um reembolso é negativo na base de dados (há um CHECK
                    // que o obriga). A cor evita ler um número negativo como
                    // se fosse dinheiro que entrou.
                    ->color(fn (Payment $record): string => (float) $record->amount < 0 ? 'danger' : 'success'),
                TextColumn::make('method')
                    ->label('Cómo')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label('Cobrado')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(PaymentStatus::class)
                    ->multiple(),
                SelectFilter::make('kind')
                    ->label('Concepto')
                    ->options(PaymentKind::class)
                    ->multiple(),
                SelectFilter::make('method')
                    ->label('Cómo')
                    ->options(PaymentMethod::class),
            ])
            ->recordActions([
                /*
                | Marcar um pagamento como recebido.
                |
                | Dentro de uma transacao, e com o `increment` a ser feito
                | pela base de dados e nao em PHP: `$evento->paid_amount + x`
                | lido e reescrito por duas pessoas ao mesmo tempo perde um
                | dos pagamentos, e e o tipo de erro que so se descobre
                | quando as contas nao batem no fim do mes.
                */
                Action::make('cobrado')
                    ->label('Marcar cobrado')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalHeading('Marcar como cobrado')
                    ->modalDescription('Para pagos en efectivo, Bizum o transferencia. Los de tarjeta se marcan solos cuando la pasarela confirma.')
                    ->modalSubmitActionLabel('Marcar')
                    ->schema([
                        Select::make('method')
                            ->label('Cómo lo has cobrado')
                            ->options(PaymentMethod::class)
                            ->default(PaymentMethod::Transfer->value)
                            ->required()
                            ->native(false),
                        DateTimePicker::make('paid_at')
                            ->label('Cuándo')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => PaymentStatus::Paid,
                                'method' => $data['method'],
                                'paid_at' => $data['paid_at'],
                            ]);

                            $record->event?->increment('paid_amount', (float) $record->amount);
                        });

                        Notification::make()->success()->title('Pago registrado')->send();
                    })
                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Pending),
            ])
            ->emptyStateHeading('No hay pagos')
            ->emptyStateDescription('La señal se crea sola cuando un cliente acepta un presupuesto.');
    }
}
