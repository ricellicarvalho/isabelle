<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Tradução do nome do menu e títulos
    protected static ?string $modelLabel = 'usuário';

    // Tradução do plural (usado no botão "Criar usuários" e no menu)
    protected static ?string $pluralModelLabel = 'usuários';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    // O texto que aparece no menu
    //protected static ?string $navigationLabel = 'Usuários'; 

    // --- MÉTODOS DE NAVEGAÇÃO (Coloque aqui) ---
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Número de usuários';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 10 ? 'warning' : 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema); //CREATE/UPDATE
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table); //READ
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),            
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
