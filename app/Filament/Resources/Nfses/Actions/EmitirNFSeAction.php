<?php

namespace App\Filament\Resources\Nfses\Actions;

use App\Jobs\EmitirNFSeJob;
use App\Models\Contract;
use App\Models\Nfse;
use App\Models\NfseConfig;
use App\Models\NfseServiceCode;
use App\Models\Receivable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class EmitirNFSeAction
{
    /**
     * Cria a action para emissão de NFSe a partir de um Contrato ou Parcela.
     *
     * @param  Contract|Receivable  $record
     */
    public static function make(Contract|Receivable $record): Action
    {
        $isReceivable = $record instanceof Receivable;
        $valor        = $isReceivable ? $record->valor : $record->valor_total;
        $descricao    = $isReceivable
            ? ($record->descricao ?? $record->contract?->descricao ?? '')
            : ($record->descricao ?? '');
        $tipoServico  = $isReceivable
            ? ($record->contract?->tipo_servico ?? 'outro')
            : $record->tipo_servico;

        return Action::make('emitirNfse')
            ->label('Emitir NFSe')
            ->icon('heroicon-o-document-check')
            ->color('success')
            ->visible(fn (): bool => self::podeEmitir($record))
            ->modalHeading('Emitir Nota Fiscal de Serviço Eletrônica')
            ->modalDescription('Preencha ou confirme os dados abaixo antes de emitir a NFSe.')
            ->modalWidth('lg')
            ->form([
                TextInput::make('valor')
                    ->label('Valor (R$)')
                    ->default(number_format((float) $valor, 2, ',', '.'))
                    ->disabled()
                    ->dehydrated(false),

                DatePicker::make('competencia')
                    ->label('Competência (mês/ano)')
                    ->required()
                    ->native(false)
                    ->displayFormat('m/Y')
                    ->default(now())
                    ->helperText('Mês de referência da prestação do serviço'),

                Textarea::make('discriminacao')
                    ->label('Descrição do Serviço')
                    ->required()
                    ->default($descricao)
                    ->rows(3)
                    ->maxLength(2000)
                    ->helperText('Texto que aparecerá na NFSe como discriminação do serviço'),
            ])
            ->action(function (array $data) use ($record, $valor, $tipoServico): void {
                $validacao = self::validarPreRequisitos($record);

                if ($validacao !== null) {
                    Notification::make()
                        ->title('Pré-requisito faltando')
                        ->body($validacao)
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $config      = NfseConfig::ativa();
                $serviceCode = NfseServiceCode::paraTipoServico($tipoServico);
                $aliquota    = $serviceCode?->aliquota ?? $config->aliquota_iss_padrao ?? 2.00;
                $itemLista   = $serviceCode?->item_lista_servico ?? $config->item_lista_servico ?? '17.01';
                $numeroRps   = $config->reservarNumeroRps();

                $isReceivable = $record instanceof Receivable;

                $nfse = Nfse::create([
                    'contract_id'       => $isReceivable ? $record->contract_id : $record->id,
                    'receivable_id'     => $isReceivable ? $record->id : null,
                    'numero_rps'        => $numeroRps,
                    'serie_rps'         => $config->serie_rps,
                    'tipo_rps'          => 1,
                    'status'            => 'pendente',
                    'ambiente'          => config('nfse.ambiente'),
                    'valor'             => (float) $valor,
                    'aliquota'          => (float) $aliquota,
                    'iss_retido'        => $config->iss_retido,
                    'valor_iss'         => round((float) $valor * (float) $aliquota / 100, 2),
                    'item_lista_servico' => $itemLista,
                    'discriminacao'     => $data['discriminacao'],
                    'competencia'       => $data['competencia'],
                    'created_by'        => auth()->id(),
                ]);

                EmitirNFSeJob::dispatch($nfse);

                Notification::make()
                    ->title('NFSe enviada para processamento')
                    ->body("RPS #{$numeroRps} — aguarde a confirmação do município.")
                    ->success()
                    ->send();
            });
    }

    private static function podeEmitir(Contract|Receivable $record): bool
    {
        if ($record instanceof Contract) {
            return in_array($record->status, ['ativo', 'finalizado']);
        }

        return in_array($record->status, ['pendente', 'pago', 'vencido']);
    }

    private static function validarPreRequisitos(Contract|Receivable $record): ?string
    {
        $config = NfseConfig::ativa();

        if (! $config) {
            return 'Nenhuma configuração de NFSe ativa. Acesse Configurações > NFSe Config e cadastre os dados do prestador.';
        }

        $client = $record instanceof Receivable
            ? $record->client
            : $record->client;

        if (! $client) {
            return 'Cliente não encontrado.';
        }

        if (blank($client->cnpj_cpf)) {
            return "CPF/CNPJ do cliente '{$client->razao_social}' não informado.";
        }

        if (blank($client->municipio_ibge)) {
            return "Código IBGE do município do cliente '{$client->razao_social}' não informado. Acesse o cadastro do cliente e preencha o campo 'Código IBGE'.";
        }

        if (blank($client->email)) {
            return "E-mail do cliente '{$client->razao_social}' não informado.";
        }

        if (! file_exists(base_path(config('nfse.cert_path')))) {
            return 'Certificado digital não encontrado em ' . config('nfse.cert_path');
        }

        return null;
    }
}
