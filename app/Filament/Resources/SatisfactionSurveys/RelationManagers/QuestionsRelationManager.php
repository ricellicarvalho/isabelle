<?php

namespace App\Filament\Resources\SatisfactionSurveys\RelationManagers;

use App\Enums\SurveyResponseType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Perguntas';

    protected static ?string $modelLabel = 'pergunta';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('description')->label('Pergunta')->required()->rows(3)->columnSpanFull(),
            Select::make('answer_type')->label('Tipo de resposta da pergunta')
                ->options(fn (): array => $this->getOwnerRecord()->response_type === SurveyResponseType::Numeric
                    ? ['scale' => 'Nota de 0 a 10', 'text' => 'Resposta textual']
                    : ['scale' => 'Péssimo a Excelente'])
                ->default('scale')->required(),
            Toggle::make('is_visible')->label('Pergunta visível na avaliação')->default(true),
            Toggle::make('is_required')->label('Resposta obrigatória')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('description')
            ->defaultSort('display_order')
            ->reorderable('display_order')
            ->columns([
                TextColumn::make('display_order')->label('Ordem')->sortable(),
                TextColumn::make('description')->label('Pergunta')->alignment(Alignment::Center)->wrap()->searchable(),
                TextColumn::make('answer_type')->label('Tipo')->formatStateUsing(fn ($state) => $state === 'text' ? 'Textual' : 'Nota'),
                IconColumn::make('is_visible')->label('Visível')->alignment(Alignment::Center)->boolean(),
                IconColumn::make('is_required')->label('Obrigatória')->alignment(Alignment::Center)->boolean(),
                TextColumn::make('answers_count')->counts('answers')->label('Respostas')->alignment(Alignment::Center),
            ])
            ->headerActions([
                CreateAction::make()->label('Adicionar pergunta')->mutateFormDataUsing(function (array $data): array {
                    $data['display_order'] = ($this->getOwnerRecord()->questions()->withTrashed()->max('display_order') ?? 0) + 1;

                    return $data;
                }),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
