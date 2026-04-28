<?php

namespace App\Filament\Resources\Receivables;

use App\Filament\Resources\Receivables\Pages\CreateReceivable;
use App\Filament\Resources\Receivables\Pages\EditReceivable;
use App\Filament\Resources\Receivables\Pages\ListReceivables;
use App\Filament\Resources\Receivables\RelationManagers\BankBoletosRelationManager;
use App\Filament\Resources\Receivables\Schemas\ReceivableForm;
use App\Filament\Resources\Receivables\Tables\ReceivablesTable;
use App\Models\Receivable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReceivableResource extends Resource
{
    protected static ?string $model = Receivable::class;

    protected static ?string $modelLabel = 'conta a receber';

    protected static ?string $pluralModelLabel = 'contas a receber';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 2;

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
        return 'Parcelas pendentes';
    }

    public static function form(Schema $schema): Schema
    {
        return ReceivableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceivablesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BankBoletosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceivables::route('/'),
            'create' => CreateReceivable::route('/create'),
            'edit' => EditReceivable::route('/{record}/edit'),
        ];
    }
}
