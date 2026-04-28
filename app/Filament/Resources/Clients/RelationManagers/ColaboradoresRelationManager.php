<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ColaboradoresRelationManager extends RelationManager
{
    protected static string $relationship = 'colaboradores';

    protected static ?string $title = 'Colaboradores';

    protected static ?string $modelLabel = 'colaborador';

    protected static ?string $pluralModelLabel = 'colaboradores';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('telefone')
                ->label('Telefone')
                ->maxLength(20)
                ->placeholder('(00) 00000-0000'),

            TextInput::make('local')
                ->label('Local / Setor')
                ->maxLength(255)
                ->placeholder('Ex: Administrativo, RH, Produção'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->defaultSort('nome', 'asc')
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefone')
                    ->label('Telefone')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('local')
                    ->label('Local / Setor')
                    ->placeholder('—')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Adicionar Colaborador')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
