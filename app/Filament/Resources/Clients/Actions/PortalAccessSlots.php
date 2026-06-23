<?php

namespace App\Filament\Resources\Clients\Actions;

/**
 * Configuração compartilhada pelas Actions de acesso ao portal
 * (GeneratePortalAccess, ResetPortalPassword, RevokePortalAccess),
 * uma para cada credencial que um Client pode ter: documentação e financeiro.
 */
class PortalAccessSlots
{
    /**
     * @return array{tipo: string, email_field: string, fk: string, campo_label: string, item_gerar: string, item_resetar: string, item_revogar: string, label_gerar: string, label_resetar: string, label_revogar: string, descricao_pessoa: string, sufixo_nome: string}
     */
    public static function get(string $tipo): array
    {
        return match ($tipo) {
            'financeiro' => [
                'tipo'             => 'financeiro',
                'email_field'      => 'contato_financeiro_email',
                'fk'               => 'portal_financeiro_user_id',
                'campo_label'      => 'E-mail (Responsável pelo Financeiro)',
                'item_gerar'       => 'Gerar Acesso',
                'item_resetar'     => 'Resetar Senha',
                'item_revogar'     => 'Revogar Acesso',
                'label_gerar'      => 'Gerar Acesso Financeiro',
                'label_resetar'    => 'Resetar Senha do Acesso Financeiro',
                'label_revogar'    => 'Revogar Acesso Financeiro',
                'descricao_pessoa' => 'responsável financeiro acessar Boletos e Notas Fiscais no portal',
                'sufixo_nome'      => 'Financeiro',
            ],
            default => [
                'tipo'             => 'documentacao',
                'email_field'      => 'email',
                'fk'               => 'portal_user_id',
                'campo_label'      => 'E-mail',
                'item_gerar'       => 'Gerar Acesso',
                'item_resetar'     => 'Resetar Senha',
                'item_revogar'     => 'Revogar Acesso',
                'label_gerar'      => 'Gerar Acesso ao Portal',
                'label_resetar'    => 'Resetar Senha do Portal',
                'label_revogar'    => 'Revogar Acesso ao Portal',
                'descricao_pessoa' => 'cliente acessar o portal',
                'sufixo_nome'      => 'Documentação',
            ],
        };
    }
}
