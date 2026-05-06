<?php

namespace App\Filament\Resources\NfseConfigs;

use App\Filament\Resources\NfseConfigs\Pages\CreateNfseConfig;
use App\Filament\Resources\NfseConfigs\Pages\EditNfseConfig;
use App\Filament\Resources\NfseConfigs\Pages\ListNfseConfigs;
use App\Filament\Resources\NfseConfigs\Schemas\NfseConfigForm;
use App\Filament\Resources\NfseConfigs\Tables\NfseConfigsTable;
use App\Models\NfseConfig;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class NfseConfigResource extends Resource
{
    protected static ?string $model = NfseConfig::class;

    protected static ?string $modelLabel = 'Config NFSe';

    protected static ?string $pluralModelLabel = 'Configurações NFSe';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'razao_social';

    public static function form(Schema $schema): Schema
    {
        return NfseConfigForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NfseConfigsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListNfseConfigs::route('/'),
            'create' => CreateNfseConfig::route('/create'),
            'edit'   => EditNfseConfig::route('/{record}/edit'),
        ];
    }
}
