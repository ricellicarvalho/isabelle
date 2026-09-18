@extends('layouts.public-survey')
@section('title', 'Avaliação enviada')
@section('content')
<div class="card" style="text-align:center;padding:48px 28px">
    <div class="success-icon">✓</div>
    <h1>Obrigado pela sua participação!</h1>
    <p class="muted">{{ $survey->thank_you_message ?: 'Sua resposta foi registrada com sucesso.' }}</p>
    <p class="redirect">Esta página será redirecionada para as avaliações disponíveis em 4 segundos. Ou <a href="{{ route('public.surveys.index') }}">clique aqui para continuar</a>.</p>
</div>
<script>window.setTimeout(() => window.location.assign(@js(route('public.surveys.index'))), 4000);</script>
@endsection
