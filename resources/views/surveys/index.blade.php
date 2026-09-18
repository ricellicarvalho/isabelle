@extends('layouts.public-survey')
@section('title', 'Avaliações disponíveis')
@section('survey-hero')
<div class="survey-hero">
    <div class="hero-glow hero-glow-one"></div><div class="hero-glow hero-glow-two"></div>
    <div class="survey-hero-content">
        <span class="survey-overline">✦ Sua opinião transforma</span>
        <h1>Avaliações disponíveis</h1>
        <p>Compartilhe sua experiência. Cada resposta nos ajuda a construir serviços, atendimentos e relações cada vez melhores.</p>
    </div>
    <div class="survey-hero-mark" aria-hidden="true"><img src="{{ asset('images/logo.png') }}" alt=""><small>Escuta que gera evolução</small></div>
</div>
@endsection
@section('content')
<div class="card survey-list-card">
    <div class="survey-list-heading"><span>Pesquisas abertas</span><strong>{{ $surveys->count() }}</strong></div>
    @forelse($surveys as $survey)
        <article class="public-survey-item">
            <div class="public-survey-icon">✓</div>
            <div class="public-survey-copy"><h2>{{ $survey->description }}</h2>
            @if($survey->introduction)<p class="muted">{{ $survey->introduction }}</p>@endif
            <small>Disponível até {{ $survey->ends_at->format('d/m/Y \à\s H:i') }}</small></div>
            <a class="btn" href="{{ route('public.surveys.show', $survey) }}">Responder <span aria-hidden="true">→</span></a>
        </article>
    @empty
        <div class="survey-empty"><b>✓</b><h2>Tudo respondido por aqui</h2><p>Nenhuma avaliação está disponível neste momento.</p></div>
    @endforelse
</div>
@endsection
