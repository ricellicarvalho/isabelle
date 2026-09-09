<?php

namespace App\Filament\Pages;

use App\Exports\BankAccountReportExport;
use App\Models\BankAccount;
use App\Models\Category;
use App\Services\BankAccountReportService;
use App\Services\BankMovementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Url as UrlAttribute;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

class BankAccountReport extends Page
{
    protected string $view = 'filament.pages.bank-account-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Movimentação de Conta';

    #[UrlAttribute]
    public ?int $account = null;

    public ?array $data = [];

    public ?array $report = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:BankAccountReport') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['bank_account_id' => $this->account, 'data_inicio' => now()->startOfMonth()->toDateString(), 'data_fim' => now()->endOfMonth()->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->columns(3)->components([
            Select::make('bank_account_id')->label('Conta financeira')->options(fn () => BankAccount::orderBy('nome')->get()->pluck('display_name', 'id'))->required()->searchable(),
            DatePicker::make('data_inicio')->label('Data inicial')->minDate(BankMovementService::CONTROL_START)->required()->native(false)->displayFormat('d/m/Y'),
            DatePicker::make('data_fim')->label('Data final')->afterOrEqual('data_inicio')->required()->native(false)->displayFormat('d/m/Y'),
            Select::make('reconciliation')->label('Conciliação')->options(['pending' => 'Pendente', 'reconciled' => 'Conciliado'])->placeholder('Todas'),
            Select::make('category_id')->label('Categoria')->options(fn () => Category::orderBy('descricao')->pluck('descricao', 'id'))->searchable()->placeholder('Todas'),
            TextInput::make('search')->label('Favorecido / Documento')->maxLength(255),
        ]);
    }

    public function generateReport(): void
    {
        Gate::authorize('View:BankAccountReport');
        $this->report = BankAccountReportService::generate($this->form->getState());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')->label('Abrir PDF')->icon('heroicon-o-document-text')
                ->disabled(fn () => ! $this->report)
                ->url(fn () => $this->report ? URL::temporarySignedRoute('reports.bank-account.pdf', now()->addMinutes(30), array_filter($this->report['filters'], fn ($value) => filled($value))) : null)->openUrlInNewTab(),
            Action::make('excel')->label('Exportar Excel')->icon('heroicon-o-table-cells')->disabled(fn () => ! $this->report)->action(fn () => $this->exportExcel()),
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('View:BankAccountReport');
        // Regenerate from the same filters used for the displayed report.
        $report = BankAccountReportService::generate($this->report['filters'] ?? $this->form->getState());

        return Excel::download(new BankAccountReportExport($report), 'movimentacao-conta-'.$report['account']['id'].'.xlsx');
    }
}
