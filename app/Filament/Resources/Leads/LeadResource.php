<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leads;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Solicitudes: os pedidos que chegam do formulário do site.
 *
 * Não há página de criação. Um lead nasce sempre no site — se a Sol
 * apontar um pedido que lhe chegou por telefone, isso é um cliente e um
 * evento, não um lead. Manter esta distinção é o que faz com que os
 * números de marketing ("quantos pedidos vieram do Instagram") signifiquem
 * alguma coisa.
 */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Trabajo';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'solicitudes';

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $recordTitleAttribute = 'name';

    /** Quantas estão por responder. É o número que interessa ver ao entrar. */
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->where('status', LeadStatus::New->value)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
