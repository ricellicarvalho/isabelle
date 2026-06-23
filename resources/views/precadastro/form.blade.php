@extends('layouts.precadastro')

@section('content')

{{-- Token inválido ou expirado --}}
@if ($estado === 'token_invalido')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Link inválido ou expirado</h2>
        <p class="text-gray-500 text-sm">Entre em contato com a empresa para receber um novo link.</p>
    </div>

{{-- Cadastro já preenchido --}}
@elseif ($estado === 'ja_preenchido')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Cadastro já enviado</h2>
        <p class="text-gray-500 text-sm">
            O pré-cadastro de <strong>{{ $client->razao_social }}</strong> já foi preenchido.<br>
            Caso precise corrigir alguma informação, entre em contato com a empresa e solicite a reabertura do pré-cadastro.
        </p>
    </div>

{{-- Enviado com sucesso (flash) --}}
@elseif (session('enviado'))
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Cadastro concluído!</h2>
        <p class="text-gray-500 text-sm">
            Os dados de <strong>{{ $client->razao_social }}</strong> foram recebidos.<br>
            Obrigado pelo preenchimento!
        </p>
    </div>

{{-- Formulário --}}
@else
    <div
        class="space-y-6"
        x-data="{
            // Colaboradores
            colaboradores: @js(old('colaboradores_json') ? json_decode(old('colaboradores_json'), true) : ($colaboradoresIniciais ?? [])),
            novoNome: '',
            novoTelefone: '',
            novoLocal: '',
            editandoIndex: -1,
            erroNome: '',
            erroLista: '',

            adicionar() {
                this.erroNome = '';
                if (this.novoNome.trim() === '') {
                    this.erroNome = 'O nome do colaborador é obrigatório.';
                    return;
                }
                if (this.editandoIndex >= 0) {
                    this.colaboradores[this.editandoIndex] = {
                        nome: this.novoNome.trim(),
                        telefone: this.novoTelefone.trim(),
                        local: this.novoLocal.trim()
                    };
                    this.editandoIndex = -1;
                } else {
                    this.colaboradores.push({
                        nome: this.novoNome.trim(),
                        telefone: this.novoTelefone.trim(),
                        local: this.novoLocal.trim()
                    });
                }
                this.novoNome = '';
                this.novoTelefone = '';
                this.novoLocal = '';
                this.erroLista = '';
            },

            editar(i) {
                this.editandoIndex = i;
                this.novoNome = this.colaboradores[i].nome;
                this.novoTelefone = this.colaboradores[i].telefone;
                this.novoLocal = this.colaboradores[i].local;
                this.erroNome = '';
            },

            remover(i) {
                if (this.editandoIndex === i) { this.cancelar(); }
                this.colaboradores.splice(i, 1);
            },

            cancelar() {
                this.editandoIndex = -1;
                this.novoNome = '';
                this.novoTelefone = '';
                this.novoLocal = '';
                this.erroNome = '';
            },

            // Telefones da empresa
            telefones: @js(old('telefones_json') ? json_decode(old('telefones_json'), true) : ($telefonesIniciais ?? [])),
            tipoTelLabels: {
                celular: '📱 Celular',
                fixo: '📞 Fixo',
                whatsapp: '💬 WhatsApp',
                trabalho: '🏢 Trabalho',
                recado: '📝 Recado'
            },
            novoTelTipo: 'celular',
            novoTelNumero: '',
            editandoTelIndex: -1,
            erroTelNumero: '',
            erroTelLista: '',

            adicionarTelefone() {
                this.erroTelNumero = '';
                if (this.novoTelNumero.trim() === '') {
                    this.erroTelNumero = 'O número é obrigatório.';
                    return;
                }
                if (this.editandoTelIndex >= 0) {
                    this.telefones[this.editandoTelIndex] = { tipo: this.novoTelTipo, numero: this.novoTelNumero.trim() };
                    this.editandoTelIndex = -1;
                } else {
                    this.telefones.push({ tipo: this.novoTelTipo, numero: this.novoTelNumero.trim() });
                }
                this.novoTelTipo = 'celular';
                this.novoTelNumero = '';
                this.erroTelLista = '';
            },

            editarTelefone(i) {
                this.editandoTelIndex = i;
                this.novoTelTipo = this.telefones[i].tipo;
                this.novoTelNumero = this.telefones[i].numero;
                this.erroTelNumero = '';
            },

            removerTelefone(i) {
                if (this.editandoTelIndex === i) { this.cancelarTelefone(); }
                this.telefones.splice(i, 1);
            },

            cancelarTelefone() {
                this.editandoTelIndex = -1;
                this.novoTelTipo = 'celular';
                this.novoTelNumero = '';
                this.erroTelNumero = '';
            },

            // Máscara de telefone: formato decidido pela quantidade de dígitos digitados
            // (celular = 9 dígitos, fixo = 8 dígitos), não pelo tipo selecionado,
            // pois whatsapp, trabalho e recado podem ser tanto fixo quanto celular.
            maskTelefone(valor) {
                const digitos = valor.replace(/\D/g, '').slice(0, 11);
                const ddd = digitos.slice(0, 2);
                const resto = digitos.slice(2);

                if (resto.length === 0) {
                    return ddd;
                }

                const ehCelular = digitos.length > 10;
                const meio = ehCelular ? resto.slice(0, 5) : resto.slice(0, 4);
                const fim = ehCelular ? resto.slice(5, 9) : resto.slice(4, 8);

                return fim ? `(${ddd}) ${meio}-${fim}` : `(${ddd}) ${meio}`;
            },

            // Endereço
            ufs: ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'],
            cep: @js(old('cep', $client->cep)),
            endereco: @js(old('endereco', $client->endereco)),
            numero: @js(old('numero', $client->numero)),
            complemento: @js(old('complemento', $client->complemento)),
            bairro: @js(old('bairro', $client->bairro)),
            uf: @js(old('uf', $client->uf)),
            cidade: @js(old('cidade', $client->cidade)),
            cidades: [],
            carregandoCidades: false,
            buscandoCep: false,

            async carregarCidades(manterCidade) {
                if (! this.uf) { this.cidades = []; return; }
                this.carregandoCidades = true;
                try {
                    const resp = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${this.uf}/municipios`);
                    const data = await resp.json();
                    this.cidades = data.map(c => c.nome).sort();
                    if (! manterCidade) { this.cidade = ''; }
                } catch (e) {
                    this.cidades = [];
                }
                this.carregandoCidades = false;
            },

            async buscarCep() {
                const digits = this.cep.replace(/\D/g, '');
                if (digits.length !== 8) return;
                this.buscandoCep = true;
                try {
                    const resp = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
                    const data = await resp.json();
                    if (! data.erro) {
                        this.endereco = data.logradouro || this.endereco;
                        this.complemento = data.complemento || this.complemento;
                        this.bairro = data.bairro || this.bairro;
                        this.uf = data.uf || this.uf;
                        await this.carregarCidades(true);
                        this.cidade = data.localidade || this.cidade;
                    }
                } catch (e) {}
                this.buscandoCep = false;
            },

            init() {
                if (this.uf) { this.carregarCidades(true); }
            },

            prepararEnvio(formEl) {
                this.erroLista = '';
                this.erroTelLista = '';
                let valido = true;

                if (this.telefones.length === 0) {
                    this.erroTelLista = 'Adicione pelo menos um telefone antes de enviar.';
                    valido = false;
                }
                if (this.colaboradores.length === 0) {
                    this.erroLista = 'Adicione pelo menos um colaborador antes de enviar.';
                    valido = false;
                }
                if (! valido) return;

                formEl.querySelector('#telefones_json').value = JSON.stringify(this.telefones);
                formEl.querySelector('#colaboradores_json').value = JSON.stringify(this.colaboradores);
                formEl.submit();
            }
        }"
        x-init="init()"
    >

        {{-- Cabeçalho --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-600 to-indigo-700 px-6 py-7 shadow-lg shadow-blue-200/50">
            <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full"></div>
            <div class="absolute -bottom-10 -left-6 w-28 h-28 bg-white/10 rounded-full"></div>
            <div class="relative">
                <span class="inline-flex items-center gap-1.5 bg-white/15 text-white text-xs font-semibold px-3 py-1 rounded-full mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pré-Cadastro
                </span>
                <h1 class="text-xl font-bold text-white">Complete o cadastro da sua empresa</h1>
                <p class="text-blue-100 text-sm mt-1">{{ $client->razao_social }}</p>
            </div>
        </div>

        <form
            id="form-envio"
            method="POST"
            action="{{ route('precadastro.submit', $token ?? '') }}"
            @submit.prevent="prepararEnvio($el)"
            class="space-y-6">
            @csrf
            <input type="hidden" id="telefones_json" name="telefones_json" value="">
            <input type="hidden" id="colaboradores_json" name="colaboradores_json" value="">

            @if ($client->tipo_pessoa !== 'caepf')
                {{-- ===================== SEÇÃO: IDENTIFICAÇÃO COMPLEMENTAR ===================== --}}
                <section class="space-y-4">
                    <div class="flex items-center gap-2 px-1">
                        <span class="w-7 h-7 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m16 0v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4m4-12h.01M15 9h.01M9 13h.01M15 13h.01"/>
                            </svg>
                        </span>
                        <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Identificação Complementar</h2>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nome Fantasia</label>
                                <input type="text" name="nome_fantasia" maxlength="255"
                                    value="{{ old('nome_fantasia', $client->nome_fantasia) }}"
                                    placeholder="Nome fantasia (opcional)"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                                @error('nome_fantasia')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($client->tipo_pessoa === 'pj')
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Inscrição Estadual</label>
                                    <input type="text" name="inscricao_estadual" maxlength="255"
                                        value="{{ old('inscricao_estadual', $client->inscricao_estadual) }}"
                                        placeholder="Inscrição Estadual (opcional)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                                    @error('inscricao_estadual')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Inscrição Municipal</label>
                                    <input type="text" name="inscricao_municipal" maxlength="255"
                                        value="{{ old('inscricao_municipal', $client->inscricao_municipal) }}"
                                        placeholder="Inscrição Municipal (opcional)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                                    @error('inscricao_municipal')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            {{-- ===================== SEÇÃO: ENDEREÇO ===================== --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 px-1">
                    <span class="w-7 h-7 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Endereço</h2>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                CEP <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text" name="cep" required maxlength="9"
                                    x-model="cep"
                                    @blur="buscarCep()"
                                    placeholder="00000-000"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <button type="button" @click="buscarCep()" title="Buscar CEP"
                                    class="flex-shrink-0 px-3 rounded-lg border border-gray-300 text-gray-500 hover:text-sky-600 hover:border-sky-300 transition-colors">
                                    <svg x-show="!buscandoCep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                                    </svg>
                                    <svg x-show="buscandoCep" x-cloak class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </div>
                            @error('cep')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Logradouro <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="endereco" required maxlength="255"
                                x-model="endereco"
                                placeholder="Rua, Av..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            @error('endereco')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Número <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="numero" required maxlength="20"
                                x-model="numero"
                                placeholder="Nº"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            @error('numero')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Complemento
                            </label>
                            <input type="text" name="complemento" maxlength="255"
                                x-model="complemento"
                                placeholder="Apto, sala, bloco..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            @error('complemento')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Bairro <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="bairro" required maxlength="255"
                                x-model="bairro"
                                placeholder="Bairro"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            @error('bairro')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                UF <span class="text-red-500">*</span>
                            </label>
                            <select name="uf" required
                                x-model="uf"
                                @change="carregarCidades(false)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="">Selecione</option>
                                <template x-for="estado in ufs" :key="estado">
                                    <option :value="estado" x-text="estado" :selected="estado === uf"></option>
                                </template>
                            </select>
                            @error('uf')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Cidade <span class="text-red-500">*</span>
                            </label>
                            <select name="cidade" required
                                x-model="cidade"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="">Selecione</option>
                                <template x-for="c in cidades" :key="c">
                                    <option :value="c" x-text="c" :selected="c === cidade"></option>
                                </template>
                                <template x-if="cidade && ! cidades.includes(cidade)">
                                    <option :value="cidade" x-text="cidade" selected></option>
                                </template>
                            </select>
                            <p x-show="carregandoCidades" class="text-xs text-gray-400 mt-1">Carregando cidades...</p>
                            @error('cidade')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== SEÇÃO: DADOS DE CONTATO ===================== --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 px-1">
                    <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Dados de Contato</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Responsável pelas documentações --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3.5 bg-amber-50 border-b border-amber-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-sm font-semibold text-amber-800">Responsável pelas Documentações</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Nome <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="contato_nome" required maxlength="255"
                                    value="{{ old('contato_nome', $client->contato_nome) }}"
                                    placeholder="Nome completo"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                @error('contato_nome')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    E-mail <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" required maxlength="255"
                                    value="{{ old('email', $client->email) }}"
                                    placeholder="email@empresa.com"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                @error('email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Responsável pelo financeiro --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3.5 bg-emerald-50 border-b border-emerald-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-9c-1.11 0-2.08.402-2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-sm font-semibold text-emerald-800">Responsável pelo Financeiro</h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Nome <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="contato_financeiro_nome" required maxlength="255"
                                    value="{{ old('contato_financeiro_nome', $client->contato_financeiro_nome) }}"
                                    placeholder="Nome completo"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('contato_financeiro_nome')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    E-mail <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="contato_financeiro_email" required maxlength="255"
                                    value="{{ old('contato_financeiro_email', $client->contato_financeiro_email) }}"
                                    placeholder="financeiro@empresa.com"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('contato_financeiro_email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-400 mt-1">Este e-mail será usado para acessar a área financeira do Portal do Cliente.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== SEÇÃO: TELEFONES ===================== --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 px-1">
                    <span class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </span>
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Telefones</h2>
                </div>

                {{-- Painel: Adicionar / Editar --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">
                            <span x-show="editandoTelIndex < 0">Adicionar telefone</span>
                            <span x-show="editandoTelIndex >= 0">
                                ✏️ Editando telefone <span x-text="editandoTelIndex + 1"></span>
                            </span>
                        </h3>
                        <button
                            type="button"
                            x-show="editandoTelIndex >= 0"
                            @click="cancelarTelefone()"
                            class="text-xs text-gray-400 hover:text-gray-600">
                            Cancelar edição
                        </button>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Tipo <span class="text-red-500">*</span>
                                </label>
                                <select x-model="novoTelTipo"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                                    <option value="celular">📱 Celular</option>
                                    <option value="fixo">📞 Fixo</option>
                                    <option value="whatsapp">💬 WhatsApp</option>
                                    <option value="trabalho">🏢 Trabalho</option>
                                    <option value="recado">📝 Recado</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Número <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    :value="novoTelNumero"
                                    @input="novoTelNumero = maskTelefone($event.target.value); $event.target.value = novoTelNumero"
                                    @keypress="if (!/[0-9]/.test($event.key)) $event.preventDefault()"
                                    @paste="$event.preventDefault(); novoTelNumero = maskTelefone((($event.clipboardData || window.clipboardData).getData('text')))"
                                    placeholder="(00) 00000-0000"
                                    maxlength="15"
                                    inputmode="numeric"
                                    @keydown.enter.prevent="adicionarTelefone()"
                                    :class="erroTelNumero ? 'border-red-400' : 'border-gray-300'"
                                    class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500">
                                <p x-show="erroTelNumero" x-text="erroTelNumero" class="text-red-500 text-xs mt-1"></p>
                            </div>

                            <div class="flex items-end">
                                <button
                                    type="button"
                                    @click="adicionarTelefone()"
                                    :class="editandoTelIndex >= 0
                                        ? 'bg-amber-500 hover:bg-amber-600'
                                        : 'bg-rose-600 hover:bg-rose-700'"
                                    class="w-full py-2 px-4 rounded-lg text-sm font-semibold text-white transition-colors">
                                    <span x-text="editandoTelIndex >= 0 ? 'Salvar alteração' : '+ Adicionar'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Lista de telefones --}}
                <div
                    x-show="telefones.length > 0"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Telefones adicionados</h3>
                        <span class="text-xs bg-rose-100 text-rose-700 font-semibold px-2.5 py-0.5 rounded-full"
                            x-text="telefones.length"></span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <template x-for="(tel, i) in telefones" :key="i">
                            <div
                                :class="editandoTelIndex === i ? 'bg-amber-50' : ''"
                                class="flex items-center gap-3 px-5 py-3">

                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 text-gray-500
                                             text-xs font-bold flex items-center justify-center"
                                    x-text="i + 1"></span>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate" x-text="tel.numero"></p>
                                    <p class="text-xs text-gray-400 mt-0.5" x-text="tipoTelLabels[tel.tipo] ?? tel.tipo"></p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button type="button" @click="editarTelefone(i)" title="Editar"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                                   m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button" @click="removerTelefone(i)" title="Remover"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                                                   L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Erros (client-side e server-side) --}}
                <div x-show="erroTelLista" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    <p x-text="erroTelLista" class="text-red-600 text-sm"></p>
                </div>
                @if ($errors->has('telefones'))
                    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                        <p class="text-red-600 text-sm">{{ $errors->first('telefones') }}</p>
                    </div>
                @endif
            </section>

            {{-- Divisor entre seções --}}
            <div class="flex items-center gap-3 pt-2">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Colaboradores</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            {{-- ===================== SEÇÃO: COLABORADORES ===================== --}}
            <section class="space-y-4">
                <div class="flex items-center gap-2 px-1">
                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3c0 1.657-3.134 3-7 3s-7-1.343-7-3 3.134-3 7-3 7 1.343 7 3z"/>
                        </svg>
                    </span>
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Colaboradores</h2>
                </div>

                {{-- Painel: Adicionar / Editar --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">
                            <span x-show="editandoIndex < 0">Adicionar colaborador</span>
                            <span x-show="editandoIndex >= 0">
                                ✏️ Editando colaborador <span x-text="editandoIndex + 1"></span>
                            </span>
                        </h3>
                        <button
                            type="button"
                            x-show="editandoIndex >= 0"
                            @click="cancelar()"
                            class="text-xs text-gray-400 hover:text-gray-600">
                            Cancelar edição
                        </button>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                            {{-- Nome --}}
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Nome <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    x-model="novoNome"
                                    placeholder="Nome completo do colaborador"
                                    @keydown.enter.prevent="adicionar()"
                                    :class="erroNome ? 'border-red-400' : 'border-gray-300'"
                                    class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p x-show="erroNome" x-text="erroNome" class="text-red-500 text-xs mt-1"></p>
                            </div>

                            {{-- Telefone --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Telefone</label>
                                <input
                                    type="text"
                                    :value="novoTelefone"
                                    @input="novoTelefone = maskTelefone($event.target.value); $event.target.value = novoTelefone"
                                    @keypress="if (!/[0-9]/.test($event.key)) $event.preventDefault()"
                                    @paste="$event.preventDefault(); novoTelefone = maskTelefone((($event.clipboardData || window.clipboardData).getData('text')))"
                                    placeholder="(00) 00000-0000"
                                    maxlength="15"
                                    inputmode="numeric"
                                    @keydown.enter.prevent="adicionar()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            {{-- Local --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Local / Setor</label>
                                <input
                                    type="text"
                                    x-model="novoLocal"
                                    placeholder="Ex: RH, Vendas, Comercial"
                                    @keydown.enter.prevent="adicionar()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            {{-- Botão Adicionar / Salvar --}}
                            <div class="flex items-end">
                                <button
                                    type="button"
                                    @click="adicionar()"
                                    :class="editandoIndex >= 0
                                        ? 'bg-amber-500 hover:bg-amber-600'
                                        : 'bg-blue-600 hover:bg-blue-700'"
                                    class="w-full py-2 px-4 rounded-lg text-sm font-semibold text-white transition-colors">
                                    <span x-text="editandoIndex >= 0 ? 'Salvar alteração' : '+ Adicionar'"></span>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Lista de colaboradores --}}
                <div
                    x-show="colaboradores.length > 0"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Colaboradores adicionados</h3>
                        <span class="text-xs bg-blue-100 text-blue-700 font-semibold px-2.5 py-0.5 rounded-full"
                            x-text="colaboradores.length"></span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <template x-for="(col, i) in colaboradores" :key="i">
                            <div
                                :class="editandoIndex === i ? 'bg-amber-50' : ''"
                                class="flex items-center gap-3 px-5 py-3">

                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 text-gray-500
                                             text-xs font-bold flex items-center justify-center"
                                    x-text="i + 1"></span>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate" x-text="col.nome"></p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <span x-show="col.telefone" x-text="col.telefone"></span>
                                        <span x-show="col.telefone && col.local"> · </span>
                                        <span x-show="col.local" x-text="col.local"></span>
                                        <span x-show="!col.telefone && !col.local" class="italic">Sem telefone ou setor</span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button type="button" @click="editar(i)" title="Editar"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                                   m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button" @click="remover(i)" title="Remover"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                                                   L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Erro de lista vazia (client-side e server-side) --}}
                <div x-show="erroLista" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    <p x-text="erroLista" class="text-red-600 text-sm"></p>
                </div>
                @if ($errors->has('colaboradores'))
                    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                        <p class="text-red-600 text-sm">{{ $errors->first('colaboradores') }}</p>
                    </div>
                @endif
            </section>

            {{-- Envio --}}
            <button
                type="submit"
                :disabled="colaboradores.length === 0 || telefones.length === 0"
                :class="(colaboradores.length > 0 && telefones.length > 0)
                    ? 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white shadow-lg shadow-green-200/50'
                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                class="w-full py-3.5 rounded-xl font-semibold text-sm transition-all">
                <span x-show="colaboradores.length === 0 || telefones.length === 0">Enviar Cadastro</span>
                <span x-show="colaboradores.length > 0 && telefones.length > 0">
                    Enviar Cadastro
                    (<span x-text="colaboradores.length"></span>
                    <span x-text="colaboradores.length === 1 ? 'colaborador' : 'colaboradores'"></span>)
                </span>
            </button>
        </form>

    </div>
@endif

@endsection
