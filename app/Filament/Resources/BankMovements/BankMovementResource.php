<?php

namespace App\Filament\Resources\BankMovements;

use App\Filament\Resources\BankMovements\Pages\CreateBankMovement;
use App\Filament\Resources\BankMovements\Pages\EditBankMovement;
use App\Filament\Resources\BankMovements\Pages\ListBankMovements;
use App\Filament\Resources\BankMovements\Schemas\BankMovementForm;
use App\Filament\Resources\BankMovements\Tables\BankMovementsTable;
use App\Models\BankMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BankMovementResource extends Resource
{
    protected static ?string $model = BankMovement::class;
    protected static ?string $modelLabel = 'movimentação bancária';
    protected static ?string $pluralModelLabel = 'movimentações bancárias';
    protected static ?string $navigationLabel = 'Movimentação de Contas';
    protected static string|BackedEnum|null $navigationIcon = null;
    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema { return BankMovementForm::configure($schema); }
    public static function table(Table $table): Table { return BankMovementsTable::configure($table); }
    public static function getPages(): array { return ['index' => ListBankMovements::route('/'), 'create' => CreateBankMovement::route('/create'), 'edit' => EditBankMovement::route('/{record}/edit')]; }
}
