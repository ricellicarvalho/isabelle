<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Nfse;
use App\Models\NfseConfig;
use App\Models\NfseLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NFSeService
{
    private string $baseUrl;
    private string $apiKey;
    private string $ambiente;
    private string $certHex;
    private string $certPassword;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('nfse.base_url'), '/');
        $this->apiKey      = config('nfse.api_key');
        $this->ambiente    = config('nfse.ambiente');
        $this->certPassword = config('nfse.cert_password');
        $this->certHex     = $this->loadCertHex();
    }

    private function loadCertHex(): string
    {
        $path = base_path(config('nfse.cert_path'));

        if (! file_exists($path)) {
            throw new RuntimeException("Certificado digital não encontrado em: {$path}");
        }

        $bytes = file_get_contents($path);

        if (! openssl_pkcs12_read($bytes, $certs, $this->certPassword)) {
            throw new RuntimeException('Senha do certificado A1 inválida. Verifique NFSE_CERT_PASSWORD no .env.');
        }

        return bin2hex($bytes);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Emissão
    // ──────────────────────────────────────────────────────────────────────────

    public function emitir(NfseConfig $config, Nfse $nfse, Client $client): array
    {
        $payload = array_merge(
            $this->buildBase('emitir'),
            [
                'numeroRps'       => (int) $nfse->numero_rps,
                'numeroLote'      => (int) $nfse->numero_rps,
                'serie'           => $nfse->serie_rps,
                'simplesNacional' => (int) $config->simples_nacional,
                'prestador'       => $this->buildPrestador($config),
                'tomador'         => $this->buildTomador($client),
                'servico'         => $this->buildServico($nfse, $config),
            ]
        );

        if ($config->padrao_nacional) {
            $payload['padraoNacional'] = 'sim';
        }

        // ABRASF: regimeEspecialTributacao 5 e 6 são exclusivos de optantes do Simples Nacional.
        // Só envia quando não há contradição com simplesNacional.
        if ($config->regime_especial_tributacao) {
            $regime          = (int) $config->regime_especial_tributacao;
            $optanteSimples  = (int) $config->simples_nacional === 1;
            $regimeSimples   = in_array($regime, [5, 6]);

            if (! $regimeSimples || $optanteSimples) {
                $payload['regimeEspecialTributacao'] = $regime;
            }
        }

        return $this->post('/nfse/emitir', $payload, $nfse, 'emitir');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cancelamento
    // ──────────────────────────────────────────────────────────────────────────

    public function cancelar(NfseConfig $config, Nfse $nfse, string $motivo, string $codigo): array
    {
        $payload = array_merge(
            $this->buildBase('cancelar'),
            [
                'xml'                 => $nfse->xml,
                'codigoCancelamento'  => $codigo,
                'motivoCancelamento'  => $motivo,
                'prestador'           => $this->buildPrestador($config),
            ]
        );

        if ($config->padrao_nacional) {
            $payload['padraoNacional'] = 'sim';
        }

        return $this->post('/nfse/cancelar', $payload, $nfse, 'cancelar');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Consulta de notas
    // ──────────────────────────────────────────────────────────────────────────

    public function consultar(NfseConfig $config, string $ultimoNsu = '0'): array
    {
        $payload = [
            'certificadoDigital'     => $this->certHex,
            'senhaCertificadoDigital' => $this->certPassword,
            'padraoNacional'         => 'sim',
            'ultimoNsu'              => $ultimoNsu,
            'prestador'              => [
                'cnpj'              => $config->cnpj,
                'inscricaoMunicipal' => $config->inscricao_municipal,
                'razaoSocial'       => $config->razao_social,
                'municipio'         => $config->municipio_ibge,
            ],
        ];

        return $this->post('/nfse/consultar-notas', $payload, null, 'consultar');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Builders internos
    // ──────────────────────────────────────────────────────────────────────────

    private function buildBase(string $acao): array
    {
        return [
            'acao'                    => $acao,
            'modeloDocumento'         => 'nfse',
            'certificadoDigital'      => $this->certHex,
            'senhaCertificadoDigital' => $this->certPassword,
            'ambiente'                => (int) $this->ambiente,
        ];
    }

    private function buildPrestador(NfseConfig $config): array
    {
        $data = [
            'cnpj'               => preg_replace('/\D/', '', $config->cnpj),
            'inscricaoMunicipal' => trim($config->inscricao_municipal),
            'razaoSocial'        => trim($config->razao_social),
            'nomeFantasia'       => trim($config->nome_fantasia ?? $config->razao_social),
            'endereco'           => trim($config->endereco),
            'numero'             => trim($config->numero),
            'complemento'        => trim($config->complemento ?? ''),
            'bairro'             => trim($config->bairro),
            'municipio'          => $config->municipio_ibge,
            'nomeMunicipio'      => strtoupper(trim($config->nome_municipio)),
            'uf'                 => strtoupper(trim($config->uf)),
            'cep'                => preg_replace('/\D/', '', $config->cep),
            'email'              => trim($config->email),
            'codigoUf'           => $config->codigo_uf,
            'telefone'           => preg_replace('/\D/', '', $config->telefone ?? ''),
            'usuario'            => $config->usuario_prefeitura ?? '',
            'senha'              => $config->senha_prefeitura ?? '',
            'fraseSecreta'       => $config->frase_secreta ?? '',
        ];

        // Credenciais opcionais: envia apenas quando preenchidas
        if (! blank($config->chave_acesso)) {
            $data['chaveAcesso'] = $config->chave_acesso;
        }
        if (! blank($config->chave_autorizacao)) {
            $data['chaveAutorizacao'] = $config->chave_autorizacao;
        }

        return $data;
    }

    private function buildTomador(Client $client): array
    {
        $cpfCnpj = preg_replace('/\D/', '', $client->cnpj_cpf ?? '');

        $data = [
            'cpfCnpj'       => $cpfCnpj,
            'razaoSocial'   => trim($client->razao_social),
            'municipio'     => $client->municipio_ibge ?? '',
            'nomeMunicipio' => strtoupper(trim($client->cidade ?? '')),
            'uf'            => strtoupper(trim($client->uf ?? '')),
            'codigoPais'    => '1058',
            'pais'          => 'Brasil',
            'cep'           => preg_replace('/\D/', '', $client->cep ?? ''),
            'email'         => trim($client->email ?? ''),
            'endereco'      => trim($client->endereco ?? ''),
            'numero'        => trim($client->numero ?? ''),
            'complemento'   => trim($client->complemento ?? ''),
            'bairro'        => trim($client->bairro ?? ''),
            'telefone'      => preg_replace('/\D/', '', $client->telefone ?? ''),
        ];

        if (! blank($client->inscricao_municipal)) {
            $data['inscricaoMunicipal'] = trim($client->inscricao_municipal);
        }

        return $data;
    }

    private function buildServico(Nfse $nfse, NfseConfig $config): array
    {
        $valor     = number_format((float) $nfse->valor, 2, '.', '');
        $aliquota  = number_format((float) $nfse->aliquota, 2, '.', '');
        $valorIss  = number_format((float) ($nfse->valor_iss ?? ($nfse->valor * $nfse->aliquota / 100)), 2, '.', '');
        $issRetido = (int) $nfse->iss_retido;

        // codigoTributacaoMunicipio é diferente de itemListaServico (LC 116).
        // O padrão nacional exige exatamente 3 dígitos [0-9]{3} — definido pela prefeitura.
        // Nunca usar item_lista_servico como fallback (formato incompatível: '17.01' → 5 chars).
        $serviceCode               = \App\Models\NfseServiceCode::where('item_lista_servico', $nfse->item_lista_servico)->first();
        $codigoCnae                = $serviceCode?->codigo_cnae ?: ($config->codigo_cnae ?: '');
        $codigoTributacaoMunicipio = $serviceCode?->codigo_tributacao_municipio
            ?: ($config->codigo_tributacao_municipio ?: '');

        $servico = [
            'valor'                     => $valor,
            'deducoes'                  => '0.00',
            'aliquotaPis'               => '0.00',
            'aliquotaCofins'            => '0.00',
            'inss'                      => '0.00',
            'ir'                        => '0.00',
            'csll'                      => '0.00',
            'issRetido'                 => $issRetido,
            'valorIssRetido'            => $issRetido === 1 ? $valorIss : '0.00',
            'outrasRetencoes'           => '0.00',
            'descontoIncondicionado'    => '0.00',
            'descontoCondicionado'      => '0.00',
            'aliquota'                  => $aliquota,
            'itemListaServico'          => $serviceCode?->codigo_tributacao_nacional
                                            ?? $this->formatarItemListaServico($nfse->item_lista_servico),
            'codigoTributacaoMunicipio' => $codigoTributacaoMunicipio,
            'codigoCnae'                => $codigoCnae,
            'discriminacao'             => $nfse->discriminacao,
            'codigoMunicipio'           => $config->municipio_ibge,
            'municipioIncidencia'       => $config->municipio_ibge,
            'exigibilidadeISS'          => (int) $config->exigibilidade_iss,
        ];

        // responsavelRetencao (ABRASF: 1=Emitente, 2=Tomador, 3=Intermediário)
        // só deve ser enviado quando o ISS for retido (issRetido = 1)
        if ($issRetido === 1) {
            $servico['responsavelRetencao'] = (int) ($config->responsavel_retencao ?? 2);
            $servico['valorIssRetido']      = $valorIss;
        }

        return $servico;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HTTP
    // ──────────────────────────────────────────────────────────────────────────

    private function post(string $endpoint, array $payload, ?Nfse $nfse, string $acao): array
    {
        $safePayload = $this->maskSensitiveFields($payload);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept'        => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}{$endpoint}", $payload);

            $status = $response->status();
            $body   = $response->json() ?? [];

            $this->saveLog($nfse, $acao, $safePayload, $body, $status);

            return ['status' => $status, 'body' => $body];
        } catch (\Exception $e) {
            Log::channel(config('nfse.log_channel'))->error("NFSe {$acao} falhou", [
                'nfse_id' => $nfse?->id,
                'erro'    => $e->getMessage(),
            ]);

            $this->saveLog($nfse, $acao, $safePayload, ['error' => $e->getMessage()], 0);

            throw $e;
        }
    }

    private function saveLog(?Nfse $nfse, string $acao, array $request, array $response, int $httpStatus): void
    {
        NfseLog::create([
            'nfse_id'          => $nfse?->id,
            'acao'             => $acao,
            'request_payload'  => $request,
            'response_payload' => $response,
            'http_status'      => $httpStatus,
            'situacao'         => $response['situacao'] ?? null,
            'mensagem'         => $response['mensagem'] ?? $response['error'] ?? null,
        ]);
    }

    private function maskSensitiveFields(array $payload): array
    {
        $masked = $payload;

        if (isset($masked['certificadoDigital'])) {
            $masked['certificadoDigital'] = '[CERTIFICADO OMITIDO]';
        }
        if (isset($masked['senhaCertificadoDigital'])) {
            $masked['senhaCertificadoDigital'] = '***';
        }
        if (isset($masked['xml'])) {
            $masked['xml'] = '[XML OMITIDO]';
        }

        return $masked;
    }

    // Converte o item LC 116 com ponto para 6 dígitos numéricos exigidos pelo cTribNac.
    // Ex: '17.01' → '170100' | '4.15' → '041500' | '8.02' → '080200'
    private function formatarItemListaServico(string $item): string
    {
        if (! str_contains($item, '.')) {
            return str_pad(preg_replace('/\D/', '', $item), 6, '0', STR_PAD_RIGHT);
        }

        [$grupo, $subitem] = explode('.', $item, 2);

        return str_pad($grupo, 2, '0', STR_PAD_LEFT)
             . str_pad($subitem, 2, '0', STR_PAD_LEFT)
             . '00';
    }
}
