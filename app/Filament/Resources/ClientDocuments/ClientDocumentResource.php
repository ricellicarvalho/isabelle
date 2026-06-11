<?php

namespace App\Filament\Resources\ClientDocuments;

use App\Filament\Resources\ClientDocuments\Pages\ListClientDocuments;
use App\Filament\Resources\ClientDocuments\Pages\ManageClientDocuments;
use App\Filament\Resources\ClientDocuments\Tables\ClientDocumentsTable;
use App\Models\Client;
use App\Models\ClientDocument;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ClientDocumentResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'portal do cliente';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'razao_social';

    // Delega as permissões para a policy de ClientDocument,
    // evitando conflito com as permissões do ClientResource.
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', ClientDocument::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', ClientDocument::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update', ClientDocument::class) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete', ClientDocument::class) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('deleteAny', ClientDocument::class) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('clientDocuments')
            ->withMax('clientDocuments', 'created_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ClientDocumentsTable::configure($table)
            ->actions([
                Action::make('manage')
                    ->label('Documentos')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn (Client $record): string => static::getUrl('manage', ['record' => $record])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClientDocuments::route('/'),
            'manage' => ManageClientDocuments::route('/{record}/documents'),
        ];
    }
}
