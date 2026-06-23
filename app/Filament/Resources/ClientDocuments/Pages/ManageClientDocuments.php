<?php

namespace App\Filament\Resources\ClientDocuments\Pages;

use App\Filament\Resources\ClientDocuments\ClientDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManageClientDocuments extends ManageRelatedRecords
{
    protected static string $resource = ClientDocumentResource::class;

    protected static string $relationship = 'clientDocuments';

    public function getTitle(): string
    {
        return 'Documentos: ' . $this->getOwnerRecord()->razao_social;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Voltar')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->url(ClientDocumentResource::getUrl('index')),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->components([
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
                            'boleto'       => 'Boleto',
                            'nota_fiscal'  => 'Nota Fiscal',
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

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'laudo'        => 'Laudo',
                        'foto'         => 'Foto',
                        'relatorio'    => 'Relatório',
                        'matriz_risco' => 'Matriz de Risco',
                        'certificado'  => 'Certificado',
                        'proposta'     => 'Proposta',
                        'boleto'       => 'Boleto',
                        'nota_fiscal'  => 'Nota Fiscal',
                        default        => 'Outro',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'laudo'        => 'info',
                        'foto'         => 'default',
                        'relatorio'    => 'success',
                        'matriz_risco' => 'warning',
                        'certificado'  => 'primary',
                        'proposta'     => 'danger',
                        'boleto'       => 'danger',
                        'nota_fiscal'  => 'amber',
                        default        => 'gray',
                    }),

                IconColumn::make('visivel_portal')
                    ->label('Visível no Portal')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Adicionar Documento')
                    ->modalHeading('Adicionar Documento')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Editar Documento'),
                DeleteAction::make()
                    ->modalHeading('Excluir Documento'),
            ]);
    }
}
