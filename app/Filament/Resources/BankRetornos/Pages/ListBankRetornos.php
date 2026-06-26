<?php

namespace App\Filament\Resources\BankRetornos\Pages;

use App\Filament\Resources\BankRetornos\BankRetornoResource;
use App\Models\BankAccount;
use App\Services\CnabRetornoService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ListBankRetornos extends ListRecords
{
    protected static string $resource = BankRetornoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importarRetorno')
                ->label('Importar Retorno')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Importar arquivo de retorno CNAB')
                ->modalSubmitActionLabel('Processar')
                ->schema([
                    Select::make('bank_account_id')
                        ->label('Conta Bancária (opcional)')
                        ->options(BankAccount::query()->pluck('descricao', 'id'))
                        ->native(false),

                    FileUpload::make('arquivo')
                        ->label('Arquivo .ret / .RET')
                        ->required()
                        ->disk('local')
                        ->directory('retornos/tmp')
                        ->preserveFilenames()
                        // CNAB (.ret/.rem) não possui MIME padrão: navegadores reportam
                        // tipo vazio/octet-stream conforme o SO. Validamos por extensão
                        // no submit (abaixo) em vez de bloquear pelo MIME no FilePond.
                        ->maxSize(10240)
                        ->helperText('Arquivo CNAB 400 (.ret) enviado pelo banco.'),
                ])
                ->action(function (array $data): void {
                    $relativePath = $data['arquivo'];
                    $absolutePath = Storage::disk('local')->path($relativePath);
                    $originalName = basename($relativePath);
                    $bankAccount = filled($data['bank_account_id'] ?? null)
                        ? BankAccount::find($data['bank_account_id'])
                        : null;

                    $extensao = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if (! in_array($extensao, ['ret', 'txt'], true)) {
                        Storage::disk('local')->delete($relativePath);

                        Notification::make()
                            ->title('Tipo de arquivo inválido')
                            ->body('Envie um arquivo de retorno CNAB com extensão .ret (ou .txt).')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $retorno = app(CnabRetornoService::class)
                            ->processar($absolutePath, $originalName, $bankAccount);

                        // Remove o arquivo temporário (já foi copiado para retornos/)
                        Storage::disk('local')->delete($relativePath);

                        Notification::make()
                            ->title('Retorno processado com sucesso')
                            ->body(sprintf(
                                '%d títulos | Liquidados: %d | Baixados: %d | Erros: %d | Não encontrados: %d',
                                $retorno->quantidade_titulos,
                                $retorno->quantidade_liquidados,
                                $retorno->quantidade_baixados,
                                $retorno->quantidade_erros,
                                $retorno->quantidade_nao_encontrados,
                            ))
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Storage::disk('local')->delete($relativePath);

                        Notification::make()
                            ->title('Falha ao processar retorno')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
