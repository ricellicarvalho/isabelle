<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Portal\Resources\ContractResource\Pages\ViewContract;
use App\Models\Client;
use App\Models\Contract;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

class ContractResource extends PortalResource
{
    protected static ?string $model = Contract::class;

    protected static ?string $modelLabel = 'contrato';

    protected static ?string $pluralModelLabel = 'contratos';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Contratos';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $client = Client::where('portal_user_id', Auth::id())->first();

        return parent::getEloquentQuery()
            ->where('client_id', $client?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Detalhes do Contrato')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('numero')->label('Número'),
                        TextEntry::make('tipo_servico')->label('Serviço'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ativo'     => 'success',
                                'cancelado' => 'danger',
                                default     => 'warning',
                            }),
                        TextEntry::make('valor_total')->label('Valor Total')->money('BRL'),
                        TextEntry::make('data_inicio')->label('Início')->date('d/m/Y'),
                        TextEntry::make('data_fim')->label('Fim')->date('d/m/Y')->placeholder('Indeterminado'),
                        TextEntry::make('quantidade_parcelas')->label('Parcelas'),
                        TextEntry::make('forma_pagamento')->label('Forma de Pagamento'),
                        TextEntry::make('observacoes')->label('Observações')->columnSpanFull()->placeholder('—'),
                    ]),

                Section::make('Documento do Contrato')
                    ->icon(Heroicon::DocumentText)
                    ->iconColor('primary')
                    ->columnSpanFull()
                    ->compact()
                    ->components([
                        TextEntry::make('arquivo_pdf')
                            ->label('')
                            ->columnSpanFull()
                            ->html()
                            ->state(function ($record): HtmlString {
                                if (! filled($record->arquivo_pdf)) {
                                    return new HtmlString(
                                        '<span style="display:inline-flex;align-items:center;gap:8px;color:#9ca3af;font-size:.875rem;">'
                                        . '<svg style="width:18px;height:18px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
                                        . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>'
                                        . '</svg>'
                                        . 'Nenhum arquivo anexado a este contrato.'
                                        . '</span>'
                                    );
                                }

                                $nome = basename($record->arquivo_pdf);

                                return new HtmlString(
                                    '<div style="display:inline-flex;align-items:center;gap:12px;background:#f0fdf4;border:1px solid #86efac;border-radius:.75rem;padding:10px 16px;">'
                                    . '<div style="width:40px;height:40px;background:#dcfce7;border-radius:.5rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
                                    . '<svg style="width:22px;height:22px;color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
                                    . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>'
                                    . '</svg>'
                                    . '</div>'
                                    . '<div>'
                                    . '<p style="margin:0;font-size:.875rem;font-weight:600;color:#15803d;">' . e($nome) . '</p>'
                                    . '<p style="margin:4px 0 0; font-size:.75rem; font-weight:500; color:#166534; background:linear-gradient(145deg,#f0fdf4,#dcfce7); border:1px solid #86efac; border-radius:.5rem; padding:5px 10px; display:inline-block;">Use o botão <strong style="color:#14532d;">Baixar Contrato PDF</strong> no topo desta página para fazer o download.</p>'
                                    . '</div>'
                                    . '</div>'
                                );
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_inicio', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Nº Contrato')->searchable()->sortable(),
                TextColumn::make('tipo_servico')->label('Serviço')->limit(30),
                TextColumn::make('valor_total')->label('Valor Total')->money('BRL')->sortable(),
                TextColumn::make('quantidade_parcelas')->label('Parcelas'),
                TextColumn::make('data_inicio')->label('Início')->date('d/m/Y')->sortable(),
                TextColumn::make('data_fim')->label('Fim')->date('d/m/Y')->placeholder('Indeterminado'),
                IconColumn::make('arquivo_pdf')
                    ->label('PDF')
                    ->boolean()
                    ->trueIcon(Heroicon::DocumentArrowDown)
                    ->falseIcon(Heroicon::DocumentMinus)
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state): string => $state ? 'PDF disponível' : 'Sem PDF'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ativo'     => 'success',
                        'cancelado' => 'danger',
                        default     => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ativo'     => 'Ativo',
                        'cancelado' => 'Cancelado',
                        'rascunho'  => 'Rascunho',
                        default     => ucfirst($state),
                    }),
            ])
            ->actions([
                Action::make('downloadPdf')
                    ->label('Baixar PDF')
                    ->icon(Heroicon::DocumentArrowDown)
                    ->color('primary')
                    ->tooltip('Baixar contrato PDF')
                    ->visible(fn (Contract $record): bool => filled($record->arquivo_pdf))
                    ->action(fn (Contract $record): BinaryFileResponse => response()->download(
                        Storage::disk('local')->path($record->arquivo_pdf),
                        $record->numero . '_' . now()->format('Y-m-d_H-i') . '.pdf',
                        ['Content-Type' => 'application/pdf']
                    )),
                ViewAction::make()
                    ->label('Detalhes')
                    ->icon(Heroicon::Eye),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
            'view'  => ViewContract::route('/{record}'),
        ];
    }
}
