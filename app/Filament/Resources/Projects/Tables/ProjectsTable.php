<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\EventType;
use App\Models\Project;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('happened_on', 'desc')
            ->columns([
                TextColumn::make('happened_on')
                    ->label('Cuándo')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Trabajo')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Project $record): ?string => $record->venue),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('Localidad')
                    ->searchable()
                    ->placeholder('—'),
                /*
                | A coluna que evita uma conversa desagradável.
                |
                | Diz, em cada linha, se há permissão do cliente. Sem ela, a
                | Sol carregava em "publicar" e levava com um erro do
                | Postgres sem perceber porquê — ou pior, ficava a achar que
                | tinha publicado.
                */
                TextColumn::make('permiso')
                    ->label('Permiso')
                    ->badge()
                    ->state(fn (Project $record): string => $record->consent_at !== null ? 'sí' : 'falta')
                    ->color(fn (string $state): string => $state === 'sí' ? 'success' : 'danger'),
                IconColumn::make('is_featured')
                    ->label('Portada')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_published')
                    ->label('En la web')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Tipo')
                    ->options(EventType::class)
                    ->multiple(),
                TernaryFilter::make('is_published')->label('Visible en la web'),
                Filter::make('sin_permiso')
                    ->label('Sin permiso del cliente')
                    ->query(fn (Builder $query): Builder => $query->whereNull('consent_at')),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->emptyStateHeading('Todavía no hay trabajos publicados')
            ->emptyStateDescription('Es lo que más ayuda a que os encuentren. Una fiesta real en un sitio real no la copia nadie.');
    }
}
