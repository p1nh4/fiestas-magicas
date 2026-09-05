<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leads\Tables;

use App\Enums\EventType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Support\Leads\LeadConversion;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * A lista de solicitudes é o ecrã onde a Sol entra de manhã.
 *
 * Por isso: as mais recentes primeiro, o estado a cores, e dois botões que
 * resolvem 90 % do trabalho — responder por WhatsApp e converter em evento.
 * Tudo o resto (utm, ip, user agent) fica escondido; está guardado, mas não
 * enche o ecrã.
 */
class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recibida')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('event_type')
                    ->label('Celebración')
                    ->badge(),
                TextColumn::make('event_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->placeholder('sin fecha')
                    ->sortable(),
                TextColumn::make('venue')
                    ->label('Lugar')
                    ->searchable()
                    ->limit(24)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('guests_count')
                    ->label('Invitados')
                    ->numeric()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Teléfono copiado')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copiado')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('locale')
                    ->label('Idioma')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('utm_source')
                    ->label('Origen')
                    ->placeholder('directo')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(LeadStatus::class)
                    ->multiple(),
                SelectFilter::make('event_type')
                    ->label('Celebración')
                    ->options(EventType::class)
                    ->multiple(),
            ])
            ->recordActions([
                // Responder é literalmente o passo seguinte em quase todos
                // os casos. Abre o WhatsApp já com o número da pessoa.
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Lead $record): string => 'https://wa.me/'.preg_replace('/\D+/', '', (string) $record->phone))
                    ->openUrlInNewTab()
                    ->visible(fn (Lead $record): bool => filled($record->phone)),

                Action::make('convertir')
                    ->label('Convertir en evento')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->modalHeading('Crear cliente y evento')
                    ->modalDescription('Se crea la ficha del cliente (o se reutiliza si ya existe) y un evento en borrador. La solicitud se queda como está, enlazada.')
                    ->modalSubmitActionLabel('Crear')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Empieza')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(fn (Lead $record) => $record->event_date?->setTime(12, 0))
                            ->helperText('Si aún no lo sabes, déjalo: se corrige luego en el evento.'),
                        DateTimePicker::make('ends_at')
                            ->label('Termina')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->after('starts_at'),
                    ])
                    ->action(function (Lead $record, array $data): void {
                        $event = app(LeadConversion::class)->convert(
                            $record,
                            filled($data['starts_at'] ?? null) ? Carbon::parse($data['starts_at']) : null,
                            filled($data['ends_at'] ?? null) ? Carbon::parse($data['ends_at']) : null,
                        );

                        Notification::make()
                            ->success()
                            ->title('Evento '.$event->reference.' creado')
                            ->body('Ya puedes prepararle el presupuesto.')
                            ->send();
                    })
                    ->visible(fn (Lead $record): bool => $record->converted_at === null),

                EditAction::make()->label('Ficha'),
            ])
            // Sem accoes em bloco de proposito: um pedido apagado e um
            // cliente que ninguem volta a encontrar. Para lixo ha o estado
            // "spam", que se ve e se desfaz.
            ->emptyStateHeading('Todavía no hay solicitudes')
            ->emptyStateDescription('Aparecerán aquí en cuanto alguien rellene el formulario de la web.');
    }
}
