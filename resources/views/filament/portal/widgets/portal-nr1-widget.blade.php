@php
    $etapas = [
        'etapa1' => ['nome' => 'Encontro', 'descricao' => 'Reunião inicial de alinhamento'],
        'etapa2' => ['nome' => 'Avaliação de Riscos Psicossociais', 'descricao' => 'Aplicação de questionários e entrevistas'],
        'etapa3' => ['nome' => 'Relatório Diagnóstico (DPRS)', 'descricao' => 'Elaboração do diagnóstico organizacional'],
        'etapa4' => ['nome' => 'Matriz de Risco', 'descricao' => 'Segurança do Trabalho e plano de ação'],
        'etapa5' => ['nome' => 'Devolutiva', 'descricao' => 'Apresentação dos resultados e recomendações'],
    ];

    $barColor = match(true) {
        $progresso === 100 => '#22c55e',
        $progresso >= 60   => '#8b5cf6',
        $progresso > 0     => '#f59e0b',
        default            => '#e5e7eb',
    };

    $badgeBg = match($nr1Status) {
        'finalizada'   => '#dcfce7',
        'regularizada' => '#d1fae5',
        'em_andamento' => '#fef9c3',
        default        => '#fee2e2',
    };
    $badgeColor = match($nr1Status) {
        'finalizada', 'regularizada' => '#15803d',
        'em_andamento'               => '#854d0e',
        default                      => '#b91c1c',
    };
    $badgeBorder = match($nr1Status) {
        'finalizada', 'regularizada' => '#86efac',
        'em_andamento'               => '#fde047',
        default                      => '#fca5a5',
    };
@endphp

<div style="background:white; border-radius:1rem; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #f1f5f9;">

    {{-- Cabeçalho --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem;">
        <div style="display:flex; align-items:center; gap:.625rem;">
            <div style="width:36px; height:36px; border-radius:.625rem; background:#fffbeb; border:1px solid #fde68a; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg style="width:20px; height:20px; color:#d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 style="font-size:.9375rem; font-weight:700; color:#111827; margin:0; line-height:1.2;">Conformidade NR-1</h3>
                <p style="font-size:.75rem; color:#6b7280; margin:0;">Acompanhe as etapas do seu programa</p>
            </div>
        </div>

        <span style="display:inline-flex; align-items:center; gap:5px; background:{{ $badgeBg }}; color:{{ $badgeColor }}; border:1px solid {{ $badgeBorder }}; border-radius:9999px; padding:4px 12px; font-size:.75rem; font-weight:600;">
            <span style="width:6px; height:6px; border-radius:50%; background:{{ $badgeColor }}; display:inline-block;"></span>
            {{ $nr1Label }}
        </span>
    </div>

    {{-- Barra de progresso --}}
    <div style="margin-bottom:1.5rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem;">
            <span style="font-size:.8125rem; color:#6b7280; font-weight:500;">Progresso geral</span>
            <div style="display:flex; align-items:baseline; gap:4px;">
                <span style="font-size:1.375rem; font-weight:800; color:{{ $barColor }}; line-height:1;">{{ $progresso }}%</span>
                <span style="font-size:.75rem; color:#9ca3af;">{{ collect(array_keys($etapas))->filter(fn($k) => !empty($checklist[$k]))->count() }} de 5 etapas</span>
            </div>
        </div>
        <div style="width:100%; height:10px; background:#f1f5f9; border-radius:9999px; overflow:hidden;">
            <div style="width:{{ $progresso }}%; height:100%; background:{{ $barColor }}; border-radius:9999px; transition:width .5s ease;"></div>
        </div>
    </div>

    {{-- Etapas --}}
    <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:.75rem;">
        @foreach ($etapas as $key => $etapa)
            @php
                $concluida = !empty($checklist[$key]);
                $data      = $checklist[$key . '_data'] ?? null;
                $tipo      = $checklist[$key . '_tipo'] ?? null;
                $num       = $loop->iteration;

                $cardBg     = $concluida ? '#f0fdf4' : '#f9fafb';
                $cardBorder = $concluida ? '#86efac' : '#e5e7eb';
                $numBg      = $concluida ? '#22c55e' : '#e5e7eb';
                $numColor   = $concluida ? 'white'   : '#9ca3af';
                $titleColor = $concluida ? '#166534' : '#374151';
                $descColor  = $concluida ? '#4ade80' : '#9ca3af';
            @endphp
            <div style="background:{{ $cardBg }}; border:1px solid {{ $cardBorder }}; border-radius:.75rem; padding:.875rem; display:flex; flex-direction:column; gap:.5rem; transition:all .2s;">

                {{-- Número + check --}}
                <div style="display:flex; align-items:center; gap:.5rem;">
                    <div style="width:26px; height:26px; border-radius:50%; background:{{ $numBg }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        @if ($concluida)
                            <svg style="width:13px; height:13px; color:white;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <span style="font-size:.6875rem; font-weight:700; color:{{ $numColor }};">{{ $num }}</span>
                        @endif
                    </div>
                    <span style="font-size:.6875rem; font-weight:600; color:{{ $concluida ? '#15803d' : '#9ca3af' }}; text-transform:uppercase; letter-spacing:.05em;">
                        Etapa {{ $num }}
                    </span>
                </div>

                {{-- Nome --}}
                <p style="font-size:.8125rem; font-weight:600; color:{{ $titleColor }}; margin:0; line-height:1.3;">
                    {{ $etapa['nome'] }}
                </p>

                {{-- Descrição --}}
                <p style="font-size:.6875rem; color:{{ $descColor }}; margin:0; line-height:1.4;">
                    {{ $etapa['descricao'] }}
                </p>

                {{-- Data / tipo (se concluída) --}}
                @if ($concluida && ($data || $tipo))
                    <div style="margin-top:auto; padding-top:.375rem; border-top:1px solid #bbf7d0; display:flex; flex-direction:column; gap:2px;">
                        @if ($tipo)
                            <span style="font-size:.6875rem; color:#16a34a; font-weight:500;">
                                {{ ucfirst($tipo) }}
                            </span>
                        @endif
                        @if ($data)
                            <span style="font-size:.6875rem; color:#22c55e;">
                                {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>
                @elseif (!$concluida)
                    <div style="margin-top:auto; padding-top:.375rem; border-top:1px solid #f1f5f9;">
                        <span style="font-size:.6875rem; color:#d1d5db; font-style:italic;">Pendente</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

</div>
