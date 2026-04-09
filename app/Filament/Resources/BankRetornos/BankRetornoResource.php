<?php

namespace App\Filament\Resources\BankRetornos;

use App\Filament\Resources\BankRetornos\Pages\ListBankRetornos;
use App\Filament\Resources\BankRetornos\Pages\ViewBankRetorno;
use App\Filament\Resources\BankRetornos\RelationManagers\BoletosRelationManager;
use App\Filament\Resources\BankRetornos\Schemas\BankRetornoForm;
use App\Filament\Resources\BankRetornos\Tables\BankRetornosTable;
use App\Models\BankRetorno;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BankRetornoResource extends Resource
{
    protected static ?string $model = BankRetorno::class;

    protected static ?string $modelLabel = 'retorno';

    protected static ?string $pluralModelLabel = 'retornos bancários';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'nome_arquivo';

    public static function form(Schema $schema): Schema
    {
        return BankRetornoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankRetornosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BoletosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankRetornos::route('/'),
            'view' => ViewBankRetorno::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Retornos são criados via ação "Importar Retorno" no header da listagem
        return false;
    }
}
