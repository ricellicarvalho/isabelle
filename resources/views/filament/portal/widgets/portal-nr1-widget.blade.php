@php
    $etapas = [
        'etapa1' => ['nome' => 'Encontro',                       'descricao' => 'Reunião inicial de alinhamento'],
        'etapa2' => ['nome' => 'Avaliação de Riscos',            'descricao' => 'Questionários e entrevistas'],
        'etapa3' => ['nome' => 'Relatório Diagnóstico',          'descricao' => 'Elaboração do DPRS'],
        'etapa4' => ['nome' => 'Matriz de Risco',                'descricao' => 'Segurança do Trabalho'],
        'etapa5' => ['nome' => 'Devolutiva',                     'descricao' => 'Apresentação dos resultados'],
    ];

    $totalConcluidas = collect(array_keys($etapas))->filter(fn($k) => !empty($checklist[$k]))->count();

    $barColor = match(true) {
        $progresso === 100 => '#16a34a',
        $progresso >= 60   => '#7c3aed',
        $progresso > 0     => '#d97706',
        default            => '#94a3b8',
    };

    $statusConfig = match($nr1Status) {
        'finalizada'   => ['bg' => '#dcfce7', 'color' => '#14532d', 'border' => '#4ade80',  'dot' => '#16a34a'],
        'regularizada' => ['bg' => '#d1fae5', 'color' => '#065f46', 'border' => '#34d399',  'dot' => '#059669'],
        'em_andamento' => ['bg' => '#fef3c7', 'color' => '#78350f', 'border' => '#fbbf24',  'dot' => '#d97706'],
        default        => ['bg' => '#f1f5f9', 'color' => '#334155', 'border' => '#94a3b8',  'dot' => '#64748b'],
    };
@endphp

