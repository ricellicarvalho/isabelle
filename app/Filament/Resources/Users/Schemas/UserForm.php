<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Role;

class UserForm
{
    private const ROLE_LABELS = [
        'super_admin'        => 'Super Admin',
        'administrador'      => 'Administrador',
        'financeiro'         => 'Financeiro',
        'colaborador'        => 'Colaborador',
        'seguranca_trabalho' => 'Segurança de Trabalho',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('Dados do usuário')
                            ->icon(Heroicon::User)
                            ->components([
                                Section::make('Informações do usuário')
                                    ->components([
                                        TextInput::make('name')
                                            ->label('Nome')
                                            ->required(),

                                        TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true),

                                        TextInput::make('password')
                                            ->label('Senha')
                                            ->password()
                                            ->revealable()
                                            ->visibleOn('create')
                                            ->required(),

                                        TextInput::make('phone')
                                            ->label('Telefone')
                                            ->mask('(99) 99999-9999'),
                                    ]),
                            ]),

                        Tab::make('Perfil de Acesso')
                            ->icon(Heroicon::ShieldCheck)
                            ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'administrador']))
                            ->components([
                                Section::make('Perfil de Acesso')
                                    ->description('Defina o perfil de acesso deste usuário ao sistema.')
                                    ->components([
                                        Select::make('roles')
                                            ->label('Perfil')
                                            ->multiple()
                                            ->relationship(
                                                name: 'roles',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn ($query) => $query->whereIn('name', self::allowedRoleNames()),
                                            )
                                            ->getOptionLabelFromRecordUsing(
                                                fn (Role $record): string => self::ROLE_LABELS[$record->name] ?? $record->name
                                            )
                                            ->preload()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }

    private static function allowedRoleNames(): array
    {
        $user = auth()->user();

        return match (true) {
            $user?->hasRole('super_admin')   => ['administrador', 'financeiro', 'colaborador', 'seguranca_trabalho'],
            $user?->hasRole('administrador') => ['financeiro', 'colaborador', 'seguranca_trabalho'],
            default                          => [],
        };
    }
}
