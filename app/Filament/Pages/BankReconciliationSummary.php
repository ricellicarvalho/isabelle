<?php

namespace App\Filament\Pages;

use App\Models\BankAccount;
use App\Services\BankReconciliationService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class BankReconciliationSummary extends Page
{
    protected string $view = 'filament.pages.bank-reconciliation-summary';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Banco × Sistema';

    public ?array $data = [];

    public array $rows = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:BankStatementEntry') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['data_inicio' => now()->startOfMonth()->toDateString(), 'data_fim' => now()->endOfMonth()->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->columns(3)->components([
            Select::make('bank_account_id')->label('Conta financeira')->options(fn () => BankAccount::orderBy('nome')->get()->pluck('display_name', 'id'))->required()->searchable(),
            DatePicker::make('data_inicio')->label('Data inicial')->required()->native(false)->displayFormat('d/m/Y'),
            DatePicker::make('data_fim')->label('Data final')->required()->afterOrEqual('data_inicio')->native(false)->displayFormat('d/m/Y'),
        ]);
    }

    public function generate(): void
    {
        Gate::authorize('ViewAny:BankStatementEntry');
        $state = $this->form->getState();
        $this->rows = app(BankReconciliationService::class)->dailyComparison(
            BankAccount::findOrFail($state['bank_account_id']), Carbon::parse($state['data_inicio']), Carbon::parse($state['data_fim']),
        );
    }
}
