<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs; // Alterado de Forms para Schemas
use Filament\Schemas\Components\Tabs\Tab; // Alterado de Forms para Schemas
use Filament\Schemas\Components\Section; // Alterado de Forms para Schemas
use Filament\Forms\Components\TextInput; 
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        // ABA 1: DADOS DO USUÁRIO
                        Tab::make('User data')
                            ->label('Dados do usuário')
                            ->badge(5)
                            ->badgeColor('success')
                            ->icon(Heroicon::User)
                            ->components([ // <--- Na v5, use components() dentro da Tab
                                Section::make('Informações do usuário')
                                    ->extraAttributes([
                                        'class' => 'border-2 border-primary-500 rounded-xl shadow-lg p-6 bg-white'
                                    ])
                                    ->components([ // <--- E aqui também
                                        TextInput::make('name')
                                            ->label('Nome')
                                            ->required()
                                            ->extraInputAttributes([
                                                'class' => 'border-2 border-primary-400 rounded-lg'
                                            ]),

                                        TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true),

                                        TextInput::make('password')
                                            ->password()
                                            ->visibleOn('create')
                                            ->required(),

                                        TextInput::make('phone')
                                            ->mask('(99) 99999-9999'),
                                    ])
                            ]),

                        // ABA 2: STATUS ADMIN
                        Tab::make('is admin')
                            ->label('é Administrador?')
                            ->icon(Heroicon::ShieldCheck)
                            ->components([ // <--- Use components()
                                Section::make('Administrador')
                                    ->components([
                                        Toggle::make('is_admin')
                                            ->label('é Administrador?')
                                            ->helperText('Habilite se o usuário é administrador?'),
                                    ])
                            ])
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }
}