<?php

declare(strict_types=1);

namespace App\Filament\Resources\Items;

use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Filament\Resources\Items\Schemas\ItemForm;
use App\Filament\Resources\Items\Tables\ItemsTable;
use App\Models\Item;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Material: as pecas fisicas que se alugam e se levam para a festa.
 *
 * O stock daqui nao e decorativo — e o numero que a base de dados usa para
 * recusar uma reserva a mais. Baixar o stock de 3 para 2 tem consequencias
 * reais.
 */
class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'material';

    protected static ?string $pluralModelLabel = 'material';

    protected static ?string $navigationLabel = 'Material';

    public static function getGloballySearchableAttributes(): array
    {
        return ['sku'];
    }

    public static function form(Schema $schema): Schema
    {
        return ItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'edit' => EditItem::route('/{record}/edit'),
        ];
    }
}
