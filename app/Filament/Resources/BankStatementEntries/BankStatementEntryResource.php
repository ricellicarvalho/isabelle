<?php

namespace App\Filament\Resources\BankStatementEntries;

use App\Filament\Resources\BankMovements\Schemas\BankMovementForm;
use App\Filament\Resources\BankStatementEntries\Pages\ListBankStatementEntries;
use App\Models\BankMovement;
use App\Models\BankStatementEntry;
use App\Models\Category;
use App\Services\BankReconciliationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class BankStatementEntryResource extends Resource
{
    protected static ?string $model = BankStatementEntry::class;

    protected static ?string $navigationLabel = 'Conciliação Bancária';

    protected static ?string $pluralModelLabel = 'conciliações bancárias';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('occurred_at', 'desc')->columns([
            TextColumn::make('occurred_at')->label('Data')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('bankAccount.nome')->label('Conta'),
            TextColumn::make('document')->label('Número')->placeholder('—')->searchable(),
            TextColumn::make('memo')->label('Histórico')->wrap()->searchable(),
            TextColumn::make('amount')->label('Valor banco')->formatStateUsing(fn ($state) => (bccomp($state, '0', 2) < 0 ? '- ' : '+ ').'R$ '.number_format(abs((float) $state), 2, ',', '.'))->color(fn ($state) => bccomp($state, '0', 2) < 0 ? 'danger' : 'success'),
            TextColumn::make('allocated')->label('Alocado')->state(fn (BankStatementEntry $record) => bcsub(ltrim($record->amount, '-'), app(BankReconciliationService::class)->entryRemaining($record), 2))->money('BRL'),
            TextColumn::make('difference')->label('Diferença')->state(fn (BankStatementEntry $record) => app(BankReconciliationService::class)->entryRemaining($record))->money('BRL'),
            TextColumn::make('status')->label('Situação')->badge()->formatStateUsing(fn ($state, BankStatementEntry $record) => match (true) {
                $state === 'reconciled' => 'Conciliado', $state === 'archived' => 'Arquivado',
                $record->activeAllocations()->exists() => 'Parcial', default => 'Pendente',
            })->color(fn ($state, BankStatementEntry $record): string => match (true) {
                $state === 'reconciled' => 'success',
                $state === 'archived' => 'gray',
                $record->activeAllocations()->exists() => 'warning',
                default => 'danger',
            }),
        ])->filters([
            SelectFilter::make('bank_account_id')->label('Conta')->relationship('bankAccount', 'nome'),
            SelectFilter::make('status')->options(['pending' => 'Pendente / parcial', 'reconciled' => 'Conciliado', 'archived' => 'Arquivado'])->default('pending'),
        ])->headerActions([
            Action::make('comparison')->label('Banco × Sistema')->url(fn () => \App\Filament\Pages\BankReconciliationSummary::getUrl()),
        ])->actions([
            Action::make('allocate')->label('Alocar')->visible(fn (BankStatementEntry $record) => $record->status === 'pending' && auth()->user()->can('Reconcile:BankStatementEntry'))
                ->modalDescription(fn (BankStatementEntry $record) => 'Diferença pendente: R$ '.number_format((float) app(BankReconciliationService::class)->entryRemaining($record), 2, ',', '.'))
                ->schema([
                    Repeater::make('allocations')->label('Movimentações do sistema')->addActionLabel('Adicionar outra movimentação')->minItems(1)->defaultItems(1)->columns(2)->schema([
                        Select::make('movement_id')->label('Movimentação do sistema')
                            ->options(fn (BankStatementEntry $record) => self::movementOptions($record))
                            ->getSearchResultsUsing(fn (BankStatementEntry $record, string $search) => self::searchMovementOptions($record, $search))
                            ->required()->searchable()->preload()
                            ->searchPrompt('Busque por histórico, documento ou favorecido')
                            ->noOptionsMessage('Nenhuma movimentação compatível. Se esta operação ainda não existe no sistema, use “Criar lançamento”.')
                            ->noSearchResultsMessage('Nenhuma movimentação compatível foi encontrada. Se necessário, use “Criar lançamento”.')
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        BankMovementForm::moneyInput('amount', 'Valor alocado'),
                    ]),
                ])->action(function (BankStatementEntry $record, array $data): void {
                    Gate::authorize('Reconcile:BankStatementEntry');
                    app(BankReconciliationService::class)->allocate($record, $data['allocations']);
                    $remaining = app(BankReconciliationService::class)->entryRemaining($record->fresh());
                    Notification::make()->success()->title('Alocações salvas')->body('Diferença pendente: R$ '.number_format((float) $remaining, 2, ',', '.'))->send();
                }),
            Action::make('complete')->label('Concluir')->color('success')->requiresConfirmation()
                ->visible(fn (BankStatementEntry $record) => $record->status === 'pending' && $record->activeAllocations()->exists() && auth()->user()->can('Reconcile:BankStatementEntry'))
                ->action(function (BankStatementEntry $record): void {
                    Gate::authorize('Reconcile:BankStatementEntry');
                    app(BankReconciliationService::class)->complete($record);
                    Notification::make()->success()->title('Conciliação concluída')->send();
                }),
            Action::make('create')->label('Criar lançamento')->visible(fn (BankStatementEntry $record) => $record->status === 'pending' && auth()->user()->can('Reconcile:BankStatementEntry'))
                ->schema([
                    Select::make('category_id')->label('Categoria')->options(fn () => Category::orderBy('descricao')->pluck('descricao', 'id'))->required()->searchable(),
                    TextInput::make('description')->label('Histórico'),
                ])->action(function (BankStatementEntry $record, array $data): void {
                    Gate::authorize('Reconcile:BankStatementEntry');
                    app(BankReconciliationService::class)->createAndReconcile($record, $data['category_id'], $data['description'] ?? null);
                    Notification::make()->success()->title('Lançamento criado e conciliado')->send();
                }),
            Action::make('undo')->label('Desfazer conciliação')->color('danger')->requiresConfirmation()
                ->visible(fn (BankStatementEntry $record) => $record->activeAllocations()->exists() && auth()->user()->can('UndoReconciliation:BankStatementEntry'))
                ->schema([Textarea::make('reason')->label('Justificativa')->required()->maxLength(2000)])
                ->action(function (BankStatementEntry $record, array $data): void {
                    Gate::authorize('UndoReconciliation:BankStatementEntry');
                    app(BankReconciliationService::class)->undo($record, $data['reason']);
                }),
            Action::make('archive')->label('Arquivar')->color('gray')->requiresConfirmation()
                ->visible(fn (BankStatementEntry $record) => $record->status === 'pending' && ! $record->activeAllocations()->exists() && auth()->user()->can('Archive:BankStatementEntry'))
                ->schema([Textarea::make('reason')->label('Justificativa')->required()->maxLength(2000)])
                ->action(function (BankStatementEntry $record, array $data): void {
                    Gate::authorize('Archive:BankStatementEntry');
                    app(BankReconciliationService::class)->archive($record, $data['reason']);
                }),
            Action::make('unarchive')->label('Reabrir')->visible(fn (BankStatementEntry $record) => $record->status === 'archived' && auth()->user()->can('Archive:BankStatementEntry'))
                ->action(function (BankStatementEntry $record): void {
                    Gate::authorize('Archive:BankStatementEntry');
                    app(BankReconciliationService::class)->unarchive($record);
                }),
            Action::make('history')->label('Histórico')->visible(fn (BankStatementEntry $record) => $record->allocations()->exists() || $record->events()->exists())
                ->modalContent(fn (BankStatementEntry $record) => view('filament.bank-reconciliation.history', [
                    'entry' => $record->load(['allocations.movement', 'events.createdBy']),
                ]))->modalSubmitAction(false)->modalCancelActionLabel('Fechar'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBankStatementEntries::route('/')];
    }

    public static function movementOptions(BankStatementEntry $entry): array
    {
        $suggestions = app(BankReconciliationService::class)->suggestions($entry)->mapWithKeys(function (array $suggestion): array {
            /** @var BankMovement $movement */
            $movement = $suggestion['movement'];
            $label = $movement->occurred_at->format('d/m/Y').' · R$ '.number_format((float) $suggestion['remaining'], 2, ',', '.')
                .' · '.$movement->description.' · '.implode(', ', $suggestion['reasons']);

            return [$movement->id => $label];
        })->all();

        return $suggestions + self::compatibleMovementOptions($entry);
    }

    private static function searchMovementOptions(BankStatementEntry $entry, string $search): array
    {
        return self::compatibleMovementOptions($entry, $search);
    }

    private static function compatibleMovementOptions(BankStatementEntry $entry, ?string $search = null): array
    {
        $direction = bccomp($entry->amount, '0', 2) >= 0 ? 'credit' : 'debit';

        return BankMovement::query()
            ->where('bank_account_id', $entry->bank_account_id)
            ->where('direction', $direction)
            ->where('status', 'confirmed')
            ->when(filled($search), fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('counterparty', 'like', "%{$search}%");
            }))
            ->latest('occurred_at')->limit(200)->get()
            ->filter(fn (BankMovement $movement): bool => bccomp(app(BankReconciliationService::class)->movementRemaining($movement), '0', 2) > 0)
            ->take(50)
            ->mapWithKeys(fn (BankMovement $movement): array => [
                $movement->id => $movement->occurred_at->format('d/m/Y').' · R$ '
                    .number_format((float) app(BankReconciliationService::class)->movementRemaining($movement), 2, ',', '.')
                    .' · '.$movement->description,
            ])->all();
    }
}
