<?php

namespace App\Filament\Resources\BankRemessas;

use App\Filament\Resources\BankRemessas\Pages\ListBankRemessas;
use App\Filament\Resources\BankRemessas\Pages\ViewBankRemessa;
use App\Filament\Resources\BankRemessas\RelationManagers\BoletosRelationManager;
use App\Filament\Resources\BankRemessas\Schemas\BankRemessaForm;
use App\Filament\Resources\BankRemessas\Tables\BankRemessasTable;
use App\Models\BankRemessa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BankRemessaResource extends Resource
{
    protected static ?string $model = BankRemessa::class;

    protected static ?string $modelLabel = 'remessa';

    protected static ?string $pluralModelLabel = 'remessas bancárias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'sequencial_arquivo';

    public static function form(Schema $schema): Schema
    {
        return BankRemessaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankRemessasTable::configure($table);
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
            'index' => ListBankRemessas::route('/'),
            'view' => ViewBankRemessa::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // Remessas são criadas apenas via Bulk Action em BankBoleto (Milestone 5)
        return false;
    }
}
