<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                TextColumn::make('title')
                    ->label('Página')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Page $record): string => $record->key),
                /*
                | A coluna que diz o que falta.
                |
                | Os textos legais nascem com PENDIENTE onde falta a
                | identidade da empresa. Publicar uma pagina com PENDIENTE
                | la dentro seria pior do que nao a ter: um aviso legal que
                | diz "PENDIENTE: NIF" e uma confissao.
                */
                TextColumn::make('revision')
                    ->label('Revisión')
                    ->badge()
                    ->state(fn (Page $record): string => static::hasPending($record) ? 'falta rellenar' : 'lista')
                    ->color(fn (string $state): string => $state === 'lista' ? 'success' : 'danger'),
                IconColumn::make('is_published')
                    ->label('En la web')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Visible en la web'),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Page $record): ?string => Page::urlFor($record->key, 'es'))
                    ->openUrlInNewTab()
                    ->visible(fn (Page $record): bool => $record->is_published),
                EditAction::make()->label('Editar'),
            ])
            ->emptyStateHeading('No hay páginas')
            ->emptyStateDescription('El aviso legal, la privacidad y las cookies se siembran con el LegalPagesSeeder.');
    }

    /** Sobra algum PENDIENTE em qualquer idioma? */
    private static function hasPending(Page $record): bool
    {
        foreach (['es', 'gl', 'pt'] as $locale) {
            if (str_contains((string) $record->getTranslation('body', $locale, false), 'PENDIENTE')) {
                return true;
            }
        }

        return false;
    }
}
