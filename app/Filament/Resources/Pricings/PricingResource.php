<?php

namespace App\Filament\Resources\Pricings;

use App\Filament\Resources\Pricings\Pages\CreatePricing;
use App\Filament\Resources\Pricings\Pages\EditPricing;
use App\Filament\Resources\Pricings\Pages\ListPricings;
use App\Filament\Resources\Pricings\Schemas\PricingForm;
use App\Filament\Resources\Pricings\Tables\PricingsTable;
use App\Models\Pricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PricingResource extends Resource
{
    protected static ?string $model = Pricing::class;

    protected static ?string $modelLabel = 'precificação';

    protected static ?string $pluralModelLabel = 'precificações';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return PricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PricingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPricings::route('/'),
            'create' => CreatePricing::route('/create'),
            'edit'   => EditPricing::route('/{record}/edit'),
        ];
    }
}
