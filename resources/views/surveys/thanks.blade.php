@extends('layouts.public-survey')
@section('title', 'Avaliação enviada')
@section('content')
<div class="card" style="text-align:center;padding:48px 28px">
    <div class="success-icon">✓</div>
    <h1>Obrigado pela sua participação!</h1>
    <p class="muted">{{ $survey->thank_you_message ?: 'Sua resposta foi registrada com sucesso.' }}</p>
    <p class="redirect">Esta página será redirecionada para as avaliações disponíveis em <span id="redirect-countdown" aria-live="off">10</span> segundos. Ou <a href="{{ route('public.surveys.index') }}">clique aqui para continuar</a>.</p>
</div>
<script>
    (() => {
        const countdown = document.getElementById('redirect-countdown');
        const destination = @js(route('public.surveys.index'));
        const deadline = Date.now() + 10000;
        const timer = window.setInterval(() => {
            const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
            countdown.textContent = remaining;
            if (remaining === 0) {
                window.clearInterval(timer);
                window.location.assign(destination);
            }
        }, 100);
    })();
</script>
@endsection
