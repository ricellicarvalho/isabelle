<?php

namespace App\Filament\Resources\SatisfactionSurveys;

use App\Filament\Resources\SatisfactionSurveys\Pages\CreateSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\Pages\EditSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\Pages\ListSatisfactionSurveys;
use App\Filament\Resources\SatisfactionSurveys\Pages\SurveyResults;
use App\Filament\Resources\SatisfactionSurveys\RelationManagers\QuestionsRelationManager;
use App\Models\SatisfactionSurvey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SatisfactionSurveyResource extends Resource
{
    protected static ?string $model = SatisfactionSurvey::class;

    protected static ?string $modelLabel = 'avaliação de satisfação';

    protected static ?string $pluralModelLabel = 'avaliações de satisfação';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Pesquisas';

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $recordRouteKeyName = 'public_token';

    public static function form(Schema $schema): Schema
    {
        return Schemas\SatisfactionSurveyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SatisfactionSurveysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [QuestionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSatisfactionSurveys::route('/'),
            'create' => CreateSatisfactionSurvey::route('/create'),
            'edit' => EditSatisfactionSurvey::route('/{record}/edit'),
            'results' => SurveyResults::route('/{record}/results'),
        ];
    }
}
