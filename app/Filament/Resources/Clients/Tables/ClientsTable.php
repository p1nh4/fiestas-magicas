<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients\Tables;

use App\Enums\ClientKind;
use App\Enums\Locale;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Client $record): ?string => $record->legal_name),
                TextColumn::make('kind')
                    ->label('Tipo')
                    ->badge()
                    ->toggleable(),
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
                    ->placeholder('—'),
                TextColumn::make('city')
                    ->label('Localidad')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('locale')
                    ->label('Idioma')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('events_count')
                    ->label('Fiestas')
                    ->counts('events')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('source')
                    ->label('Origen')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Tipo')
                    ->options(ClientKind::class),
                SelectFilter::make('locale')
                    ->label('Idioma')
                    ->options(Locale::class),
                // Para saber a quem se PODE escrever. Mandar novidades a quem
                // nao consentiu e uma coima, nao um descuido.
                Filter::make('marketing')
                    ->label('Acepta novedades')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('marketing_opt_in_at')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Client $record): string => 'https://wa.me/'.preg_replace('/\D+/', '', (string) ($record->whatsapp ?: $record->phone)))
                    ->openUrlInNewTab()
                    ->visible(fn (Client $record): bool => filled($record->whatsapp ?: $record->phone)),
                EditAction::make()->label('Ficha'),
            ])
            ->emptyStateHeading('Todavía no hay clientes')
            ->emptyStateDescription('Se crean solos al convertir una solicitud, o a mano con el botón de arriba.');
    }
}
