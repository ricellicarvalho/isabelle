<?php

namespace App\Services;

use App\Enums\SurveyResponseType;
use App\Models\SatisfactionSurvey;
use Illuminate\Support\Carbon;

class SurveyResultsService
{
    public static function summarize(SatisfactionSurvey $survey): array
    {
        $survey->load(['questions' => fn ($query) => $query->withCount('answers')->withAvg('answers', 'numeric_value')->with('answers:id,question_id,numeric_value')]);
        $answers = $survey->submissions()->join('satisfaction_answers', 'satisfaction_submissions.id', '=', 'satisfaction_answers.submission_id');
        $count = (clone $answers)->count();
        $average = $count ? round((float) (clone $answers)->avg('numeric_value'), 2) : null;
        $distributionRows = (clone $answers)->selectRaw('numeric_value, count(*) as total')->groupBy('numeric_value')->pluck('total', 'numeric_value');
        $range = range($survey->response_type->min(), $survey->response_type->max());
        $distribution = collect($range)->mapWithKeys(fn (int $value) => [$value => (int) ($distributionRows[$value] ?? 0)])->all();

        $positiveThreshold = $survey->response_type === SurveyResponseType::Numeric ? 9 : 4;
        $negativeThreshold = $survey->response_type === SurveyResponseType::Numeric ? 6 : 2;
        $positive = $count ? round((clone $answers)->where('numeric_value', '>=', $positiveThreshold)->count() * 100 / $count, 1) : 0;
        $negative = $count ? round((clone $answers)->where('numeric_value', '<=', $negativeThreshold)->count() * 100 / $count, 1) : 0;
        $neutral = max(0, round(100 - $positive - $negative, 1));
        $values = (clone $answers)->orderBy('numeric_value')->pluck('numeric_value');
        $median = $values->isEmpty() ? null : round((float) $values->median(), 2);
        $submissions = $survey->submissions()->orderBy('submitted_at')->get(['submitted_at']);
        $trend = $submissions->groupBy(fn ($submission) => $submission->submitted_at->format('Y-m-d'))
            ->map(fn ($items, string $date) => [
                'date' => Carbon::parse($date)->format('d/m'),
                'total' => $items->count(),
            ])->values()->all();
        $expectedAnswers = $survey->questions->where('is_visible', true)->count() * $submissions->count();
        $completion = $expectedAnswers ? round($count * 100 / $expectedAnswers, 1) : 0;

        $questions = $survey->questions->map(function ($question) use ($range): array {
            $composition = $question->answers->countBy('numeric_value');

            return [
                'description' => $question->description,
                'average' => $question->answers_avg_numeric_value !== null ? round((float) $question->answers_avg_numeric_value, 2) : null,
                'answers' => $question->answers_count,
                'composition' => collect($range)->mapWithKeys(fn (int $value) => [$value => (int) ($composition[$value] ?? 0)])->all(),
            ];
        })->all();

        $ranked = collect($questions)->whereNotNull('average')->sortByDesc('average');

        return [
            'submissions' => $submissions->count(),
            'answers' => $count,
            'average' => $average,
            'median' => $median,
            'positive' => $positive,
            'neutral' => $neutral,
            'negative' => $negative,
            'completion' => $completion,
            'last_response' => $submissions->last()?->submitted_at?->format('d/m/Y H:i'),
            'distribution' => $distribution,
            'questions' => $questions,
            'best_question' => $ranked->first(),
            'worst_question' => $ranked->last(),
            'trend' => $trend,
        ];
    }
}
