<?php

namespace App\Filament\Pages;

use App\Services\BoletoReenvioService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

class BoletosParaReenvio extends Page
{
    protected string $view = 'filament.pages.boletos-para-reenvio';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Boletos para Reenvio';

    public array $grupos = [];

    public int $totalBoletos = 0;

    public float $valorTotal = 0.0;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:BankBoleto') ?? false;
    }

    public function mount(BoletoReenvioService $service): void
    {
        $this->grupos = $service->gruposPorCliente()->all();
        $this->totalBoletos = $service->totalBoletos();
        $this->valorTotal = $service->valorTotal();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('baixarTodos')
                ->label('Baixar todos')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->visible(fn (): bool => $this->totalBoletos > 0)
                ->action(fn (): ?StreamedResponse => $this->downloadTodos()),
        ];
    }

    public function baixarCliente(int $clientId): ?StreamedResponse
    {
        try {
            $result = app(BoletoReenvioService::class)->renderPdfCliente($clientId);

            return response()->streamDownload(
                fn () => print($result['pdf']),
                $result['filename'],
                ['Content-Type' => 'application/pdf'],
            );
        } catch (Throwable $e) {
            Notification::make()
                ->title('Não foi possível gerar os boletos')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return null;
        }
    }

    private function downloadTodos(): ?StreamedResponse
    {
        try {
            $result = app(BoletoReenvioService::class)->renderPdfTodos();

            return response()->streamDownload(
                fn () => print($result['pdf']),
                $result['filename'],
                ['Content-Type' => 'application/pdf'],
            );
        } catch (Throwable $e) {
            Notification::make()
                ->title('Não foi possível gerar os boletos')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return null;
        }
    }
}
