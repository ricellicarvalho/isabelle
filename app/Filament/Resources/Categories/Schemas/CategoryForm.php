<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Services\CategoryCodeGenerator;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plano de Contas')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        SelectTree::make('parent_id')
                            ->label('Conta Pai')
                            ->relationship('parent', 'descricao', 'parent_id')
                            ->searchable()
                            ->placeholder('Nenhuma (conta raiz)')
                            ->enableBranchNode()
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set) => $set(
                                'codigo',
                                app(CategoryCodeGenerator::class)->next(filled($state) ? (int) $state : null),
                            ))
                            ->columnSpanFull(),

                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->default(fn (): string => app(CategoryCodeGenerator::class)->next())
                            ->readOnly()
                            ->maxLength(255)
                            ->helperText('Gerado automaticamente de acordo com a conta pai.'),

                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255),

                        Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'receita' => 'Receita',
                                'custo' => 'Custo',
                                'despesa' => 'Despesa',
                            ])
                            ->required()
                            ->native(false),

                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true),
                    ]),
            ]);
    }
}
