<?php

namespace App\Filament\Resources\Payables;

use App\Filament\Resources\Payables\Pages\CreatePayable;
use App\Filament\Resources\Payables\Pages\EditPayable;
use App\Filament\Resources\Payables\Pages\ListPayables;
use App\Filament\Resources\Payables\Schemas\PayableForm;
use App\Filament\Resources\Payables\Tables\PayablesTable;
use App\Models\Payable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PayableResource extends Resource
{
    protected static ?string $model = Payable::class;

    protected static ?string $modelLabel = 'conta a pagar';

    protected static ?string $pluralModelLabel = 'contas a pagar';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'descricao';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pendente')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Contas pendentes';
    }

    public static function form(Schema $schema): Schema
    {
        return PayableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayablesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayables::route('/'),
            'create' => CreatePayable::route('/create'),
            'edit' => EditPayable::route('/{record}/edit'),
        ];
    }
}
