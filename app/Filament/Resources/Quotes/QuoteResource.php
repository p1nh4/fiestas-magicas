<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotes;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Filament\Resources\Quotes\Schemas\QuoteForm;
use App\Filament\Resources\Quotes\Tables\QuotesTable;
use App\Models\Quote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Presupuestos.
 *
 * Não há botão de "criar" aqui de propósito: um orçamento é sempre de um
 * evento, e nasce a partir dele. Assim nunca existe um orçamento órfão, que
 * é a maneira mais fácil de perder dinheiro numa festa que ninguém marcou.
 */
class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Trabajo';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'presupuesto';

    protected static ?string $pluralModelLabel = 'presupuestos';

    protected static ?string $navigationLabel = 'Presupuestos';

    /** Quantos estão em rascunho, ou seja, por enviar. */
    /**
     * Traz as relacoes que a tabela mostra, numa consulta so.
     *
     * A tabela mostra o nome da cliente e monta o link publico com o idioma
     * do evento: sem isto sao tres consultas por linha.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['event.client']);
    }

    public static function getNavigationBadge(): ?string
    {
        $drafts = static::getModel()::query()
            ->where('status', QuoteStatus::Draft->value)
            ->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return QuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }
}
