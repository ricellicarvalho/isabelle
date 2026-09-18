<?php

namespace App\Filament\Resources\SatisfactionSurveys\Schemas;

use App\Enums\SurveyResponseType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SatisfactionSurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])
                ->columnSpanFull()
                ->extraAttributes(['class' => 'survey-form-grid'])
                ->components([
                    Section::make('Avaliação')->columns(2)->columnSpan(1)->extraAttributes(['style' => 'height: 100%'])->components([
                        TextInput::make('description')->label('Descrição')->required()->maxLength(255)->columnSpanFull(),
                        Textarea::make('introduction')->label('Texto de apresentação')->rows(3)->columnSpanFull(),
                        Select::make('response_type')->label('Tipo de resposta')->options(
                            collect(SurveyResponseType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()
                        )->required()->native(false),
                        Toggle::make('is_active')->label('Ativa')->helperText('A avaliação também precisa estar dentro do período de publicação.'),
                    ]),
                    Section::make('Publicação')->columns(2)->columnSpan(1)->extraAttributes(['style' => 'height: 100%'])->components([
                        DateTimePicker::make('starts_at')->label('Disponível a partir de')->required()->seconds(false)->displayFormat('d/m/Y H:i')->default(now()),
                        DateTimePicker::make('ends_at')->label('Disponível até')->required()->seconds(false)->displayFormat('d/m/Y H:i')->after('starts_at'),
                        Toggle::make('allow_multiple_submissions')->label('Permitir mais de uma resposta por navegador')->default(true)->columnSpanFull(),
                        Textarea::make('thank_you_message')->label('Mensagem de agradecimento')->rows(2)->columnSpanFull(),
                    ]),
                ]),
        ]);
    }
}
