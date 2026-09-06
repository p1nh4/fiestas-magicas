<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Trabalhos publicados — o portefólio.
 *
 * É a principal arma de SEO do site: uma festa real num sítio real é o
 * conteúdo que nenhuma concorrência copia. E é também o único sítio do
 * backoffice onde se publica a festa de outra pessoa, por isso o
 * consentimento não é uma caixa a mais no formulário: sem ele, a base de
 * dados recusa publicar.
 */
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Web';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'trabajo';

    protected static ?string $pluralModelLabel = 'trabajos';

    protected static ?string $navigationLabel = 'Portfolio (web)';

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
