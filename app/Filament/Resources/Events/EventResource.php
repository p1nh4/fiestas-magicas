<?php

declare(strict_types=1);

namespace App\Filament\Resources\Events;

use App\Enums\EventStatus;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\Schemas\EventForm;
use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Eventos: a agenda. É a tabela à volta da qual gira tudo o resto —
 * orçamentos, reservas de material e pagamentos penduram-se aqui.
 */
class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Trabajo';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'evento';

    protected static ?string $pluralModelLabel = 'eventos';

    protected static ?string $navigationLabel = 'Eventos';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'title', 'venue_name'];
    }

    /** Quantas festas confirmadas estão por vir. */
    public static function getNavigationBadge(): ?string
    {
        $upcoming = static::getModel()::query()
            ->whereIn('status', [EventStatus::Confirmed->value, EventStatus::InProgress->value])
            ->where('starts_at', '>=', now())
            ->count();

        return $upcoming > 0 ? (string) $upcoming : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client'])->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
