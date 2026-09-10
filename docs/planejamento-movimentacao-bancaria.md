# Planejamento do módulo de Movimentação Bancária

**Sistema:** Instituto Alves Neves  
**Data:** 9 de setembro de 2026
**Objetivo:** incorporar contas financeiras, movimentações, saldos e conciliação bancária sem invalidar o histórico financeiro já existente.

> **Documento de continuidade (handoff):** este arquivo registra tanto o planejamento quanto o estado real da implementação, atualizado em 09/09/2026 (ver também as seções 16 e 17). Em outra máquina, ler primeiro as seções 0, 7, 9, 11, 13 e 14 antes de alterar código ou banco.

## 0. Estado atual da implementação

**Etapa atual:** Etapas 2 e 3 da seção 14 implementadas e testadas localmente; próxima etapa de desenvolvimento é a **Etapa 4 — conciliação avançada**. A migração da Etapa 2 e os seeders ainda não foram aplicados ao banco operacional nesta continuidade. A conciliação OFX básica foi antecipada da Fase 3, mas ainda não é a conciliação avançada prevista neste documento.

### Decisões confirmadas pelo usuário

- início oficial do controle bancário: **01/08/2026**;
- o histórico de julho/2026 não precisa ser reconstruído nem gerar retroativamente o relatório do PDF;
- saldo inicial é o saldo final de **31/07/2026**, para formar o saldo de abertura de 01/08/2026;
- Bradesco: **R$ 80,01**;
- Sicoob: **R$ 4.221,34**;
- somente o Bradesco será utilizado para emissão de boletos;
- contas pagas/recebidas desde o corte serão revisadas pelo usuário, que informará a conta correta; não deve haver associação automática em massa.

### O que já foi implementado

- evolução do cadastro existente `bank_accounts`, mantendo em uma única entidade a conta financeira e as configurações opcionais de cobrança;
- campos de nome, tipo, data/saldo inicial, limite de crédito, uso para boletos e conta padrão de cobrança;
- campos antigos de carteira, cedente e layout tornados opcionais no banco e condicionais no formulário;
- Bradesco preservado como conta padrão para boletos;
- Sicoob Movimento cadastrado com banco 756, agência 5004 e conta 1.161.429-3;
- saldos iniciais gravados em 31/07/2026: Bradesco R$ 80,01 e Sicoob R$ 4.221,34;
- `bank_account_id` opcional em contas a pagar e receber, exigido na interface quando um título é salvo como pago a partir de 01/08/2026;
- criação idempotente de baixa e movimentação ao salvar títulos pagos após o corte;
- baixas em lote de contas a pagar, receber e parcelas de contratos agora pedem conta e data e geram movimentação;
- retorno CNAB associa a conta e sincroniza a movimentação do recebível;
- livro de movimentações com créditos, débitos, lançamentos avulsos, categorias, referências e favorecidos;
- transferência entre contas gravada atomicamente como débito na origem e crédito no destino, ligados por UUID;
- cálculo do saldo atual: saldo inicial + créditos confirmados − débitos confirmados;
- importação OFX por conta, com hash do arquivo e FITID para impedir duplicação;
- conciliação básica 1:1 por conta, direção e valor exato;
- possibilidade de criar um lançamento a partir de uma linha OFX e conciliá-lo;
- possibilidade de arquivar uma linha importada pendente;
- recursos, telas e permissões iniciais no Filament;
- testes de corte, idempotência da baixa, transferência e importação OFX.

### Onde operar o que já existe

- **Contas bancárias:** Financeiro → Contas bancárias (`/bank-accounts`);
- **Importar OFX:** abrir a edição da conta correta e usar a ação **Importar OFX** no cabeçalho;
- **Movimentações:** Financeiro → Movimentações Bancárias (`/bank-movements`);
- **Conciliação:** Financeiro → Conciliação Bancária (`/bank-statement-entries`);
- **Conta do título:** formulários de Contas a Pagar (`/payables`) e Contas a Receber (`/receivables`).

### Limites da entrega atual

- baixas parciais/múltiplas, ajustes e estorno têm ações explícitas; os relatórios específicos antigos de pagamentos/recebimentos ainda usam resumos dos títulos, mas fluxo de caixa, dashboard e DRE já usam o livro;
- a conciliação atual é somente 1:1 e por valor exato;
- ainda não há desfazer conciliação nem fechamento mensal; o estorno auditado está disponível somente para baixas não conciliadas e usuários autorizados;
- relatório de movimentação de conta disponível em tela, PDF e XLSX, aguardando homologação com extratos reais;
- fluxo de caixa, dashboard e DRE leem o livro bancário após o corte; títulos pagos ainda sem baixa não entram nesses totais e precisam ser revisados;
- saldos não estarão conciliados até o usuário classificar os títulos desde 01/08/2026 e importar os OFX correspondentes.

## 1. Decisão recomendada

O sistema deve separar quatro conceitos:

1. **Plano de contas (categoria):** explica *por que* o dinheiro entrou ou saiu — receita de consultoria, aluguel, tarifa bancária etc.
2. **Conta financeira:** explica *onde* o dinheiro está — Sicoob, Bradesco, caixa, aplicação etc.
3. **Título financeiro:** representa o compromisso ou direito — conta a pagar ou conta a receber, ainda que esteja em aberto.
4. **Movimentação/baixa:** representa o dinheiro efetivamente debitado ou creditado. A conciliação liga essa movimentação à linha real do extrato bancário.

Portanto, não se recomenda simplesmente adicionar uma coluna obrigatória `bank_account_id` em `payables` e `receivables`. Um título pode ser pago parcialmente, em datas diferentes e até por contas diferentes; um depósito pode quitar vários títulos. É necessária uma entidade de **baixas/movimentações** entre os títulos e as contas.

## 2. O que já existe no sistema

Antes desta implementação, o sistema já possuía `bank_accounts` e telas de “Contas bancárias”, mas o cadastro era desenhado principalmente para cobrança bancária: carteira, convênio, nosso número, sequencial de remessa, CNAB e dados do cedente. A primeira entrega descrita na seção 0 já acrescentou tipo da conta, data/saldo inicial e o livro de movimentações.

Também já há:

