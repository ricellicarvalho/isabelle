<?php

namespace App\Filament\Resources\ClientDocuments\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Documento')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'razao_social')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('tipo')
                            ->label('Tipo')
                            ->options(function (): array {
                                $options = [
                                    'laudo'        => 'Laudo',
                                    'foto'         => 'Foto',
                                    'relatorio'    => 'Relatório',
                                    'matriz_risco' => 'Matriz de Risco',
                                    'certificado'  => 'Certificado',
                                    'outro'        => 'Outro',
                                ];
                                if (auth()->user()?->hasAnyRole(['super_admin', 'administrador', 'financeiro'])) {
                                    $options['proposta'] = 'Proposta';
                                }
                                return $options;
                            })
                            ->default('outro')
                            ->required()
                            ->native(false),

                        Toggle::make('visivel_portal')
                            ->label('Visível no Portal do Cliente')
                            ->default(true),

                        FileUpload::make('caminho_arquivo')
                            ->label('Arquivos')
                            ->required()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->disk('local')
                            ->directory('documentos-clientes')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(20480)
                            ->maxFiles(20)
                            ->columnSpanFull(),

                        Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
