<?php

namespace App\Filament\Pages;

use App\Services\DreService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class DreReport extends Page
{
    protected string $view = 'filament.pages.dre-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'DRE - Demonstração de Resultados';

    public ?array $data = [];

    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill([
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim' => now()->endOfMonth()->format('Y-m-d'),
        ]);
        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->live(),

                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->live(),
            ])
            ->columns(2);
    }

    public function updatedData(): void
    {
        $this->generateReport();
    }

    public function generateReport(): void
    {
        $inicio = Carbon::parse($this->data['data_inicio'] ?? now()->startOfMonth());
        $fim = Carbon::parse($this->data['data_fim'] ?? now()->endOfMonth());

        $this->report = DreService::generate($inicio, $fim);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(fn (): StreamedResponse => $this->exportPdf()),
        ];
    }

    public function exportPdf(): StreamedResponse
    {
        $this->generateReport();
        $pdf = Pdf::loadView('pdf.dre', ['report' => $this->report]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'dre-' . now()->format('Y-m-d-His') . '.pdf'
        );
    }
}
