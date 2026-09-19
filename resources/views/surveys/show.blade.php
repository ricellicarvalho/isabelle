@extends('layouts.public-survey')
@section('title', $survey->description)
@section('survey-hero')
<div class="survey-hero">
    <div class="hero-glow hero-glow-one"></div><div class="hero-glow hero-glow-two"></div>
    <div class="survey-hero-content">
        <span class="survey-overline">✦ Pesquisa de satisfação</span>
        <h1>{{ $survey->description }}</h1>
        <p>{{ $survey->introduction ?: 'Sua opinião é essencial para aprimorarmos continuamente nossos serviços e nosso atendimento.' }}</p>
    </div>
    <div class="survey-hero-mark" aria-hidden="true">
        <img src="{{ asset('images/logo.png') }}" alt="">
        <small>Sua opinião transforma</small>
    </div>
</div>
@endsection
@section('content')
<div class="card">
    @if($alreadySubmitted && ! $survey->allow_multiple_submissions)
        <div class="alert">Esta avaliação já foi respondida neste navegador.</div>
        <div class="form-footer"><a class="btn" href="{{ route('public.surveys.index') }}">Voltar</a></div>
    @else
    <form method="post" action="{{ route('public.surveys.store', $survey) }}">
        @csrf
        @foreach($survey->questions as $question)
        <fieldset class="question">
            <legend><strong>{{ $question->description }}</strong>@if($question->is_required) <span class="required-mark" title="Pergunta obrigatória">*</span> @endif</legend>
            <div class="options">
            @if($question->answer_type === 'text' && $survey->response_type === \App\Enums\SurveyResponseType::Numeric)
                <textarea name="answers[{{ $question->id }}]" rows="4" maxlength="5000" aria-label="{{ $question->description }}" @if($question->is_required) required @endif style="width:100%;padding:.85rem;border:1px solid #cbd5e1;border-radius:.65rem;font:inherit;resize:vertical">{{ old("answers.{$question->id}") }}</textarea>
            @elseif($survey->response_type === \App\Enums\SurveyResponseType::Numeric)
                <div class="numeric-scale" role="radiogroup" aria-label="Escala de 0 a 10">
                @foreach(range(0, 10) as $value)
                <label class="numeric-option"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $value }}" @checked(old("answers.{$question->id}") !== null && (int)old("answers.{$question->id}") === $value)><span>{{ $value }}</span></label>
                @endforeach
                <div class="numeric-colors" aria-hidden="true"><i></i><i></i><i></i></div>
                </div>
            @else
                @foreach([1 => ['😠','Péssimo'], 2 => ['🙁','Ruim'], 3 => ['😐','Regular'], 4 => ['🙂','Bom'], 5 => ['😄','Excelente']] as $value => [$emoji,$label])
                <label class="option"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $value }}" @checked((int)old("answers.{$question->id}") === $value)><span><b class="emoji" aria-hidden="true">{{ $emoji }}</b>{{ $label }}</span></label>
                @endforeach
            @endif
            </div>
        </fieldset>
        @endforeach
        <div class="form-footer">
            @if($errors->any())<div class="alert">Por favor, responda a todas as perguntas.</div>@endif
            <button class="btn" type="submit">Enviar respostas</button>
        </div>
    </form>
    @endif
</div>
@endsection
