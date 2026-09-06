<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotes\Tables;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Support\Mail\Notifier;
use App\Support\Quotes\QuoteBuilder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event.reference')
                    ->label('Ref.')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('event.title')
                    ->label('Fiesta')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Quote $record): ?string => $record->event?->client?->name),
                TextColumn::make('version')
                    ->label('v')
                    ->alignEnd(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('deposit')
                    ->label('Señal')
                    ->alignEnd()
                    ->state(fn (Quote $record): string => $record->depositAmount())
                    ->money('EUR')
                    ->toggleable(),
                TextColumn::make('valid_until')
                    ->label('Válido hasta')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    // Um orçamento a caducar amanhã é uma chamada a fazer hoje.
                    ->color(fn (Quote $record): string => $record->isExpired() ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->since()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                /*
                | O link do cliente, como coluna que se copia num clique.
                |
                | Duas coisas separadas de proposito: o que se VE e o que se
                | COPIA. O `copyable()` do Filament copia o texto mostrado —
                | por isso, com o URL truncado no ecra, era o URL truncado que
                | ia para a area de transferencia. O `copyableState()` e o
                | metodo que existe precisamente para isto.
                |
                | Meio URL cortado tambem nao informava ninguem; mostrar a
                | acao diz mais do que mostrar o endereco.
                |
                | Fica a traco enquanto o orcamento for rascunho: ate estar
                | enviado nao ha nada para mandar a ninguem.
                */
                TextColumn::make('enlace')
                    ->label('Enlace')
                    ->state(fn (Quote $record): ?string => $record->isEditable() ? null : 'Copiar enlace')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-link')
                    ->copyable()
                    ->copyableState(fn (Quote $record): string => static::publicUrl($record))
                    ->copyMessage('Enlace copiado')
                    ->tooltip(fn (Quote $record): ?string => $record->isEditable() ? null : static::publicUrl($record)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(QuoteStatus::class)
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make()->label('Abrir'),

                Action::make('enviar')
                    ->label('Enviar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Marcar como enviado')
                    ->modalDescription('A partir de aquí ya no se puede editar: si hay que cambiar algo, se hace una versión nueva. Copia el enlace y mándaselo al cliente.')
                    ->modalSubmitActionLabel('Marcar como enviado')
                    ->action(function (Quote $record): void {
                        if ($record->lines()->count() === 0) {
                            Notification::make()
                                ->warning()
                                ->title('El presupuesto está vacío')
                                ->body('Añádele al menos una línea antes de enviarlo.')
                                ->send();

                            return;
                        }

                        app(QuoteBuilder::class)->markSent($record);

                        // Agora "enviar" envia mesmo. Se o cliente não tiver
                        // email, o Notifier nao faz nada — e o link continua
                        // na coluna, para ir por WhatsApp.
                        app(Notifier::class)->quoteSent($record->fresh(), static::publicUrl($record));

                        $hasEmail = filled($record->event?->client?->email);

                        Notification::make()
                            ->success()
                            ->title('Marcado como enviado')
                            ->body($hasEmail
                                ? 'Le hemos mandado el presupuesto por correo. El enlace también está en la columna "Enlace".'
                                : 'Este cliente no tiene email: copia el enlace de la columna "Enlace" y mándaselo por WhatsApp.')
                            ->send();
                    })
                    ->visible(fn (Quote $record): bool => $record->isEditable()),

                Action::make('ver')
                    ->label('Ver como el cliente')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Quote $record): string => static::publicUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (Quote $record): bool => ! $record->isEditable()),

                Action::make('revisar')
                    ->label('Nueva versión')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Crear la versión siguiente')
                    ->modalDescription('Se copian todas las líneas a un borrador nuevo. Esta versión se queda intacta, como prueba de lo que vio el cliente.')
                    ->modalSubmitActionLabel('Crear')
                    ->action(function (Quote $record): void {
                        $next = app(QuoteBuilder::class)->reviseFrom($record);

                        Notification::make()
                            ->success()
                            ->title('Versión '.$next->version.' creada')
                            ->send();
                    })
                    ->visible(fn (Quote $record): bool => ! $record->isEditable()),
            ])
            ->emptyStateHeading('No hay presupuestos')
            ->emptyStateDescription('Se crean desde el evento, con el botón "Presupuesto".');
    }

    /** O link mágico, no idioma do cliente. */
    protected static function publicUrl(Quote $record): string
    {
        return route('quote.show', [
            'locale' => $record->event?->locale?->value ?? config('app.locale'),
            'token' => $record->public_token,
        ]);
    }
}
