<?php

namespace App\Filament\Resources\NfseServiceCodes;

use App\Filament\Resources\NfseServiceCodes\Pages\CreateNfseServiceCode;
use App\Filament\Resources\NfseServiceCodes\Pages\EditNfseServiceCode;
use App\Filament\Resources\NfseServiceCodes\Pages\ListNfseServiceCodes;
use App\Filament\Resources\NfseServiceCodes\Schemas\NfseServiceCodeForm;
use App\Filament\Resources\NfseServiceCodes\Tables\NfseServiceCodesTable;
use App\Models\NfseServiceCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class NfseServiceCodeResource extends Resource
{
    protected static ?string $model = NfseServiceCode::class;

    protected static ?string $modelLabel = 'Código de Serviço NFSe';

    protected static ?string $pluralModelLabel = 'Códigos de Serviço NFSe';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'descricao';

    public static function form(Schema $schema): Schema
    {
        return NfseServiceCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NfseServiceCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListNfseServiceCodes::route('/'),
            'create' => CreateNfseServiceCode::route('/create'),
            'edit'   => EditNfseServiceCode::route('/{record}/edit'),
        ];
    }
}
