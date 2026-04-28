<?php

namespace App\Filament\Resources\ClientDocuments;

use App\Filament\Resources\ClientDocuments\Pages\CreateClientDocument;
use App\Filament\Resources\ClientDocuments\Pages\EditClientDocument;
use App\Filament\Resources\ClientDocuments\Pages\ListClientDocuments;
use App\Filament\Resources\ClientDocuments\Schemas\ClientDocumentForm;
use App\Filament\Resources\ClientDocuments\Tables\ClientDocumentsTable;
use App\Models\ClientDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClientDocumentResource extends Resource
{
    protected static ?string $model = ClientDocument::class;

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'portal do cliente';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'titulo';

    public static function form(Schema $schema): Schema
    {
        return ClientDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientDocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClientDocuments::route('/'),
            'create' => CreateClientDocument::route('/create'),
            'edit'   => EditClientDocument::route('/{record}/edit'),
        ];
    }
}
