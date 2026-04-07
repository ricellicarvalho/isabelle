<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plano de Contas')
                    ->columns(2)
                    ->components([
                        SelectTree::make('parent_id')
                            ->label('Conta Pai')
                            ->relationship('parent', 'descricao', 'parent_id')
                            ->searchable()
                            ->placeholder('Nenhuma (conta raiz)')
                            ->enableBranchNode()
                            ->columnSpanFull(),

                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: 1.1'),

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

                        TextInput::make('order')
                            ->label('Ordem')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true),
                    ]),
            ]);
    }
}
