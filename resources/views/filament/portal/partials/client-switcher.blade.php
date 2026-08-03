@php
    use App\Support\PortalAccess;
    use Illuminate\Support\Facades\Auth;

    $userId = Auth::guard('portal')->id();
    $clients = $userId ? PortalAccess::clients($userId) : collect();
    $selectedClient = $userId ? PortalAccess::client($userId) : null;
@endphp

@if ($clients->count() > 1 && $selectedClient)
    <div style="margin-left:-.5rem; margin-right:-.5rem; padding:.625rem 0 1.125rem;">
        <form method="POST" action="{{ route('portal.select-client') }}">
            @csrf
            <label for="portal-client-switcher" style="display:flex; width:100%; align-items:center; gap:.5rem; font-size:.875rem; font-weight:900; color:#b45309; text-transform:uppercase; letter-spacing:.035em; margin-bottom:.5rem; padding-left:.5rem; padding-right:.5rem;">
                <span style="width:1.75rem; height:1.75rem; border-radius:.5rem; background:#fef3c7; border:1px solid #f59e0b; display:inline-flex; align-items:center; justify-content:center; color:#b45309; flex-shrink:0;">
                    <svg style="width:1rem; height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 21h16.5M4.5 21V5.25A2.25 2.25 0 016.75 3h10.5a2.25 2.25 0 012.25 2.25V21M9 7.5h1.5M9 11.25h1.5M9 15h1.5m3-7.5H15m-1.5 3.75H15M13.5 15H15" />
                    </svg>
                </span>
                Empresa
            </label>

            <div style="position:relative;">
                <select
                    id="portal-client-switcher"
                    name="client_id"
                    onchange="this.form.submit()"
                    style="appearance:none; -webkit-appearance:none; width:100%; border:1.5px solid #f59e0b; border-radius:.625rem; background:#fffbeb; color:#111827; font-size:.875rem; font-weight:800; line-height:1.25; padding:.7rem 2.35rem .7rem .75rem; box-shadow:0 1px 3px rgba(180,83,9,.16), 0 0 0 3px rgba(245,158,11,.12); cursor:pointer;"
                >
                    @foreach ($clients as $client)
                        @php
                            $name = $client->nome_fantasia ?: $client->razao_social;
                            $document = $client->cnpj_cpf ? ' - ' . $client->cnpj_cpf : '';
                        @endphp
                        <option value="{{ $client->id }}" @selected($client->id === $selectedClient->id)>
                            {{ $name }}{{ $document }}
                        </option>
                    @endforeach
                </select>
                <span style="pointer-events:none; position:absolute; right:.75rem; top:50%; transform:translateY(-50%); width:1.5rem; height:1.5rem; border-radius:.375rem; background:#f59e0b; color:#fff; display:inline-flex; align-items:center; justify-content:center;">
                    <svg style="width:.9rem; height:.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 9l6 6 6-6" />
                    </svg>
                </span>
            </div>
        </form>
    </div>
@endif
