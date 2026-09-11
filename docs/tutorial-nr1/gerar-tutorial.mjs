import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const dir=path.dirname(fileURLToPath(import.meta.url));
// As imagens são capturas reais. O enquadramento e as marcações pertencem à diagramação.
function fig(name,crop,width,marks=[]){const [x,y,w,h]=crop;return `<figure style="width:${width}mm"><div class="screen" style="height:${width*h/w}mm"><img src="${name}.png" style="width:${1440/w*100}%;left:${-x/w*100}%;top:${-y/h*100}%">${marks.map(([a,b,c,d,n])=>`<span class="mark" style="left:${(a-x)/w*100}%;top:${(b-y)/h*100}%;width:${c/w*100}%;height:${d/h*100}%"><b>${n}</b></span>`).join('')}</div></figure>`;}
let pages=[];
function page(tag,title,body){pages.push(`<section class="page"><header><span>INSTITUTO ALVES NEVES · SISTEMA ISABELLE</span><span>${tag}</span></header><h1>${title}</h1>${body}<footer><span>Telas reais · dados fictícios para demonstração · 11/09/2026</span><span>${pages.length+1} / 8</span></footer></section>`);}
const box=(title,text)=>`<div class="note"><h3>${title}</h3><p>${text}</p></div>`;
page('GUIA PRÁTICO','Ciclos NR-1: contrato novo e renovação',`
<p class="lead">Cada contrato NR-1 tem seu checklist organizado por ano. O ano do ciclo vem da <b>data de início da vigência</b>.</p>
<div class="cards"><div><h2>Contrato novo · páginas 2–5</h2><p><b>Criar contrato → selecionar NR-1 → salvar → preencher checklist.</b><br>O sistema abre automaticamente o primeiro ciclo.</p></div><div><h2>Contrato existente · páginas 6–8</h2><p><b>Abrir contrato → Renovar contrato → confirmar → preencher novo ciclo.</b><br>O ciclo anterior permanece no histórico.</p></div></div>
<p><b>Comece em CRM → Contratos.</b> Para cadastrar, clique em <b>Criar contrato</b>. Para renovar, localize e abra o contrato existente.</p>
${fig('00-acesso',[0,70,1430,435],273,[[1258,120,150,43,'1']])}
<p class="caption">1 · Botão de cadastro. Tenha o cliente e a categoria do plano de contas já cadastrados. Os números e valores deste guia são apenas exemplos.</p>`);
page('CONTRATO NOVO · 1/3','Identifique o contrato e selecione NR-1',`
<div class="row">${fig('01-novo-contrato',[330,92,1090,895],191,[[874,440,513,72,'1']])}<aside>
<h2>Em Dados do Contrato</h2><ol><li>Informe um <b>Número do Contrato</b> ainda não utilizado.</li><li>Selecione o <b>Cliente</b> e a <b>Categoria (Plano de Contas)</b>.</li><li>Em <b>Tipo de Serviço</b>, escolha <b>NR-1</b> <em>①</em>.</li><li>Defina o <b>Status</b> conforme a situação do contrato e preencha a descrição, se necessário.</li></ol>
${box('Ainda há duas abas','Preencha também <b>Financeiro</b> e <b>Vigência</b> antes de clicar em Criar.')}
<p class="small">No exemplo, o contrato está Ativo. O cadastro também permite Rascunho; a criação inicial de um contrato NR-1 já abre seu ciclo.</p></aside></div>`);
page('CONTRATO NOVO · 2/3','Preencha os dados financeiros',`
${fig('02-financeiro',[330,165,1090,385],273)}
<div class="cards"><div><h2>O que informar</h2><p><b>Valor Total:</b> valor contratado.<br><b>Quantidade de Parcelas:</b> número de parcelas acordadas.<br><b>Forma de Pagamento:</b> selecione a opção combinada.</p></div><div><h2>Exemplo da tela</h2><p>R$ 2.400,00 em 12 parcelas, por PIX.<br>Use os dados reais do seu contrato e avance para a aba <b>Vigência</b>.</p></div></div>
${box('Confira antes de salvar','O cadastro do contrato gera as parcelas financeiras automaticamente. Revise valor, quantidade e forma de pagamento.')}`);
page('CONTRATO NOVO · 3/3','Defina a vigência e crie o primeiro ciclo',`
<div class="row">${fig('03-vigencia',[330,180,1090,830],191,[[352,348,516,76,'1'],[332,955,74,44,'2']])}<aside>
<ol><li>Em <b>Vigência</b>, informe a <b>Data de Início</b> <em>①</em> e confira a <b>Data de Fim</b>.</li><li>Anexe o PDF do contrato e escreva observações, se necessário.</li><li>Revise as três abas e clique em <b>Criar</b> <em>②</em>.</li></ol>
${box('O início define o ano','Início em <b>01/01/2026</b> cria a <b>NR-1/2026</b>, mesmo que a vigência termine em 2027.')}
<p>A data final é sugerida automaticamente após informar o início. Ajuste-a ao período contratado.</p>
<h3>Resultado esperado</h3><p>O sistema abre a edição do contrato e disponibiliza <b>Preencher checklist NR-1/2026</b>.</p></aside></div>`);
page('CHECKLIST · VALE PARA OS DOIS CENÁRIOS','Preencha as etapas e salve o checklist',`
<p>Na edição do contrato, clique em <b>Preencher checklist NR-1/ANO</b>. Confira o ano e o contrato no início da janela.</p>
<div class="row">${fig('07-checklist-salvar',[201,50,1025,1040],135,[[220,1016,85,51,'1']])}<aside class="wide">
<ol><li>Marque <b>Concluída</b> somente nas etapas realizadas e registre a data. Na etapa 1, informe também a modalidade: Presencial ou Online.</li><li>Percorra as cinco etapas: Encontro; Avaliação dos Riscos Psicossociais; Relatório Diagnóstico (DPRS); Matriz de Risco; Devolutiva.</li><li>Inclua observações, role até o final e clique em <b>Enviar</b> <em>①</em> para salvar.</li></ol>
<h3>O status é calculado pelo sistema</h3><table><tr><th>Etapas concluídas</th><th>Status</th></tr><tr><td>Ainda não concluiu todas as etapas 1–3</td><td>Pendente</td></tr><tr><td>Etapas 1, 2 e 3</td><td>Em Andamento</td></tr><tr><td>Etapas 1, 2, 3 e 4</td><td>Regularizada</td></tr><tr><td>Todas as cinco etapas</td><td>Finalizada</td></tr></table>
<p class="small">Cada etapa vale 20% de progresso. Assim, 20% ou 40% ainda pode aparecer como Pendente. O status acima é o acompanhamento registrado no sistema.</p>
</aside></div>`);
page('RENOVAÇÃO · 1/2','Abra o contrato existente e escolha Renovar',`
<p>Em <b>CRM → Contratos</b>, abra o contrato NR-1 que será renovado. No topo da edição, clique em <b>Renovar contrato</b> <em>①</em>.</p>
${fig('08-renovar-acesso',[450,193,715,61],273,[[965,202,185,43,'1']])}
<div class="row gap-top">${fig('09-renovar-dados',[264,17,895,585],143)}<aside class="wide">
<h2>A janela já vem preenchida</h2><p>Confira os dados do contrato e altere somente as condições necessárias para a nova vigência.</p>
<p><b>Cliente, categoria, número e tipo de serviço</b> permanecem os mesmos e estão bloqueados na renovação.</p>
${box('Quando a ação aparece','O contrato precisa estar <b>Ativo</b> ou <b>Finalizado</b>. Contratos cancelados não podem ser renovados.')}
<p class="small"><b>Corrigir contrato</b> é para corrigir informações cadastradas; essa ação não abre um novo ciclo anual.</p>
</aside></div>`);
page('RENOVAÇÃO · 2/2','Revise a nova vigência e confirme a renovação',`
<div class="row">${fig('10-renovar-vigencia',[264,0,895,1090],133,[[285,435,856,167,'1'],[714,1019,432,48,'2']])}<aside class="wide">
<ol><li>Revise <b>Valor Total</b>, <b>Quantidade de Parcelas</b> e <b>Forma de Pagamento</b>.</li><li>Confira <b>Novo início</b> e <b>Novo fim</b> <em>①</em>. Na tela: 01/01/2027 a 31/12/2027.</li><li>Anexe o novo contrato/aditivo em PDF, se houver. Observações e motivo da renovação são opcionais.</li><li>Clique em <b>Confirmar renovação</b> <em>②</em>.</li></ol>
${box('O que acontece ao confirmar','O sistema registra uma nova versão do contrato, gera as parcelas da nova vigência e abre a <b>NR-1/2027</b>, com checklist vazio e status Pendente.')}
<h3>Uma NR-1 por ano, por contrato</h3><p>Se já existir uma NR-1/2027 nesse contrato, o sistema impede outra para o mesmo ano. Confira o ano de início e o histórico antes de confirmar.</p>
<p class="small">O ano é determinado pelo <b>Novo início</b>, e não pela data em que você clica no botão nem pelo Novo fim.</p></aside></div>`);
page('CONFERÊNCIA FINAL','Confira o novo ciclo e preserve o histórico',`
<p>Depois da confirmação, use <b>Preencher checklist NR-1/2027</b> no topo do contrato. Para conferir todos os anos, desça abaixo do formulário e abra <b>Checklist NR-1 por ano</b>.</p>
${fig('13-historico',[330,701,1075,375],240,[[349,901,1030,50,'1'],[349,958,1030,49,'2']])}
<div class="cards"><div><h2>① Novo ciclo · NR-1/2027</h2><p>Começa <b>Pendente, com 0%</b>. Clique em <b>Editar checklist</b> na linha de 2027 ou use o botão do topo para preencher as etapas.</p></div><div><h2>② Ciclo anterior · NR-1/2026</h2><p>No exemplo, continua <b>Finalizada, com 100%</b>. A renovação não limpa o checklist anterior. Confira o ano antes de editar qualquer linha.</p></div></div>
<div class="cards plain"><div><h3>Histórico do contrato</h3><p>A aba <b>Linha do tempo de renovações</b> mostra as versões e suas vigências. O ano identifica a NR-1; a versão registra o histórico contratual.</p></div><div><h3>Se o ciclo não aparecer</h3><p>Confira o tipo de serviço e a aba de histórico. Se faltarem ciclos ou ações esperadas, solicite a verificação ao administrador antes de cadastrar outro contrato.</p></div></div>`);
const html=`<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Tutorial — Ciclos NR-1 | Sistema Isabelle</title><style>
@page{size:A4 landscape;margin:0}*{box-sizing:border-box}body{margin:0;background:#e8e5ef;color:#242235;font-family:Arial,Helvetica,sans-serif;font-size:11pt;line-height:1.42}.page{width:297mm;height:210mm;background:white;padding:10mm 12mm 12mm;position:relative;break-after:page;overflow:hidden}.page:last-child{break-after:auto}header{display:flex;justify-content:space-between;color:#716985;font-size:8pt;letter-spacing:.7px;border-bottom:1px solid #e5dff1;padding-bottom:3mm}h1{font-size:23pt;line-height:1.12;margin:4mm 0 4mm;color:#45267c}h2{font-size:13pt;margin:0 0 2mm;color:#45267c}h3{font-size:11pt;margin:0 0 2mm;color:#45267c}p{margin:0 0 3mm}b{font-weight:700}em{font-style:normal;color:#df5c20;font-weight:bold}.lead{font-size:12pt}.row{display:flex;gap:7mm;align-items:flex-start}aside{flex:1;min-width:0}aside.wide{font-size:11pt}.cards{display:flex;gap:5mm;margin:3mm 0}.cards>div{flex:1;padding:4mm;background:#f4f0fa;border-radius:3mm}.cards.plain>div{padding:2mm 0;background:none}.cards p{margin-bottom:0}.note{background:#f5f0fc;border-left:3px solid #8851d4;padding:3mm;margin:4mm 0}.note p{margin:0}.small,.caption{font-size:9pt;color:#5a5466}.caption{margin-top:2mm}ol{padding-left:5mm;margin:0 0 4mm}li{margin-bottom:3mm}figure{margin:0;flex-shrink:0}.screen{position:relative;overflow:hidden;border:1px solid #ded8e7;border-radius:2mm;background:#fafafa}.screen img{position:absolute;max-width:none;height:auto}.mark{position:absolute;border:2px solid #df5c20;border-radius:2mm;pointer-events:none}.mark>b{position:absolute;left:-2mm;top:-2.5mm;color:white;background:#df5c20;border-radius:50%;width:5mm;height:5mm;text-align:center;font-size:9pt;line-height:5mm}.gap-top{margin-top:5mm}table{border-collapse:collapse;width:100%;font-size:9pt;margin:3mm 0}th,td{text-align:left;padding:2mm;border-bottom:1px solid #e4dfeb}th{background:#f4f0fa;color:#45267c}footer{position:absolute;bottom:5mm;left:12mm;right:12mm;display:flex;justify-content:space-between;color:#81778e;font-size:8pt;border-top:1px solid #eee8f4;padding-top:2mm}@media screen{.page{margin:8mm auto;box-shadow:0 2px 12px #aaa}}
</style></head><body>${pages.join('')}</body></html>`;
await fs.writeFile(path.join(dir,'tutorial-ciclos-nr1.html'),html);
console.log('HTML gerado: 8 páginas.');
