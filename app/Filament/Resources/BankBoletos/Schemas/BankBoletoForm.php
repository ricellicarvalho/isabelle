<?php

namespace App\Filament\Resources\BankBoletos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BankBoletoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('Identificação')
                            ->icon(Heroicon::DocumentText)
                            ->components([
                                Section::make('Vínculo e Identificação')
                                    ->columns(2)
                                    ->components([
                                        // RN11 - vínculo obrigatório com Receivable
                                        Select::make('receivable_id')
                                            ->label('Parcela (Conta a Receber)')
                                            ->relationship(
                                                'receivable',
                                                'descricao',
                                                fn ($query) => $query->whereIn('status', ['pendente', 'vencido'])
                                            )
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->descricao} (R$ ".number_format($record->valor, 2, ',', '.').')')
                                            ->searchable(['descricao'])
                                            ->preload()
                                            ->required()
                                            ->native(false)
                                            ->columnSpanFull(),

                                        TextInput::make('numero_documento')
                                            ->label('Número do Documento')
                                            ->maxLength(255),

                                        TextInput::make('carteira')
                                            ->label('Carteira')
                                            ->maxLength(255)
                                            ->placeholder('Ex: 17, 109'),
                                    ]),
                            ]),

                        Tab::make('Valores')
                            ->icon(Heroicon::CurrencyDollar)
                            ->components([
                                Section::make('Valores e Vencimento')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('valor')
                                            ->label('Valor')
                                            ->required()
                                            ->numeric()
                                            ->prefix('R$')
                                            ->minValue(0),

                                        DatePicker::make('data_vencimento')
                                            ->label('Data de Vencimento')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),
                                    ]),
                            ]),

                        Tab::make('Códigos Bancários')
                            ->icon(Heroicon::QrCode)
                            ->components([
                                Section::make('Identificação Bancária')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('nosso_numero')
                                            ->label('Nosso Número')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->helperText('Gerado automaticamente — único por boleto (RN12)'),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'pendente' => 'Pendente',
                                                'emitido' => 'Emitido',
                                                'pago' => 'Pago',
                                                'cancelado' => 'Cancelado',
                                            ])
                                            ->default('pendente')
                                            ->required()
                                            ->native(false),

                                        TextInput::make('codigo_barras')
                                            ->label('Código de Barras')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        TextInput::make('linha_digitavel')
                                            ->label('Linha Digitável')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('instrucao_remessa')
                                            ->label('Instrução para Remessa')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }
}
