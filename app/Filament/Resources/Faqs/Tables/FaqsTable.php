<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Tables;

use App\Models\Faq;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('question')
                    ->label('Pregunta')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Faq $record): string => str((string) $record->answer)->limit(90)->value()),
                IconColumn::make('is_published')
                    ->label('En la web')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Visible en la web'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
            ])
            ->emptyStateHeading('No hay preguntas')
            ->emptyStateDescription('Las que ya estaban vienen del catálogo inicial. Dos están apagadas esperando tu respuesta.');
    }
}
