# Project Context: Sistema de Gestão Isabelle (ERP NR-1)

## 1. Visão Geral

Sistema de gestão focado em consultoria psicossocial e conformidade com a norma NR-1. O sistema gerencia desde o CRM e contratos até o faturamento em lote, indicadores de riscos psicossociais e um portal dedicado para o cliente final.

## 2. Stack Tecnológica & Plugins (Obrigatórios)

- _Framework:_ Laravel 12+
- _Painel Administrativo:_ [FilamentPHP v5.x](https://filamentphp.com/docs/3.x/panels/installation)
- _Permissões:_ [Filament Shield](https://filamentphp.com/plugins/bezhansalleh-shield)
- _Prototipagem:_ [Laravel Blueprint](https://blueprint.laravelshift.com/docs/getting-started/)
- _Interface de Árvore:_ [Filament Tree](https://filamentphp.com/plugins/solution-forest-tree) (Gerenciamento de Modelos para o Plano de Contas)
- _Input Hierárquico:_ [Filament Select Tree](https://github.com/codewithdennis/filament-select-tree) (Para seleção de 'Conta Pai' no Plano de Contas)
- _Gestão de Arquivos:_ [File Manager](https://filamentphp.com/plugins/mwguerra-file-manager) (Portal do Cliente)
- _Agenda:_ [FullCalendar](https://filamentphp.com/plugins/saade-fullcalendar) (Eventos e Ações)
- _Banco de Dados:_ MySQL

## 3. Estrutura de Entidades (Blueprint Models)

- Clientes (Empresas): Gestão completa de dados cadastrais (CNPJ, Razão Social), endereços e contatos.
- Contratos: Registro de serviços (NR-1, Palestras), controle de vigência e geração de PDF.
- Gestão Bancária (Boletos e Remessas):
    -   1. **BankBoleto:** `receivable_id` (FK), `nosso_numero` (unique), `numero_documento`, `carteira`, `codigo_barras`, `linha_digitavel`, `data_vencimento`, `valor`, `status` (enum), `remessa_id` (FK, nullable).
    -   2. **BankRemessa:** `sequencial_arquivo`, `data_geracao`, `caminho_arquivo`, `quantidade_titulos`, `valor_total`.
- Eventos/Agenda: Vinculado a client_id (obrigatório) e contract_id (opcional)
- Plano de Contas: Árvore hierárquica para categorização financeira (Receitas, Custos, Despesas). _Colunas:_ id (BigInt), parent_id (BigInt, nullable), codigo (string), descricao (string), order (integer).
- Financeiro: Contas a pagar/receber, fluxo de caixa e geração de DRE mensal/anual.
- DRE: Relatório automatizado de lucro/prejuízo baseado no Plano de Contas.
- Precificação: Calculadora de margem de lucro por ação/serviço.
- Portal do Cliente: Área restrita para visualização de relatórios, fotos e matriz de riscos.
- Boletos: Gerencia os boletos gerados no Contas a Receber

### Vínculos de Categoria (category_id - FK obrigatória)

- _Contratos_: Deve vincular a uma categoria do Plano de Contas na criação.
- _Contas a Receber_: FK obrigatória (Origem das Receitas).
- _Contas a Pagar_: FK obrigatória (Origem de Custos/Despesas no DRE).
- _Precificação_: FK obrigatória para natureza do custo/serviço orçado.

## 4. Regras de Negócio (RN)

- **RN01 - Geração Automática:** Ao definir as parcelas no Contrato, o sistema deve gerar automaticamente os registros correspondentes no 'Contas a Receber'.
- **RN02 - Fluxo de Boletos:** Se a forma de pagamento for 'Boleto', o sistema deve gerar o arquivo para o cliente e permitir a geração de 'Arquivo de Remessa' em lote para o banco.
- **RN03 - Hierarquia Contábil:** O Plano de Contas deve seguir o padrão (Ex: 1 - Despesas, 1.1 - Energia). O uso de 'Select Tree' é obrigatório para evitar erros de hierarquia no cadastro.
- **RN04 - Faturamento em Lote:** Action em massa na listagem de Contratos para faturar múltiplos itens simultaneamente.
- **RN05 - Quitação em Lote:** Ações de 'Bulk Update' para marcar múltiplas contas (Pagar/Receber) como quitadas.
- **RN06 - Privacidade e Segurança:** O perfil 'Operacional' é estritamente proibido de visualizar dados Financeiros ou DRE. Permissões controladas via Shield.
- **RN07 - Regularização NR-1:** O status 'Regularizada' de uma empresa é condicional à conclusão de 100% dos itens do Checklist (Avaliação, Devolutiva, Plano, Treinamento, Relatório).
- **RN08 - Alertas:** Notificações automáticas de vencimento de contratos (30, 15 e 7 dias).
- **RN09 - Eventos e Agenda:** A criação de um Evento de "Avaliação NR-1" deve permitir o bloqueio de data na agenda do colaborador responsável
- **RN10 - Ciclo de Vida Financeiro:**
    1. Gatilho de Geração Financeira: A criação ou ativação de um Contrato deve gerar automaticamente as parcelas em `receivables`. O sistema não deve permitir um contrato ativo sem seu respectivo espelho financeiro.
    2. Estorno por Cancelamento: Caso o status do Contrato seja alterado para "Cancelado", o sistema deve localizar automaticamente todas as parcelas de receivables vinculadas a ele que ainda estejam com status "Pendente" e alterá-las para "Cancelado".
    3. Bloqueio de Exclusão: Não deve ser permitida a exclusão de um Contrato que possua parcelas financeiras já marcadas como "Pago" ou "Recebido", forçando o usuário a realizar um estorno manual ou manter o histórico. Ou seja, parcelas já 'Pagas' não são alteradas automaticamente em caso de cancelamento, exigindo intervenção manual.
- **RN11 - Vínculo com Recebível:** Um boleto não pode existir isoladamente; ele deve estar obrigatoriamente atrelado a uma parcela de "Contas a Receber".
- **RN12 - Geração Única de Nosso Número:** O sistema deve garantir que o campo nosso_numero seja gerado seguindo as regras da carteira do banco e nunca se repita, evitando duplicidade no registro bancário.
- **RN13 - Agrupamento em Remessa (Bulk Action):** Na listagem de Boletos, deve haver uma Bulk Action chamada "Gerar Arquivo de Remessa". Esta ação deve:
-   1. Selecionar apenas boletos com status 'pendente'.
-   2. Agrupar os dados no formato CNAB exigido.
-   3. Atualizar o status dos boletos para 'emitido'.
-   4. Registrar o vínculo com a entidade BankRemessa.
- **RN14 - Regra de Cancelamento:** Se um Contrato for cancelado ou uma conta a receber for excluída, o boleto correspondente deve ser marcado como 'cancelado' e incluído na próxima remessa com a instrução de "Baixa de Título".
- **RN15 - Padronização de Layout:** O sistema deve suportar a configuração de layouts CNAB 240 ou 400, dependendo da homologação com a agência bancária da empresa.
- **RN16 - Automatização de Valores:** O valor e a data de vencimento do boleto devem ser herdados automaticamente da parcela do Contas a Receber, mas podem permitir edição manual (com log de auditoria) antes da emissão da remessa.

## 5. Implementação no Filament (UX/UI)

- **ReceivableResource:** Deve conter uma `Action` personalizada para "Gerar Boleto", criando o registro em `BankBoleto`.
- **BankRemessaResource:** Menu dedicado para gerenciar os lotes de arquivos enviados ao banco.
- **Portal do Cliente:** Utilizar o plugin `File Manager` para upload/download de laudos e fotos.

## 6. Definição de Pronto (DoD)

- Código seguindo os padrões PSR-12.
- Migrations geradas via Blueprint.
- Relatórios (DRE e Precificação) exportáveis em PDF/Excel
- Políticas de acesso (Shield) implementadas para cada recurso.
- Interface utilizando componentes nativos do Filament para manter a consistência.
- Relatórios exportáveis em PDF/Excel quando solicitado.

## 7. Documentação de Referência

- Filament: https://filamentphp.com/docs/5.x/introduction/overview (documentação Oficial do Filamentphp).
- Filament Demo: https://demo.filamentphp.com/ (Email address: admin@filamentphp.com Password: demo.Filament@2021!).
- Filament Shield: https://filamentphp.com/plugins/bezhansalleh-shield (deve ser utilizado na criação das Permissões/Controle de Acesso).
- Blueprint: https://blueprint.laravelshift.com/docs/getting-started/
- Filament-tree: https://filamentphp.com/plugins/solution-forest-tree (Deve ser utilizado para criar a hierárquia do Plano de Contas).
- File Manager: https://filamentphp.com/plugins/mwguerra-file-manager (Deve ser utilizado no módulo Portal do cliente para disponibilizar arquivos).
- FullCalendar: https://filamentphp.com/plugins/saade-fullcalendar (Deve ser utilizado na criação do Evento/Agenda).