<div style="background:white; border-radius:1rem; padding:1.5rem 1.75rem; box-shadow:0 1px 4px rgba(0,0,0,.07), 0 0 0 1px rgba(0,0,0,.04); overflow:hidden;">

    {{-- ── Cabeçalho ── --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:.75rem;">
        <div style="display:flex; align-items:center; gap:.75rem;">
            <div style="width:40px; height:40px; border-radius:.75rem; background:linear-gradient(135deg,#fef9c3,#fde68a); border:1px solid #fcd34d; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 1px 3px rgba(0,0,0,.08);">
                <svg style="width:22px; height:22px; color:#b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h3 style="font-size:1rem; font-weight:700; color:#0f172a; margin:0; line-height:1.25;">Conformidade NR-1</h3>
                <p style="font-size:.8125rem; color:#475569; margin:0;">Acompanhe as etapas do seu programa</p>
            </div>
        </div>

        {{-- Badge de status --}}
        <span style="display:inline-flex; align-items:center; gap:6px; background:{{ $statusConfig['bg'] }}; color:{{ $statusConfig['color'] }}; border:1.5px solid {{ $statusConfig['border'] }}; border-radius:9999px; padding:5px 14px; font-size:.8125rem; font-weight:700; letter-spacing:.01em;">
            <span style="width:7px; height:7px; border-radius:50%; background:{{ $statusConfig['dot'] }}; display:inline-block; flex-shrink:0;"></span>
            {{ $nr1Label }}
        </span>
    </div>

    {{-- ── Barra de progresso ── --}}
    <div style="margin-bottom:1.75rem; background:#f8fafc; border-radius:.75rem; padding:.875rem 1rem; border:1px solid #e2e8f0;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.625rem;">
            <span style="font-size:.8125rem; color:#475569; font-weight:600;">Progresso geral</span>
            <div style="display:flex; align-items:baseline; gap:5px;">
                <span style="font-size:1.5rem; font-weight:800; color:{{ $barColor }}; line-height:1; letter-spacing:-.02em;">{{ $progresso }}%</span>
                <span style="font-size:.75rem; color:#64748b; font-weight:500;">· {{ $totalConcluidas }}/5 etapas</span>
            </div>
        </div>
        <div style="width:100%; height:8px; background:#e2e8f0; border-radius:9999px; overflow:hidden;">
            <div style="width:{{ $progresso }}%; height:100%; background:{{ $barColor }}; border-radius:9999px; transition:width .6s cubic-bezier(.4,0,.2,1);
                        box-shadow: {{ $progresso > 0 ? '0 0 8px ' . $barColor . '66' : 'none' }};"></div>
        </div>
    </div>

    {{-- ── Grade de etapas ── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:.875rem;">
        @foreach ($etapas as $key => $etapa)
            @php
                $concluida = !empty($checklist[$key]);
                $data      = $checklist[$key . '_data'] ?? null;
                $tipo      = $checklist[$key . '_tipo'] ?? null;
                $num       = $loop->iteration;
            @endphp

            @if ($concluida)
                {{-- ── CARD CONCLUÍDO ── --}}
                <div style="
                    background: linear-gradient(145deg, #f0fdf4, #dcfce7);
                    border: 1.5px solid #4ade80;
                    border-radius: .875rem;
                    padding: 1rem;
                    display: flex; flex-direction: column; gap: .5rem;
                    box-shadow: 0 1px 3px rgba(22,163,74,.12);
                ">
                    {{-- Número + badge --}}
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:.5rem;">
                            <div style="
                                width:28px; height:28px; border-radius:50%;
                                background:linear-gradient(135deg,#16a34a,#15803d);
                                display:flex; align-items:center; justify-content:center;
                                flex-shrink:0; box-shadow:0 1px 4px rgba(22,163,74,.35);
                            ">
                                <svg style="width:14px; height:14px; color:white;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span style="font-size:.6875rem; font-weight:700; color:#166534; text-transform:uppercase; letter-spacing:.06em;">Etapa {{ $num }}</span>
                        </div>
                        <span style="font-size:.625rem; font-weight:700; background:#bbf7d0; color:#14532d; border-radius:9999px; padding:2px 8px; border:1px solid #86efac;">✓ Concluída</span>
                    </div>

                    {{-- Nome --}}
                    <p style="font-size:.875rem; font-weight:700; color:#14532d; margin:0; line-height:1.3;">
                        {{ $etapa['nome'] }}
                    </p>

                    {{-- Descrição --}}
                    <p style="font-size:.75rem; font-weight:500; color:#166534; margin:0; line-height:1.5;">
                        {{ $etapa['descricao'] }}
                    </p>

                    {{-- Footer com data/tipo --}}
                    @if ($data || $tipo)
                        <div style="margin-top:auto; padding-top:.5rem; border-top:1px solid #86efac; display:flex; flex-wrap:wrap; align-items:center; gap:.375rem;">
                            @if ($tipo)
                                <span style="font-size:.6875rem; font-weight:600; background:#dcfce7; color:#15803d; border:1px solid #86efac; border-radius:9999px; padding:2px 8px;">{{ ucfirst($tipo) }}</span>
                            @endif
                            @if ($data)
                                <span style="font-size:.6875rem; font-weight:600; color:#15803d;">
                                    <svg style="width:11px;height:11px;display:inline;vertical-align:-.1em;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                    @else
                        <div style="margin-top:auto; padding-top:.5rem; border-top:1px solid #86efac;">
                            <span style="font-size:.6875rem; font-weight:600; color:#15803d;">✓ Realizada</span>
                        </div>
                    @endif
                </div>

            @else
                {{-- ── CARD PENDENTE ── --}}
                <div style="
                    background: white;
                    border: 1.5px dashed #94a3b8;
                    border-radius: .875rem;
                    padding: 1rem;
                    display: flex; flex-direction: column; gap: .5rem;
                    opacity: .95;
                ">
                    {{-- Número + badge --}}
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:.5rem;">
                            <div style="
                                width:28px; height:28px; border-radius:50%;
                                background:#f1f5f9;
                                border:2px solid #cbd5e1;
                                display:flex; align-items:center; justify-content:center;
                                flex-shrink:0;
                            ">
                                <span style="font-size:.75rem; font-weight:800; color:#334155;">{{ $num }}</span>
                            </div>
                            <span style="font-size:.6875rem; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.06em;">Etapa {{ $num }}</span>
                        </div>
                        <span style="font-size:.625rem; font-weight:700; background:#fef9c3; color:#78350f; border-radius:9999px; padding:2px 8px; border:1px solid #fde047;">Pendente</span>
                    </div>

                    {{-- Nome --}}
                    <p style="font-size:.875rem; font-weight:700; color:#1e293b; margin:0; line-height:1.3;">
                        {{ $etapa['nome'] }}
                    </p>

                    {{-- Descrição --}}
                    <p style="font-size:.75rem; font-weight:500; color:#475569; margin:0; line-height:1.5;">
                        {{ $etapa['descricao'] }}
                    </p>

                    {{-- Footer aguardando --}}
                    <div style="margin-top:auto; padding-top:.5rem; border-top:1px solid #e2e8f0; display:flex; align-items:center; gap:5px;">
                        <svg style="width:12px; height:12px; color:#94a3b8; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span style="font-size:.6875rem; font-weight:600; color:#64748b;">Pendente</span>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

</div>
