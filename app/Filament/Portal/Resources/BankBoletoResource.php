<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\BankBoletoResource\Pages\ListBankBoletos;
use App\Models\BankBoleto;
use App\Models\Client;
use App\Services\BankBoletoService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class BankBoletoResource extends Resource
{
    protected static ?string $model = BankBoleto::class;

    protected static ?string $modelLabel = 'boleto';

    protected static ?string $pluralModelLabel = 'boletos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $client = Client::where('portal_user_id', Auth::id())->first();

        return parent::getEloquentQuery()
            ->whereHas('receivable', fn (Builder $q) => $q->where('client_id', $client?->id));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_vencimento', 'asc')
            ->columns([
                TextColumn::make('nosso_numero')->label('Nosso Número')->searchable(),
                TextColumn::make('receivable.descricao')->label('Parcela')->limit(30)->placeholder('—'),
                TextColumn::make('valor')->label('Valor')->money('BRL')->sortable(),
                TextColumn::make('data_vencimento')->label('Vencimento')->date('d/m/Y')->sortable(),
                TextColumn::make('linha_digitavel')->label('Linha Digitável')->limit(50)->placeholder('—')->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente'  => 'warning',
                        'emitido'   => 'info',
                        'pago'      => 'success',
                        'cancelado' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente'  => 'Pendente',
                        'emitido'   => 'Emitido',
                        'pago'      => 'Pago',
                        'cancelado' => 'Cancelado',
                        default     => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pendente'  => 'Pendente',
                        'emitido'   => 'Emitido',
                        'pago'      => 'Pago',
                        'cancelado' => 'Cancelado',
                    ]),
            ])
            ->actions([
                Action::make('baixarPdf')
                    ->label('Baixar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->visible(fn (BankBoleto $record): bool => in_array($record->status, ['pendente', 'emitido']))
                    ->action(fn (BankBoleto $record): StreamedResponse => response()->streamDownload(
                        fn () => print(BankBoletoService::renderPdf($record)),
                        "boleto-{$record->nosso_numero}.pdf",
                        ['Content-Type' => 'application/pdf']
                    )),
            ]);
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => ListBankBoletos::route('/'),
        ];
    }
}
