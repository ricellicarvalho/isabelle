{{-- Token inválido ou expirado --}}
@if ($tokenInvalido)
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
@elseif ($cadastroJaFeito)
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
            Caso precise corrigir, entre em contato com a empresa.
        </p>
    </div>

{{-- Enviado com sucesso --}}
@elseif ($enviado)
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

{{-- Formulário principal --}}
@else
    <div class="space-y-4">

        {{-- Cabeçalho --}}
        <div class="bg-blue-600 rounded-2xl px-6 py-5">
            <h1 class="text-lg font-bold text-white">Pré-Cadastro de Colaboradores</h1>
            <p class="text-blue-100 text-sm mt-0.5">{{ $client->razao_social }}</p>
        </div>

        {{-- Painel: Adicionar / Editar colaborador --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700">
                    @if ($editandoIndex >= 0)
                        ✏️ Editando colaborador {{ $editandoIndex + 1 }}
                    @else
                        Adicionar colaborador
                    @endif
                </h2>
                @if ($editandoIndex >= 0)
                    <button type="button" wire:click="cancelarEdicao"
                        class="text-xs text-gray-400 hover:text-gray-600">
                        Cancelar edição
                    </button>
                @endif
            </div>

            <form wire:submit="adicionarOuAtualizar" class="p-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                    {{-- Nome --}}
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Nome <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="novoNome"
                            placeholder="Nome completo do colaborador"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                   {{ $errors->has('novoNome') ? 'border-red-400' : 'border-gray-300' }}">
                        @error('novoNome')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Telefone --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Telefone</label>
                        <input type="text" wire:model="novoTelefone"
                            placeholder="(00) 00000-0000"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Local --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Local / Setor</label>
                        <input type="text" wire:model="novoLocal"
                            placeholder="Ex: RH, Produção, TI"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Botão --}}
                    <div class="flex items-end">
                        <button type="submit"
                            wire:loading.attr="disabled" wire:target="adicionarOuAtualizar"
                            class="w-full py-2 px-4 rounded-lg text-sm font-semibold text-white transition-colors
                                   {{ $editandoIndex >= 0
                                       ? 'bg-amber-500 hover:bg-amber-600'
                                       : 'bg-blue-600 hover:bg-blue-700' }}">
                            <span wire:loading.remove wire:target="adicionarOuAtualizar">
                                {{ $editandoIndex >= 0 ? 'Salvar alteração' : '+ Adicionar' }}
                            </span>
                            <span wire:loading wire:target="adicionarOuAtualizar">Aguarde...</span>
                        </button>
                    </div>

                </div>
            </form>
        </div>

        {{-- Painel: Lista de colaboradores adicionados --}}
        @if (count($colaboradores) > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">
                        Colaboradores adicionados
                    </h2>
                    <span class="text-xs bg-blue-100 text-blue-700 font-semibold px-2.5 py-0.5 rounded-full">
                        {{ count($colaboradores) }}
                    </span>
                </div>

                <div class="divide-y divide-gray-100">
                    @foreach ($colaboradores as $i => $col)
                        <div wire:key="col-{{ $i }}"
                            class="flex items-center gap-3 px-5 py-3
                                   {{ $editandoIndex === $i ? 'bg-amber-50' : '' }}">

                            {{-- Número --}}
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 text-gray-500
                                         text-xs font-bold flex items-center justify-center">
                                {{ $i + 1 }}
                            </span>

                            {{-- Dados --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    {{ $col['nome'] }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    @if ($col['telefone'])
                                        {{ $col['telefone'] }}
                                        @if ($col['local']) &nbsp;·&nbsp; @endif
                                    @endif
                                    @if ($col['local'])
                                        {{ $col['local'] }}
                                    @endif
                                    @if (! $col['telefone'] && ! $col['local'])
                                        <span class="italic">Sem telefone ou setor</span>
                                    @endif
                                </p>
                            </div>

                            {{-- Ações --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button" wire:click="editarColaborador({{ $i }})"
                                    title="Editar"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                               m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button type="button" wire:click="removerColaborador({{ $i }})"
                                    title="Remover"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                                               L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Erro de validação do envio --}}
        @error('colaboradores')
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                <p class="text-red-600 text-sm">{{ $message }}</p>
            </div>
        @enderror

        {{-- Botão Enviar --}}
        <button
            type="button"
            wire:click="salvar"
            wire:loading.attr="disabled" wire:target="salvar"
            wire:loading.class="opacity-75 cursor-not-allowed" wire:target="salvar"
            @if (count($colaboradores) === 0) disabled @endif
            class="w-full py-3 rounded-xl font-semibold text-sm transition-colors
                   {{ count($colaboradores) > 0
                       ? 'bg-green-600 hover:bg-green-700 text-white'
                       : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
            <span wire:loading.remove wire:target="salvar">
                Enviar Cadastro
                @if (count($colaboradores) > 0)
                    ({{ count($colaboradores) }} {{ count($colaboradores) === 1 ? 'colaborador' : 'colaboradores' }})
                @endif
            </span>
            <span wire:loading wire:target="salvar">Enviando...</span>
        </button>

    </div>
@endif
