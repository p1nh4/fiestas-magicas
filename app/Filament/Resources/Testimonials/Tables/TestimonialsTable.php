<?php

declare(strict_types=1);

namespace App\Filament\Resources\Testimonials\Tables;

use App\Enums\Locale;
use App\Models\Testimonial;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('author_name')
                    ->label('Quién')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('body')
                    ->label('Lo que dijo')
                    ->searchable()
                    ->limit(70)
                    ->tooltip(fn (Testimonial $record): string => $record->body),
                TextColumn::make('rating')
                    ->label('★')
                    ->placeholder('—')
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('locale')
                    ->label('Idioma')
                    ->badge(),
                TextColumn::make('source')
                    ->label('Origen')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('consent_at')
                    ->label('Permiso')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label('En la web')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Visible en la web'),
                SelectFilter::make('locale')->label('Idioma')->options(Locale::class),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->emptyStateHeading('Todavía no hay opiniones')
            ->emptyStateDescription('Pídeselas a clientes que quedaron contentos. Inventarlas no: el sitio tiene pruebas que lo impiden.');
    }
}