- contas a pagar e receber com status, vencimento, data de pagamento, valor pago e forma de pagamento;
- baixa automática de recebíveis por retorno CNAB;
- fluxo de caixa calculado diretamente a partir dos títulos pagos;
- uma conta Bradesco cadastrada localmente;
- 49 contas a pagar, das quais 22 pagas (01/07/2026 a 14/08/2026);
- 300 contas a receber, das quais 186 pagas (06/03/2025 a 21/08/2026).

O PDF fornecido, porém, é da conta **Sicoob 5004 / 1.161.429-3** e mostra também uma transferência para Bradesco. Logo, pelo menos Sicoob e Bradesco devem existir como contas financeiras distintas.

## 3. Leitura do PDF de exemplo

O relatório contém:

- conta, agência e limite no cabeçalho;
- saldo anterior;
- data e hora de cada movimento;
- número/referência;
- débito ou crédito;
- histórico, favorecido e classificação;
- subtotais de débito/crédito, variação do período, saldo final e saldo com limite.

### Campo “Número”

É, com alta probabilidade, a referência do documento ou do lançamento no sistema de origem. Os valores do PDF sustentam essa leitura:

- `91-11/12`, `32-12/12` e `6290-7/10` parecem número do título seguido da parcela;
- `NF-17-10/12` incorpora uma referência de nota fiscal e parcela;
- números simples como `6462`, `6478` e `6480` parecem identificadores sequenciais internos.

No novo módulo, esse campo deve ser chamado **Documento/Referência** e pode ser preenchido pelo número do título, parcela, NF, boleto/nosso número ou identificador bancário. Não deve ser a chave primária do banco de dados.

### Campo “Sub-PC”

Não foi localizada documentação pública do sistema que gerou o PDF; portanto, a interpretação é uma **inferência**, não uma definição confirmada pelo fornecedor. “Sub-PC” muito provavelmente significa **Subconta do Plano de Contas**. O comportamento dos códigos reforça isso: `001.001` aparece repetidamente nos recebimentos; `012.072` em tarifas/pacotes bancários; outros códigos agrupam fornecedores ou tipos de despesa.

No Isabelle, isso corresponde funcionalmente à **Categoria (Plano de Contas)** já existente. Não é necessário criar um campo “Sub-PC”. Se for importante preservar códigos do sistema anterior, pode-se guardar `codigo_externo` na categoria ou na importação.

## 4. Referência de funcionamento no mercado

