<?php

namespace Tests\Feature;

use App\Enums\SurveyResponseType;
use App\Models\SatisfactionSurvey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSatisfactionSurveyTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_survey_is_available_without_login(): void
    {
        $survey = $this->survey();
        $survey->questions()->create(['description' => 'Como você avalia o atendimento?', 'display_order' => 1]);

        $this->get(route('public.surveys.show', $survey))
            ->assertOk()
            ->assertSee('Como você avalia o atendimento?');
    }

    public function test_inactive_or_out_of_period_survey_is_not_available(): void
    {
        $survey = $this->survey(['is_active' => false]);
        $survey->questions()->create(['description' => 'Pergunta', 'display_order' => 1]);

        $this->get(route('public.surveys.show', $survey))->assertNotFound();
    }

    public function test_numeric_answers_are_stored_with_snapshot(): void
    {
        $survey = $this->survey();
        $question = $survey->questions()->create(['description' => 'Nota geral', 'display_order' => 1]);

        $this->post(route('public.surveys.store', $survey), [
            'answers' => [$question->id => 9],
        ])->assertRedirect(route('public.surveys.thanks', $survey));

        $this->assertDatabaseHas('satisfaction_answers', [
            'question_id' => $question->id,
            'numeric_value' => 9,
            'question_snapshot' => 'Nota geral',
            'response_type_snapshot' => SurveyResponseType::Numeric->value,
        ]);
    }

    public function test_answer_outside_the_configured_scale_is_rejected(): void
    {
        $survey = $this->survey(['response_type' => SurveyResponseType::Qualitative]);
        $question = $survey->questions()->create(['description' => 'Qualidade', 'display_order' => 1]);

        $this->post(route('public.surveys.store', $survey), [
            'answers' => [$question->id => 10],
        ])->assertSessionHasErrors("answers.{$question->id}");

        $this->assertDatabaseCount('satisfaction_submissions', 0);
    }

    public function test_hidden_questions_cannot_be_injected_into_submission(): void
    {
        $survey = $this->survey();
        $visible = $survey->questions()->create(['description' => 'Visível', 'display_order' => 1]);
        $hidden = $survey->questions()->create(['description' => 'Oculta', 'display_order' => 2, 'is_visible' => false]);

        $this->post(route('public.surveys.store', $survey), [
            'answers' => [$visible->id => 8, $hidden->id => 10],
        ])->assertRedirect();

        $this->assertDatabaseHas('satisfaction_answers', ['question_id' => $visible->id]);
        $this->assertDatabaseMissing('satisfaction_answers', ['question_id' => $hidden->id]);
    }

    public function test_optional_questions_may_be_left_unanswered(): void
    {
        $survey = $this->survey();
        $required = $survey->questions()->create(['description' => 'Obrigatória', 'display_order' => 1]);
        $optional = $survey->questions()->create([
            'description' => 'Opcional',
            'display_order' => 2,
            'is_required' => false,
        ]);

        $this->post(route('public.surveys.store', $survey), [
            'answers' => [$required->id => 10],
        ])->assertRedirect(route('public.surveys.thanks', $survey));

        $this->assertDatabaseHas('satisfaction_answers', ['question_id' => $required->id]);
        $this->assertDatabaseMissing('satisfaction_answers', ['question_id' => $optional->id]);
    }

    private function survey(array $attributes = []): SatisfactionSurvey
    {
        return SatisfactionSurvey::create(array_merge([
            'description' => 'Pesquisa pública',
            'response_type' => SurveyResponseType::Numeric,
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ], $attributes));
    }
}
