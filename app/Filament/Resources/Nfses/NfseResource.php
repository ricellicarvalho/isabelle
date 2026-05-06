<?php

namespace App\Filament\Resources\Nfses;

use App\Filament\Resources\Nfses\Pages\ListNfses;
use App\Filament\Resources\Nfses\Tables\NfsesTable;
use App\Models\Nfse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class NfseResource extends Resource
{
    protected static ?string $model = Nfse::class;

    protected static ?string $modelLabel = 'NFSe';

    protected static ?string $pluralModelLabel = 'NFSes';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'numero';

    public static function getNavigationBadge(): ?string
    {
        $emitidas = static::getModel()::where('status', 'emitida')
            ->where('ambiente', config('nfse.ambiente'))
            ->count();

        return $emitidas > 0 ? (string) $emitidas : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'NFSes emitidas';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return NfsesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNfses::route('/'),
        ];
    }
}
