<?php

namespace App\Filament\Resources\BankBoletos;

use App\Filament\Resources\BankBoletos\Pages\CreateBankBoleto;
use App\Filament\Resources\BankBoletos\Pages\EditBankBoleto;
use App\Filament\Resources\BankBoletos\Pages\ListBankBoletos;
use App\Filament\Resources\BankBoletos\Schemas\BankBoletoForm;
use App\Filament\Resources\BankBoletos\Tables\BankBoletosTable;
use App\Models\BankBoleto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BankBoletoResource extends Resource
{
    protected static ?string $model = BankBoleto::class;

    protected static ?string $modelLabel = 'boleto';

    protected static ?string $pluralModelLabel = 'boletos';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'nosso_numero';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pendente')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return BankBoletoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankBoletosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankBoletos::route('/'),
            'create' => CreateBankBoleto::route('/create'),
            'edit' => EditBankBoleto::route('/{record}/edit'),
        ];
    }
}
