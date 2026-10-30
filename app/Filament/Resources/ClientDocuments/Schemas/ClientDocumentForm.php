<?php

namespace App\Filament\Resources\ClientDocuments\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                                    'laudo' => 'Laudo',
                                    'foto' => 'Foto',
                                    'relatorio' => 'Relatório',
                                    'matriz_risco' => 'Matriz de Risco',
                                    'certificado' => 'Certificado',
                                    'boleto' => 'Boleto',
                                    'nota_fiscal' => 'Nota Fiscal',
                                    'devolutiva_nr1' => 'Devolutiva NR-1',
                                    'outro' => 'Outro',
                                ];
                                if (auth()->user()?->hasAnyRole(['super_admin', 'administrador', 'financeiro'])) {
                                    $options['proposta'] = 'Proposta';
                                }

                                return $options;
                            })
                            ->default('outro')
                            ->required()
                            ->live()
                            ->native(false),

                        Select::make('nr1_cycle_id')
                            ->label('Ciclo NR-1')
                            ->relationship(
                                name: 'nr1Cycle',
                                titleAttribute: 'reference_year',
                                modifyQueryUsing: fn ($query, Get $get) => $query
                                    ->where('client_id', $get('client_id'))
                                    ->orderByDesc('reference_year'),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "NR-1/{$record->reference_year}")
                            ->visible(fn (Get $get): bool => $get('tipo') === 'devolutiva_nr1')
                            ->required(fn (Get $get): bool => $get('tipo') === 'devolutiva_nr1')
                            ->dehydrateStateUsing(fn ($state, Get $get) => $get('tipo') === 'devolutiva_nr1' ? $state : null)
                            ->native(false),

                        Toggle::make('visivel_portal')
                            ->label('Visível no Portal do Cliente')
                            ->default(true),

                        FileUpload::make('caminho_arquivo')
                            ->label('Arquivos')
                            ->helperText('Tamanho máximo: 25 MB por arquivo. Até 20 arquivos por documento.')
                            ->required()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->disk('local')
                            ->directory('documentos-clientes')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'image/svg+xml',
                                'image/bmp',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'video/mp4',
                                'video/webm',
                                'video/ogg',
                                'video/quicktime',
                                'video/x-msvideo',
                            ])
                            ->maxSize(config('client_documents.max_file_size_kb'))
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