A Conta Azul separa “Contas financeiras”, “Conciliações pendentes” e “Movimentações”. Na conciliação, o extrato bancário fica de um lado e os lançamentos do ERP do outro. Após conciliar, a tela apresenta os lançamentos baixados daquela conta e compara saldo do banco com saldo do ERP. [Conta Azul — telas da conciliação](https://ajuda.contaazul.com/hc/pt-br/articles/44909030747405-Concilia%C3%A7%C3%A3o-quais-s%C3%A3o-as-principais-telas-da-concilia%C3%A7%C3%A3o)

O mercado também trata:

- correspondência 1:1, 1:N e N:1 entre extrato e títulos;
- busca/sugestão por conta, favorecido, data e valor aproximados;
- baixa parcial, juros, multa, desconto e tarifa;
- transferência entre contas sem classificá-la como receita/despesa;
- importação OFX e arquivamento reversível de linhas que não devem ser conciliadas.

A Conta Azul documenta sugestões com data e valor próximos, favorecido e preferência por títulos da mesma conta; também admite um movimento bancário para vários títulos e vários movimentos para um título. [Sugestões de conciliação](https://ajuda.contaazul.com/hc/pt-br/articles/44909103864461-Concilia%C3%A7%C3%A3o-como-funciona-a-sugest%C3%A3o-da-coluna-Lan%C3%A7amentos-da-Conta-Azul) Para diferenças, oferece baixa parcial, juros, multa, desconto e tarifa, exigindo diferença zero antes de concluir. [Revisar valores](https://ajuda.contaazul.com/hc/pt-br/articles/31662131116429)

Transferências precisam gerar saída na origem e entrada no destino, ligadas entre si, sem afetar receitas/despesas ou DRE. As datas podem diferir em operações D+1. [Conta Azul — transferências](https://ajuda.contaazul.com/hc/pt-br/articles/7454447472653-Concilia%C3%A7%C3%A3o-como-conciliar-transfer%C3%AAncia-entre-contas)

## 5. Modelo funcional proposto

### 5.1 Contas financeiras

Evoluir `bank_accounts` em vez de duplicá-la. Novos campos:

- `nome`: “Sicoob Movimento”, “Bradesco Cobrança”;
- `tipo`: conta corrente, poupança, caixa, investimento ou outra;
- `moeda`, inicialmente BRL;
- `saldo_inicial` e `data_saldo_inicial`;
- `limite_credito`, separado do saldo real;
- `permite_movimentacao` e `conta_padrao`;
- dados bancários atuais;
- configurações CNAB opcionais, visíveis apenas quando usadas para cobrança;
- ativa/inativa, sem apagar histórico.

O saldo contábil da conta nunca deve ser digitado e sobrescrito diretamente. Deve ser calculado como:

`saldo na data = saldo inicial + créditos confirmados − débitos confirmados até a data`

O “saldo disponível” pode ser exibido separadamente como `saldo contábil + limite`, reproduzindo “Saldo com limite” do PDF.

### 5.2 Baixas financeiras

Criar `financial_settlements` (baixas):

- conta financeira;
- título de origem (`payable` ou `receivable`);
- data efetiva;
- valor;
- forma de pagamento;
- referência/documento;
- observação e auditoria;
- situação: prevista, confirmada, estornada;
- origem: manual, CNAB, conciliação ou migração.

Um título pode ter várias baixas. Seu saldo em aberto será `valor total − soma das baixas confirmadas`. Os estados passam a ser calculados: em aberto, vencido, parcialmente pago/recebido, pago/recebido, cancelado. Os campos atuais `data_pagamento` e `valor_pago` podem ser mantidos durante a transição como resumo compatível, mas a baixa será a fonte definitiva.

### 5.3 Livro de movimentações

Criar `bank_movements`:

- conta, data/hora e tipo (crédito/débito);
- valor sempre positivo, com direção separada;
- descrição, favorecido e documento/referência;
- categoria opcional;
- tipo de origem: baixa, transferência, saldo inicial, ajuste, tarifa, rendimento etc.;
- identificador do agrupamento de transferência;
- estado: previsto, confirmado, estornado;
- criado por, alterado por e timestamps.

Toda baixa confirmada gera uma movimentação. Uma transferência gera atomicamente duas movimentações ligadas: débito na origem e crédito no destino. Estorno não apaga: cria reversão ou muda o estado mantendo auditoria.

### 5.4 Extrato importado e conciliação

Não misturar a linha do banco com a movimentação interna. Criar:

- `bank_statement_imports`: arquivo, conta, período, hash e usuário;
- `bank_statement_entries`: FITID/identificador, data, valor, memo, favorecido, saldo informado e estado;
- `bank_reconciliations` e `bank_reconciliation_allocations`: vínculos e valores conciliados entre linhas do banco e movimentos/baixas.

Isso permite 1:1, 1:N, N:1, conciliação parcial, desfazer conciliação e impedir OFX duplicado por conta + FITID/hash.

## 6. Fluxos de usuário

### Conta a pagar/receber em aberto

No cadastro do título, **Conta prevista** deve ser opcional. Ela indica de onde se pretende pagar ou onde se pretende receber, mas não altera saldo.

### Dar baixa

Em vez de alterar livremente o status para “Pago”, usar a ação **Dar baixa**:

1. informar conta financeira obrigatória;
2. data efetiva, valor e forma;
3. informar juros, multa, desconto ou tarifa, quando houver;
4. confirmar; a operação cria baixa e movimentação em transação de banco de dados;
5. se restar valor, título fica parcial; se zerar, fica pago/recebido.

As ações rápidas atuais que marcam como pago precisam abrir esse formulário. O retorno CNAB deve criar a baixa na conta associada ao arquivo, não apenas atualizar o status do recebível.

### Lançamento avulso

Permitir crédito/débito sem título apenas para eventos realmente bancários: tarifa, rendimento, aporte, retirada, imposto debitado diretamente ou ajuste identificado. Categoria é obrigatória, exceto saldo inicial e transferência.

### Transferência

Selecionar origem, destino, valor, datas e referência. O serviço grava as duas pontas atomicamente. Transferência não entra na DRE; aparece apenas nos extratos e saldos das contas.

### Conciliação

1. importar OFX da conta;
2. rejeitar duplicidades;
3. sugerir títulos/movimentos por direção, conta, valor, data, documento e favorecido;
4. usuário confirma, procura outro lançamento, cria um lançamento ou arquiva a linha;
5. permitir agrupamentos e parciais;
6. concluir somente quando o valor alocado fechar;
7. mostrar diariamente **Saldo banco × Saldo sistema × Diferença**.

## 7. Implantação com o sistema já em funcionamento

Sim, é necessário lançar um saldo inicial — **um por conta financeira** — mas ele deve representar o saldo real de uma data de corte, e não ser usado para esconder divergências.

### Estratégia confirmada

Foi confirmado **1º de agosto de 2026** como início do controle bancário:

1. cadastrar todas as contas reais, especialmente Sicoob e Bradesco;
2. usar o saldo final de **31/07/2026** de cada conta;
3. registrar esse valor e data como saldo inicial;
4. importar/lançar todas as movimentações a partir de **01/08/2026**;
5. associar conta e criar baixas para pagamentos/recebimentos realizados desde o corte;
6. conciliar cada linha até o saldo diário coincidir;
7. bloquear a edição livre do saldo inicial após a primeira conciliação, liberando correção apenas com permissão e trilha de auditoria.

Essa regra segue a prática documentada pela Conta Azul: se os lançamentos começam em determinada data, utiliza-se o saldo final do dia anterior. [Conta Azul — saldo inicial](https://ajuda.contaazul.com/hc/pt-br/articles/11481434351245-Contas-financeiras-Outras-contas-saldo-inicial-na-cria%C3%A7%C3%A3o-da-conta) O saldo inicial também é reconhecido como elemento típico de implantação/migração no módulo contábil da Omie. [Omie — saldo inicial](https://ajuda.omie.com.br/pt-BR/articles/15266096-lancando-o-saldo-inicial-no-modulo-contabil)

### Tratamento do histórico existente

- Títulos pagos antes de 01/08/2026 permanecem válidos para DRE e relatórios históricos e ficam sem baixa bancária.
- Títulos em aberto não geram saldo; podem receber “conta prevista” opcionalmente.
- Nenhuma conta histórica deve ser atribuída automaticamente só porque existe um único cadastro Bradesco: o PDF prova movimentação em Sicoob e transferências entre bancos.
- Não será emitido retroativamente o relatório de julho igual ao PDF. Julho está absorvido pelo saldo inicial de 31/07/2026.

O saldo inicial não deve somar novamente títulos antigos. Ele já incorpora toda a vida financeira anterior ao corte. Incluir o saldo de 31/07 e também gerar movimentos para pagamentos anteriores a 01/08 causaria duplicidade.

## 8. Relatório “Movimentação de Conta”

Filtros: conta obrigatória, período, situação de conciliação, categoria e busca por favorecido/documento.

Colunas recomendadas:

- Lançamento (data/hora);
- Documento/Referência (equivalente ao “Número”);
- Débito;
- Crédito;
- Histórico;
- Favorecido/Cliente;
- Categoria (substitui “Sub-PC”);
- Situação da conciliação;
- saldo acumulado.

Cabeçalho: empresa, conta, agência, período e limite. Rodapé: saldo anterior, total de débitos, total de créditos, movimento líquido, saldo final e saldo disponível com limite. Exportações em PDF e XLSX.

## 9. Fases de implementação

### Fase 1 — fundação e migração segura

- evoluir contas bancárias para contas financeiras;
- criar saldos iniciais, baixas e movimentos;
- adicionar conta prevista opcional aos títulos;
- criar serviços transacionais de baixa, estorno e transferência;
- converter o retorno CNAB para gerar baixa;
- preservar compatibilidade com campos/status atuais;
- testes de saldo, parcial, estorno, transferência, CNAB e concorrência.

### Fase 2 — operação e relatórios

- ações “Dar baixa” e “Estornar baixa”;
- extrato por conta, saldos diário/atual/disponível;
- relatório PDF semelhante ao fornecido;
- filtros por conta em contas a pagar/receber, fluxo de caixa e dashboard;
- permissões separadas para visualizar, lançar, baixar, transferir, estornar e alterar saldo inicial.

### Fase 3 — OFX e conciliação

- importação idempotente de OFX;
- tela Banco × Sistema;
- sugestões, conciliação 1:1, 1:N, N:1 e parcial;
- ajustes de juros, multa, desconto e tarifa;
- desfazer/arquivar com auditoria;
- indicador de diferença por dia.

### Fase 4 — automação opcional

- regras aprendidas por favorecido/histórico;
- conciliação em lote com confirmação;
- integração Open Finance, se houver provedor, contrato e requisitos de segurança/LGPD;
- fechamento mensal da conta para impedir alterações retroativas sem reabertura.

## 10. Regras de integridade indispensáveis

- valores monetários em `decimal`, nunca `float`;
- baixa/estorno/transferência em transações de banco de dados e com bloqueio contra duplo clique/concorrência;
- não permitir baixa superior ao saldo aberto sem fluxo explícito de crédito/adiantamento;
- não apagar movimentos conciliados; exigir desfazer conciliação/estorno;
- conta inativa não aceita novos movimentos, mas mantém consulta;
- transferências não afetam DRE nem total consolidado de caixa, apenas redistribuem saldos;
- saldo inicial tem data, autor, justificativa e histórico de alterações;
- conciliação exige soma das alocações igual à linha do banco;
- importações OFX são idempotentes;
- relatórios de caixa passam a ler movimentos confirmados; DRE por caixa lê baixas vinculadas a categorias, preservando o comportamento histórico no período legado.

## 11. Critérios de aceite

O módulo estará pronto para produção quando:

1. cada conta mostrar saldo inicial, entradas, saídas, saldo atual e disponível reproduzíveis;
2. toda nova baixa exigir conta financeira;
3. baixa parcial e agrupada não perder saldo em aberto;
4. transferências fecharem nas duas contas sem afetar a DRE;
5. retorno CNAB criar recebimento na conta correta sem duplicar baixa;
6. OFX reimportado não duplicar linhas;
7. conciliação puder ser desfeita com trilha completa;
8. o saldo de cada dia bater com o extrato oficial após conciliação;
9. dados anteriores ao corte continuarem produzindo os relatórios atuais;
10. o novo PDF reproduzir os totais e saldo do extrato de referência.

## 12. Estimativa original de execução

Uma implementação segura deve ser entregue incrementalmente. Estimativa técnica inicial:

- Fase 1: 5–8 dias úteis;
- Fase 2: 4–6 dias úteis;
- Fase 3: 7–12 dias úteis;
- Fase 4: projeto separado, dependente do provedor bancário.

Contas, saldos, data de corte e ausência de reconstrução de julho já foram confirmados. Ainda precisam ser confirmadas as regras de autorização para estorno, reabertura e alteração de saldo inicial.

## 13. Inventário técnico para continuidade

### Banco de dados

A migração principal é `database/migrations/2026_09_04_000001_create_bank_movement_module.php`. Ela cria ou altera `bank_accounts`, `payables.bank_account_id`, `receivables.bank_account_id`, `financial_settlements`, `bank_movements`, `bank_statement_imports`, `bank_statement_entries` e `bank_reconciliation_allocations`.

A migração contém verificações `Schema::hasColumn()` e `Schema::hasTable()` porque o MySQL confirma DDL imediatamente. Assim ela pode ser retomada se falhar entre alterações. Em uma instalação nova, deve ser executada normalmente com `php artisan migrate --force` somente depois de backup.

### Models e serviços adicionados

- `app/Models/FinancialSettlement.php`;
- `app/Models/BankMovement.php`;
- `app/Models/BankStatementImport.php`;
- `app/Models/BankStatementEntry.php`;
- `app/Models/BankReconciliationAllocation.php`;
- `app/Services/BankMovementService.php`;
- `app/Services/OfxImportService.php`;
- `app/Services/BankReconciliationService.php`.

O corte está centralizado em `BankMovementService::CONTROL_START`, com valor `2026-08-01`. O método `syncLegacyPaid()` é idempotente e mantém compatibilidade com os campos atuais de pagamento dos títulos.

### Recursos Filament adicionados ou alterados

- `app/Filament/Resources/BankMovements/`;
- `app/Filament/Resources/BankStatementEntries/`;
- formulário, tabela e edição de `BankAccounts`;
- formulários, páginas e tabelas de `Payables` e `Receivables`;
- baixa em lote em `Contracts/RelationManagers/ReceivablesRelationManager.php`.

Também foram alterados `BankAccount`, `Payable`, `Receivable`, `CnabRetornoService`, `RoleSeeder` e adicionadas as policies de movimentação e conciliação.

### Testes e resultado conhecido

Arquivo novo: `tests/Feature/BankMovementModuleTest.php`.

Testes focados executados em banco isolado: **12 aprovados, 47 assertions**. A suíte completa teve **31 testes aprovados e 1 falha antiga**: `tests/Feature/ExampleTest.php` espera HTTP 200 em `/`, mas a aplicação redireciona para login com HTTP 302. Essa falha não foi causada pelo módulo bancário.

Para testar em Docker, nunca executar `migrate:fresh` apontando para `isabelle_db`. Criar ou usar um banco exclusivo e informar explicitamente:

```bash
docker exec isabelle_app sh -lc 'cd /var/www && DB_DATABASE=isabelle_test php artisan migrate:fresh --force'
docker exec isabelle_app sh -lc 'cd /var/www && DB_DATABASE=isabelle_test php artisan test'
```

### Estado do ambiente local em 04/09/2026

A migração foi aplicada localmente e as permissões foram semeadas. Após conferência, o banco operacional continha 34 usuários, 47 clientes, 51 registros brutos em contas a pagar, 320 registros brutos em contas a receber e 18 categorias. Existiam duas contas bancárias, com os saldos iniciais informados acima. Nenhuma baixa histórica foi criada automaticamente: `financial_settlements` e `bank_movements` estavam inicialmente vazias, aguardando a classificação pelo usuário.

Houve durante o desenvolvimento uma execução acidental de `migrate:fresh` contra o banco local. O banco foi recuperado por point-in-time recovery dos binlogs do MySQL e as contagens acima foram verificadas antes da aplicação definitiva da migração. Em outra máquina, restaurar ou obter o banco normalmente e jamais presumir que `.env.testing` isola o banco; sempre passar `DB_DATABASE=isabelle_test` explicitamente.

O arquivo `exemplo-emitir-webiss-gurupi-log-582.json` está ignorado no `.gitignore`. O PDF `movimentacao-de-conta.pdf` é apenas referência funcional e não faz parte da implementação.

## 14. Próximas etapas, em ordem recomendada

### Etapa 2 — baixa financeira completa (implementada localmente em 09/09/2026)

1. Criar ações explícitas **Dar baixa** e **Receber** em vez de depender da edição do status.
2. Usar `financial_settlements` como fonte definitiva para saldo aberto.
3. Permitir baixa parcial e várias baixas por título, inclusive em contas diferentes.
4. Tratar juros, multa, desconto e tarifa sem distorcer o principal.
5. Implementar estorno auditado, sem apagar movimentações.
6. Bloquear edição direta de campos que provoque divergência após baixa ou conciliação.
7. Adicionar testes de parcial, múltiplas contas, estorno, concorrência e CNAB idempotente.

### Etapa 3 — relatório e integração financeira (implementada localmente em 09/09/2026)

1. Implementar o relatório “Movimentação de Conta” descrito na seção 8, inicialmente em tela e PDF; depois XLSX.
2. Exibir saldo anterior, saldo acumulado, totais, saldo final e saldo disponível.
3. Adicionar filtros por conta nos títulos, fluxo de caixa e dashboard.
4. Fazer fluxo de caixa e dashboard lerem títulos antes do corte e movimentos confirmados a partir dele, sem dupla contagem.
5. Garantir por testes que transferências não afetem DRE.

### Etapa 4 — conciliação avançada (implementada localmente em 09/09/2026)

1. Sugestões por valor, janela de data, documento e favorecido.
2. Conciliação 1:N, N:1 e parcial por alocações.
3. Exigir diferença zero para concluir.
4. Implementar desfazer conciliação e reabrir linha com trilha de auditoria.
5. Mostrar Banco × Sistema × Diferença por dia.
6. Melhorar tratamento de variantes OFX reais de Bradesco e Sicoob.

### Etapa 5 — implantação e homologação

1. Commitar e transferir todas as alterações deste trabalho; neste ponto os arquivos ainda podem estar modificados ou não rastreados no Git.
2. Na outra máquina, instalar dependências, obter banco atualizado e executar migrations e seeders.
3. Importar um OFX real de cada banco em ambiente de homologação.
4. Usuário informar a conta correta nos títulos pagos ou recebidos desde 01/08/2026.
5. Conciliar agosto em diante e comparar os saldos finais diariamente com os extratos.
6. Fazer backup de produção, publicar código, executar migration e `RoleSeeder`, limpar caches e validar permissões.
7. Somente considerar o módulo concluído quando todos os critérios da seção 11 forem atendidos.

## 15. Comandos de retomada sugeridos

Antes de continuar em outra máquina:

```bash
git status --short
git log -5 --oneline
php artisan migrate:status
php artisan route:list --path=bank
```

Depois, conferir no banco as duas contas e os saldos iniciais, executar os testes com banco isolado e continuar pela Etapa 4 da seção 14. As Etapas 2 e 3 e sua preparação para implantação estão detalhadas nas seções 16 e 17. Não executar migrations de produção antes de confirmar backup, credenciais e `DB_DATABASE`.


## 16. Continuidade implementada em 09/09/2026 — Etapa 2

### Entrega

- Ações **Dar baixa / Receber**, **Estornar baixa** e **Histórico de baixas** nas tabelas de títulos, edição e parcelas do contrato.
- Modal com conta, data, principal, juros, multa, desconto, tarifa, forma, referência e observação. Cada confirmação possui chave idempotente; uma repetição com valores divergentes ou após estorno é rejeitada.
- Baixas em lote quitam somente o saldo principal restante, em transação que engloba o lote. Nenhuma conta histórica é selecionada automaticamente.
- Cálculo decimal com BCMath, título bloqueado com `lockForUpdate()` antes de consultar o saldo; gravação de baixa, movimento e resumo na mesma transação.
- `principal_amount` representa a dívida liquidada; `amount` representa o dinheiro movimentado. Fórmula: principal + juros + multa − desconto; tarifa é acrescida na conta a pagar e deduzida na conta a receber. Principal deve ser positivo e não superar o saldo aberto; líquido deve ser positivo. Não há fluxo de crédito/adiantamento ou perdão integral sem movimento nesta entrega.
- `saldo_aberto` é calculado pelas baixas confirmadas. `situacao_financeira` mostra **Parcial** sem alterar os enums antigos: enquanto houver saldo, o status compatível continua `pendente`/`vencido`; ao zerar fica `pago`. `valor_pago` resume o líquido e a data/conta resumidas vêm da baixa mais recente por data efetiva, não pela ordem de cadastro.
- Estorno mantém ambos os registros com status `reversed`, grava autor/data/justificativa e recalcula o resumo do título. Não recria baixa estornada ao salvar o título ou repetir o CNAB.
- Campos financeiros e exclusão são bloqueados em títulos com histórico de baixa. Movimentos vinculados a baixa/transferência e movimentos conciliados não aceitam edição/exclusão direta. Observações do título continuam editáveis.
- Cancelamento de contrato com parcela parcialmente recebida é bloqueado. Correções de vigência e exclusão respeitam o histórico de baixas das parcelas.
- CNAB gera baixa `cnab` com chave por boleto, usa data de crédito, separa mora/juros, desconto/abatimento e tarifa reportados pelo parser e não duplica recebimento em reimportação. Valores que excedam o principal aberto são rejeitados para revisão, sem inferir adiantamento. A leitura de arquivos reais continua pendente de homologação; outros créditos, IOF e custas específicos do banco ainda não têm classificação automática.
- Conciliação básica trava os registros e rejeita movimentos estornados ou já conciliados, protegendo também a disputa entre conciliar e estornar. Desfazer conciliação continua na Etapa 4; por enquanto uma baixa conciliada não pode ser estornada pela interface.

### Migração, permissões e implantação

Nova migração: `database/migrations/2026_09_09_000001_extend_financial_settlements.php`. Acrescenta principal/ajustes, chave idempotente e auditoria do estorno. Para baixas existentes, copia `amount` para o principal, sem atribuir contas nem criar movimentos retroativos. Divergências antigas entre título e baixa continuam exigindo revisão humana.

Novas permissões: `Settle:Payable`, `Settle:Receivable`, `Reverse:Payable`, `Reverse:Receivable`. O `RoleSeeder` concede baixa a administrador/financeiro. **Não concede estorno automaticamente**; a definição dos responsáveis continua pendente conforme seção 12. Concessões de estorno já realizadas a esses perfis são preservadas quando o seeder roda novamente.

**O banco operacional não foi migrado nem teve permissões ou saldos alterados nesta continuidade.** Após backup e conferência de credenciais/banco, aplicar a nova migration e executar `RoleSeeder` antes de disponibilizar as telas alteradas. Atribuir as permissões de estorno aos responsáveis aprovados. O módulo como um todo ainda depende das Etapas 3–5 para atender todos os critérios de produção.

### Verificação

- Suíte completa em SQLite em memória: **43 aprovados, 1 ignorado e 1 falha antiga**, 184 assertions. A falha permanece `ExampleTest` (espera 200, recebe redirecionamento 302); o ignorado é a concorrência real, executada separadamente em MySQL.
- Suíte bancária e concorrência real no MySQL `isabelle_test`: **16 aprovados, 79 assertions**.
- Cobertura bancária inclui parciais em contas diferentes, ajustes, ordem de datas, requisição repetida, título desatualizado, estorno auditado, proteção de conciliação/edição/exclusão, corte, conta inativa, precisão decimal, CNAB idempotente, permissões e ações Filament via Livewire.
- Teste de concorrência usa dois processos reais contra MySQL: duas baixas de R$ 60,00 para um título de R$ 100,00 resultam em uma baixa confirmada e uma rejeitada, deixando R$ 40,00 em aberto.
- O teste de contrato pago foi adaptado para preparar o recebimento pelo serviço de baixa; foi acrescentada regressão para cancelamento com recebimento parcial.
- Formatação PHP verificada com `vendor/bin/pint --dirty` e whitespace com `git diff --check`.

Comandos utilizados (sempre banco explicitamente isolado e configuração cacheada separada):

```bash
docker exec isabelle_app sh -lc 'cd /var/www && DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= APP_CONFIG_CACHE=/tmp/isabelle-test-config.php php artisan test'
docker exec isabelle_app sh -lc 'cd /var/www && DB_CONNECTION=mysql DB_DATABASE=isabelle_test DB_URL= APP_CONFIG_CACHE=/tmp/isabelle-test-config.php php artisan test tests/Feature/BankMovementModuleTest.php tests/Feature/FinancialSettlementConcurrencyTest.php'
```

O segundo comando prepara o schema de `isabelle_test` pelo `RefreshDatabase` da suíte bancária antes do teste concorrente; não apontar esses testes para um banco operacional. O teste concorrente exige explicitamente o nome `isabelle_test` e extensão `pcntl`.


## 17. Relatório e integração financeira — Etapa 3 (09/09/2026)

### Onde operar

- **Relatórios → Movimentação de Conta** (`/bank-account-report`): selecionar conta obrigatória e período, depois **Gerar relatório**. Exportações **Abrir PDF** e **Exportar Excel** usam os filtros do relatório gerado.
- Atalhos para o relatório no cabeçalho da edição de contas bancárias e na listagem de movimentações.
- Filtros de conta nas listagens de contas a pagar/receber, no fluxo de caixa, no dashboard e também na DRE.
- Cadastro de contas mostra saldo atual e saldo com limite; saldo atual desconsidera movimentos futuros.

### Regras implementadas

- Extrato inclui somente movimentos confirmados da conta, em ordem de data/hora e ID. Conta inativa continua disponível para consulta.
- Saldo anterior inclui saldo inicial mais os movimentos confirmados anteriores ao período; cada linha mostra o saldo real acumulado da conta. Totais mostram débitos, créditos, movimento líquido, saldo final e saldo com limite.
- Categoria, conciliação e busca por favorecido/documento selecionam linhas **sem recalcular o saldo da conta como se os lançamentos ocultos não existissem**. Totais da seleção são mostrados separadamente, com explicação na tela/PDF/XLSX.
- O período do extrato deve começar desde 01/08/2026 e depois da data do saldo inicial da conta. Não foi implementada reconstrução de julho.
- Tela e PDF compartilham a mesma apresentação de dados; XLSX conserva os mesmos valores e filtros. Campos textuais são explicitamente texto na planilha; valores monetários são células numéricas com duas casas decimais. Cálculos financeiros permanecem decimais via BCMath.
- Nova permissão `View:BankAccountReport`, incluída para administrador/financeiro no `RoleSeeder`. PDF exige sessão, URL assinada e essa permissão; a exportação Excel também verifica permissão no servidor.
- `FinancialCashService` centraliza a leitura: títulos pagos com data anterior a 01/08/2026 e movimentos confirmados desde o corte, sem somar novamente `valor_pago` dos títulos atuais. Isso inclui baixas parciais em suas datas/contas efetivas, avulsos e exclusão dos estornos.
- Títulos pagos após o corte ainda sem baixa bancária são contados como pendências e ficam fora dos valores realizados; nenhuma baixa ou associação histórica foi criada automaticamente. Avisos aparecem no fluxo de caixa, DRE, dashboard e extrato (neste último, para títulos já associados à conta consultada).
- DRE e dashboard classificam o líquido dos movimentos por categoria, excluindo transferências internas e saldo inicial. Juros/descontos/tarifas incorporados à baixa continuam na categoria do movimento; não há distribuição automática desses componentes em categorias próprias nesta etapa.
- Fluxo de caixa consolidado exclui as duas pontas das transferências; por conta, mostra a entrada/saída correspondente. Saldo inicial em regime de caixa desde o corte é calculado pelas contas, não digitado. Em períodos que atravessam o corte, uma linha explícita ajusta o saldo para a abertura bancária de 01/08/2026, fora dos totais de entradas/saídas. Aberturas de novas contas durante o período também são separadas do resultado.
- Regime de competência mantém os valores nominais dos títulos por vencimento. Indicadores de valores em aberto no dashboard e na tabela de vencidos mostram somente o principal restante.
- Filtro dos títulos e valores em aberto aceita a conta do título ou uma de suas baixas confirmadas. Um título parcial em várias contas pode aparecer em mais de um filtro; esses subtotais em aberto não devem ser somados entre contas. Valores realizados usam sempre a conta efetiva de cada movimento.

### Arquivos principais

- `app/Services/BankAccountReportService.php` e `app/Services/FinancialCashService.php`;
- `app/Filament/Pages/BankAccountReport.php`;
- `app/Http/Controllers/BankAccountReportController.php` e rota `reports.bank-account.pdf`;
- `app/Exports/BankAccountReportExport.php`;
- `resources/views/reports/bank-account-content.blade.php`, `resources/views/pdf/bank-account-report.blade.php` e `resources/views/filament/pages/bank-account-report.blade.php`;
- integração em `CashFlowService`, `DreService`, `DashboardFinanceService` e widgets/filtros correspondentes;
- `tests/Feature/BankAccountReportTest.php`, com fixtures dos testes anteriores de DRE/dashboard atualizadas para registrar as baixas após o corte.

### Validação e estado de implantação

- Suíte completa em SQLite em memória: **53 aprovados, 1 ignorado e 1 falha antiga**, **253 assertions**. A falha continua sendo o `ExampleTest` (200 esperado, 302 retornado). A concorrência real exige MySQL e já foi validada na Etapa 2.
- Validação da Etapa 3 no MySQL `isabelle_test`: **12 aprovados, 84 assertions** (relatório, DRE e dashboard).
- Dez novos testes cobrem saldos/totais exatos, desempate por ID, filtros, conta inativa, conciliação, corte e abertura, parciais por conta, transferências fora da DRE/consolidado, estorno, validação de período, autorização do PDF, renderização Livewire e leitura de XLSX real.
- PDF renderizado com dados fictícios e inspecionado visualmente em A4 horizontal; prévia local em `storage/app/bank-report-preview.pdf` (ignorada pelo Git). Não contém movimentações operacionais.
- `git diff --check` sem erros; arquivos PHP novos/alterados formatados com Pint. O arquivo de rotas preserva a formatação anterior fora da rota acrescentada.
- **Nenhuma migration, seeder, saldo ou classificação foi alterado no banco operacional.** A Etapa 3 não acrescenta migration; depende da migration da Etapa 2 e da execução de `RoleSeeder` após backup/conferência do banco. Depois da implantação, limpar caches e conferir o acesso ao relatório.
- Próxima etapa: **Etapa 5 — implantação e homologação**. A implementação e a simulação isolada com os OFX reais estão descritas na seção 18. A importação assistida na aplicação deve ocorrer em homologação depois das migrations, permissões e contas bancárias terem sido conferidas.

## 18. Conciliação avançada e preparação da homologação — Etapa 4 (09/09/2026)

### Onde operar

- **Financeiro → Contas Bancárias → Editar → Importar OFX**: importa o extrato para a conta selecionada. A conta deve estar cadastrada com banco e número iguais aos do arquivo.
- **Financeiro → Conciliação Bancária**: mostra valor do banco, valor já alocado, diferença e situação. A ação **Alocar** apresenta sugestões e também permite buscar outro lançamento por histórico, documento ou favorecido.
- **Financeiro → Conciliação Bancária → Banco × Sistema** (`/bank-reconciliation-summary`): compara o saldo diário reconstruído do OFX com o saldo confirmado no sistema.

### Regras implementadas

- Sugestões são ordenadas por valor restante, proximidade de data, documento e histórico/favorecido. A janela automática é de dez dias; a busca manual alcança lançamentos compatíveis fora dela.
- Uma linha bancária pode ser distribuída entre vários lançamentos e um lançamento pode receber várias linhas. Alocações parciais ficam gravadas, e **Concluir** exige diferença exatamente zero.
- Locks no banco e cálculo decimal impedem que alocações simultâneas ultrapassem o valor da linha ou do lançamento.
- **Desfazer conciliação** preserva as alocações revertidas, autor, data e justificativa, reabre a linha e permite nova alocação. A permissão `UndoReconciliation:BankStatementEntry` não é concedida automaticamente pelo seeder.
- Linhas sem alocação podem ser arquivadas com justificativa e depois reabertas. O histórico registra alocação, conclusão, desfazimento, arquivamento e reabertura.
- O comparativo diário usa o saldo informado pelo banco em cada linha importada e o saldo do razão confirmado na conta. Dias sem saldo bancário disponível são identificados sem inventar valor.
- O importador limita arquivos a 10 MB, bloqueia reimportação pelo hash, valida banco e conta, rejeita FITID repetido e datas anteriores ao corte/saldo inicial e aceita OFX SGML, decimal com ponto ou vírgula e texto UTF-8/Windows-1252/ISO-8859-1.
- O período efetivo é derivado das movimentações. Isso evita confiar em cabeçalhos inconsistentes, como o encontrado no arquivo do Bradesco. A data inválida do saldo final usa como referência a última movimentação válida.
- Não haverá integração Open Finance. O escopo aprovado permanece importação manual de OFX e conciliação dentro da aplicação.

### OFX reais disponíveis e momento da importação

- `docs/Bradesco_03092026_144009.OFX`: 15 movimentações reconhecidas.
- `docs/extrato-conta-corrente-ofx-money_202609_20260903143848.ofx`: 20 movimentações reconhecidas.
- Ambos foram usados somente por teste automatizado com `RefreshDatabase` em SQLite e no MySQL isolado `isabelle_test`. O teste valida leitura, codificação, saldo final, FITID único e bloqueio da reimportação. Nenhum dado desses arquivos foi persistido no banco operacional; os arquivos foram incluídos no `.gitignore` para não serem versionados.
- A importação pela interface deve ser feita na **Etapa 5, em homologação**, depois de aplicar as migrations `2026_09_09_000001` e `2026_09_09_000002`, executar `RoleSeeder`, conferir banco/número/saldo inicial das duas contas e classificar as baixas desde 01/08/2026. Importar cada arquivo na respectiva conta e então conciliar as linhas e validar **Banco × Sistema** dia a dia.
- Em produção, repetir a importação somente depois da homologação aprovada, backup confirmado, publicação do código, migrations/seeders e validação das permissões. O bloqueio por hash evita uma segunda importação acidental na mesma conta.

### Arquivos principais

- `database/migrations/2026_09_09_000002_advance_bank_reconciliation.php`;
- `app/Services/BankReconciliationService.php` e `app/Services/OfxImportService.php`;
- `app/Filament/Resources/BankStatementEntries/BankStatementEntryResource.php`;
- `app/Filament/Pages/BankReconciliationSummary.php` e views de comparativo/histórico;
- `app/Models/BankStatementEntryEvent.php` e relações de alocação/auditoria;
- `tests/Feature/AdvancedBankReconciliationTest.php`, `tests/Feature/RealOfxCompatibilityTest.php` e o cenário de concorrência em `FinancialSettlementConcurrencyTest.php`.

### Estado de implantação

**O banco operacional não recebeu migration, seeder, importação OFX, conciliação ou alteração de saldo nesta etapa.** A Etapa 5 ainda exige homologação assistida e conferência dos saldos antes da publicação em produção.

### Verificação

- Suíte completa em SQLite em memória: **62 aprovados, 2 ignorados e 1 falha antiga**, 310 assertions. A falha continua em `ExampleTest`, que espera HTTP 200 na raiz protegida e recebe o redirecionamento 302. Os dois testes ignorados exigem concorrência real no MySQL.
- Validação integrada das Etapas 2–4 no MySQL `isabelle_test`: **36 aprovados, 211 assertions**. Inclui os dois arquivos OFX reais e duas disputas com processos simultâneos: baixa financeira e alocação de conciliação.
- Os dois OFX também passaram isoladamente no MySQL com 12 assertions. A importação repetida do mesmo conteúdo na mesma conta foi rejeitada como previsto.
- Os 60 arquivos PHP modificados passaram pelo Pint e `git diff --check` não apresentou erro de whitespace.

## 19. Início da Etapa 5 no ambiente local — 10/09/2026

- Os menus **Relatórios → Movimentação de Conta** e **Financeiro → Conciliação Bancária** estavam ocultos porque as migrations `2026_09_09_000001` e `2026_09_09_000002` ainda estavam pendentes e as novas permissões não existiam no banco local.
- Antes da alteração foi criado o backup `storage/app/backups/isabelle_db_pre_etapa5_20260910.sql`, com SHA-256 `a5ebbf31da85570903e1c7b47244bb0a007200fbc7175b645428ef2e4fba44f0`. O diretório de backups passou a ser ignorado pelo Git.
- As duas migrations foram aplicadas, o `RoleSeeder` foi executado e todos os caches da aplicação/Filament foram limpos. Os perfis `administrador` e `financeiro` possuem `View:BankAccountReport`, `ViewAny:BankStatementEntry`, `Reconcile:BankStatementEntry` e `Archive:BankStatementEntry`.
- As duas contas cadastradas correspondem ao banco e ao número informados nos respectivos OFX. Ainda existem **4 contas a pagar** e **16 contas a receber**, marcadas como pagas desde 01/08/2026, sem baixa financeira vinculada. Existem zero movimentos, zero baixas e zero linhas OFX no banco local.
- Os OFX não foram importados nesta execução. Antes da importação, o usuário deve identificar em qual das duas contas ocorreu cada um dos 20 pagamentos/recebimentos históricos; só então as baixas devem ser registradas e os arquivos importados para que as sugestões de conciliação encontrem os movimentos corretos.
- Uma segunda verificação identificou que `super_admin` não recebia permissões explícitas e o bypass do Gate estava desativado. `config/filament-shield.php` passou a habilitar `define_via_gate`, fazendo o perfil acessar os relatórios, a conciliação e as ações financeiras conforme previsto.
- Títulos antigos já marcados como pagos ocultavam **Dar baixa**. Para os títulos pagos desde o corte e ainda sem `financial_settlements`, a tabela agora exibe **Registrar baixa histórica**, preenchendo a data e o principal existentes e pedindo a conta correta. A operação usa chave idempotente própria e cria a baixa/movimento necessários para a conciliação.
- Regressão após a correção: **25 testes aprovados, 132 assertions** em SQLite isolado. Os caches foram limpos e o contêiner da aplicação foi reiniciado.
- Para evitar o tratamento individual dos 20 títulos, as tabelas de contas a pagar e receber agora possuem o filtro **Pagos sem baixa bancária** e a ação em lote **Registrar baixas históricas**. O usuário pode selecionar os títulos de uma mesma conta, escolher Bradesco ou Sicoob uma única vez e confirmar; cada título preserva sua própria data, valor e forma de pagamento.
- O registro histórico em lote é transacional e idempotente: se qualquer item da seleção for inválido, o lote inteiro é cancelado; uma repetição não cria movimentos duplicados. Validação atualizada: **26 testes aprovados, 137 assertions** no grupo financeiro/conciliação em SQLite isolado.
- Ajuste de usabilidade das listagens: em contas a pagar, **Categoria** inicia oculta, **Baixa** foi removida e todas as ações da linha ficam no menu de três pontos. Em contas a receber, **Contrato** inicia oculto e as colunas **Baixa** e **Situação** foram removidas. A ordem numérica da navegação mantém **Contas a Receber** imediatamente antes de **Contas a Pagar** entre esses dois recursos.
- A navegação financeira recebeu posições exclusivas para eliminar empates de ordenação: Plano de Contas, Movimentação de Contas, **Contas a Receber**, **Contas a Pagar**, Conciliação Bancária, Banco × Sistema, Boletos, Remessas, Reenvio, Retornos e NFSes. A ordem efetiva foi consultada no Navigation Manager do Filament após limpeza de cache e reinício dos serviços.
- Após nova sincronização local com a base de produção, em 10/09/2026, as migrations bancárias foram corretamente reaplicadas no lote 11. A conferência encontrou novamente **4 pagamentos e 16 recebimentos históricos sem baixa**, zero movimentos, zero baixas e zero linhas OFX. O banco restaurado não continha as permissões customizadas; o `RoleSeeder` foi executado novamente e os perfis administrador/financeiro voltaram a possuir as cinco permissões operacionais verificadas. Nenhum título ou extrato foi alterado nessa conferência.
