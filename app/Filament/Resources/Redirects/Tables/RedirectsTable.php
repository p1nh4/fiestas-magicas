<?php

declare(strict_types=1);

namespace App\Filament\Resources\Redirects\Tables;

use App\Models\Redirect;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('from_path')
                    ->label('Dirección antigua')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('to_path')
                    ->label('Lleva a')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status_code')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (Redirect $record): string => $record->status_code === 301 ? 'success' : 'warning'),
                /*
                | Quantas pessoas ainda chegam pelo endereco velho.
                |
                | Um numero alto meses depois significa que o link antigo
                | continua a circular algures — e que apagar a
                | redirecao custaria visitas a serio.
                */
                TextColumn::make('hits')
                    ->label('Visitas')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('usadas')
                    ->label('Solo las que se usan')
                    ->query(fn (Builder $query): Builder => $query->where('hits', '>', 0)),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()
                    ->label('Borrar')
                    ->modalDescription('Quien llegue por la dirección antigua verá un error 404. Mira antes la columna de visitas.'),
            ])
            ->emptyStateHeading('No hay redirecciones')
            ->emptyStateDescription('Se crean solas cuando cambias la dirección de una página.');
    }
}
