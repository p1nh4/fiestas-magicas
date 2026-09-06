<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventDesigns;

use App\Filament\Resources\EventDesigns\Pages\CreateEventDesign;
use App\Filament\Resources\EventDesigns\Pages\EditEventDesign;
use App\Filament\Resources\EventDesigns\Pages\ListEventDesigns;
use App\Filament\Resources\EventDesigns\Schemas\EventDesignForm;
use App\Filament\Resources\EventDesigns\Tables\EventDesignsTable;
use App\Models\EventDesign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Proyectos: a ideia de cada festa antes de virar orçamento.
 *
 * Tema, cores, fotos de referência, passos da montagem e o material que se
 * tenciona levar. É o caderno da Sol, com uma diferença: o material que
 * escreve aqui passa para o orçamento com um botão.
 */
class EventDesignResource extends Resource
{
    protected static ?string $model = EventDesign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Trabajo';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'proyecto';

    protected static ?string $pluralModelLabel = 'proyectos';

    protected static ?string $navigationLabel = 'Diseño de la fiesta';

    /**
     * Traz as relacoes que a tabela mostra, numa consulta so.
     *
     * A coluna do cliente segue event -> client em cada linha.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['event.client']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['theme'];
    }

    public static function form(Schema $schema): Schema
    {
        return EventDesignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventDesignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventDesigns::route('/'),
            'create' => CreateEventDesign::route('/create'),
            'edit' => EditEventDesign::route('/{record}/edit'),
        ];
    }
}
