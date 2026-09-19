<?php

namespace App\Http\Controllers;

use App\Models\SatisfactionSubmission;
use App\Models\SatisfactionSurvey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicSurveyController extends Controller
{
    public function index(): View
    {
        return view('surveys.index', [
            'surveys' => SatisfactionSurvey::published()->orderBy('ends_at')->get(),
        ]);
    }

    public function show(SatisfactionSurvey $survey): View
    {
        abort_unless($survey->isPublished(), 404);

        return view('surveys.show', [
            'survey' => $survey->load(['questions' => fn ($query) => $query->where('is_visible', true)]),
            'alreadySubmitted' => session()->has("survey_submitted.{$survey->id}"),
        ]);
    }

    public function store(Request $request, SatisfactionSurvey $survey): RedirectResponse
    {
        abort_unless($survey->isPublished(), 404);

        if (! $survey->allow_multiple_submissions && session()->has("survey_submitted.{$survey->id}")) {
            return back()->withErrors(['survey' => 'Esta avaliação já foi respondida neste navegador.']);
        }

        $questions = $survey->questions()->where('is_visible', true)->get();
        $rules = [];
        foreach ($questions as $question) {
            $rules["answers.{$question->id}"] = $question->answer_type === 'text' && $survey->response_type->value === 'numeric_0_10'
                ? [$question->is_required ? 'required' : 'nullable', 'string', 'max:5000']
                : [$question->is_required ? 'required' : 'nullable', 'integer', Rule::in(range($survey->response_type->min(), $survey->response_type->max()))];
        }
        $validated = $request->validate($rules, [], ['answers.*' => 'resposta']);

        DB::transaction(function () use ($request, $survey, $questions, $validated): void {
            $fingerprint = hash('sha256', implode('|', [
                $request->ip(),
                (string) $request->userAgent(),
                (string) $survey->id,
            ]));

            $submission = new SatisfactionSubmission([
                'public_identifier' => (string) Str::uuid(),
                'respondent_fingerprint_hash' => $fingerprint,
                'ip_hash' => hash('sha256', (string) $request->ip()),
                'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
                'submitted_at' => now(),
            ]);
            $survey->submissions()->save($submission);

            foreach ($questions as $question) {
                $value = data_get($validated, "answers.{$question->id}");
                if ($value === null || $value === '') {
                    continue;
                }

                $submission->answers()->create([
                    'question_id' => $question->id,
                    'numeric_value' => $question->answer_type === 'text' ? null : $value,
                    'text_value' => $question->answer_type === 'text' ? trim($value) : null,
                    'question_snapshot' => $question->description,
                    'response_type_snapshot' => $survey->response_type->value,
                ]);
            }
        });

        $request->session()->put("survey_submitted.{$survey->id}", true);

        return redirect()->route('public.surveys.thanks', $survey);
    }

    public function thanks(SatisfactionSurvey $survey): View
    {
        return view('surveys.thanks', compact('survey'));
    }
}
