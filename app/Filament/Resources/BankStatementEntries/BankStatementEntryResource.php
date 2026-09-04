<?php

namespace App\Filament\Resources\BankStatementEntries;

use App\Filament\Resources\BankStatementEntries\Pages\ListBankStatementEntries;
use App\Models\BankStatementEntry;
use App\Models\BankMovement;
use App\Models\Category;
use App\Services\BankReconciliationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BankStatementEntryResource extends Resource
{
    protected static ?string $model = BankStatementEntry::class;
    protected static ?string $navigationLabel = 'Conciliação Bancária';
    protected static ?string $pluralModelLabel = 'conciliações bancárias';
    protected static string|BackedEnum|null $navigationIcon = null;
    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';
    protected static ?int $navigationSort = 3;
    public static function canCreate(): bool { return false; }
    public static function table(Table $table): Table
    {
        return $table->defaultSort('occurred_at', 'desc')->columns([
            TextColumn::make('occurred_at')->label('Data')->dateTime('d/m/Y H:i')->sortable(), TextColumn::make('bankAccount.nome')->label('Conta'), TextColumn::make('document')->label('Número')->placeholder('—'), TextColumn::make('memo')->label('Histórico')->wrap()->searchable(),
            TextColumn::make('amount')->label('Valor banco')->formatStateUsing(fn ($state) => (($state < 0) ? '- ' : '+ ').'R$ '.number_format(abs((float) $state), 2, ',', '.'))->color(fn ($state) => $state < 0 ? 'danger' : 'success'),
            TextColumn::make('status')->label('Situação')->badge()->formatStateUsing(fn ($state) => ['pending' => 'Pendente', 'reconciled' => 'Conciliado', 'archived' => 'Arquivado'][$state] ?? $state),
        ])->filters([SelectFilter::make('bank_account_id')->label('Conta')->relationship('bankAccount', 'nome'), SelectFilter::make('status')->options(['pending' => 'Pendente', 'reconciled' => 'Conciliado', 'archived' => 'Arquivado'])->default('pending')])->actions([
            Action::make('reconcile')->label('Vincular')->visible(fn ($record) => $record->status === 'pending')->schema([
                Select::make('movement')->label('Movimentação do sistema')->options(fn (BankStatementEntry $record) => BankMovement::where('bank_account_id', $record->bank_account_id)->where('direction', (float) $record->amount >= 0 ? 'credit' : 'debit')->whereBetween('occurred_at', [$record->occurred_at->copy()->subDays(5), $record->occurred_at->copy()->addDays(5)])->get()->mapWithKeys(fn ($m) => [$m->id => $m->occurred_at->format('d/m/Y').' · R$ '.number_format((float) $m->amount, 2, ',', '.').' · '.$m->description]))->required()->searchable(),
            ])->action(function (BankStatementEntry $record, array $data): void { app(BankReconciliationService::class)->reconcile($record, BankMovement::findOrFail($data['movement'])); Notification::make()->success()->title('Movimentação conciliada')->send(); }),
            Action::make('create')->label('Criar lançamento')->visible(fn ($record) => $record->status === 'pending')->schema([Select::make('category_id')->label('Categoria')->options(fn () => Category::orderBy('descricao')->pluck('descricao', 'id'))->required()->searchable(), TextInput::make('description')->label('Histórico')])->action(function (BankStatementEntry $record, array $data): void { app(BankReconciliationService::class)->createAndReconcile($record, $data['category_id'], $data['description'] ?? null); Notification::make()->success()->title('Lançamento criado e conciliado')->send(); }),
            Action::make('archive')->label('Arquivar')->color('gray')->requiresConfirmation()->visible(fn ($record) => $record->status === 'pending')->action(fn (BankStatementEntry $record) => $record->update(['status' => 'archived'])),
        ]);
    }
    public static function getPages(): array { return ['index' => ListBankStatementEntries::route('/')]; }
}
