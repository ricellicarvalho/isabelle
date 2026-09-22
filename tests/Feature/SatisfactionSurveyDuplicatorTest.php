<?php

namespace Tests\Feature;

use App\Enums\SurveyResponseType;
use App\Models\SatisfactionSurvey;
use App\Services\SatisfactionSurveyDuplicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SatisfactionSurveyDuplicatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_duplicates_the_survey_and_questions_without_responses(): void
    {
        $source = SatisfactionSurvey::create([
            'description' => 'Setembro Amarelo 2025',
            'introduction' => 'Conte-nos como foi a campanha.',
            'response_type' => SurveyResponseType::Numeric,
            'is_active' => true,
            'starts_at' => '2025-09-01 08:00:00',
            'ends_at' => '2025-09-30 18:00:00',
            'thank_you_message' => 'Obrigado pela participação.',
            'allow_multiple_submissions' => true,
        ]);
        $source->questions()->create([
            'description' => 'Como você avalia a campanha?',
            'answer_type' => 'scale',
            'is_visible' => true,
            'is_required' => true,
            'display_order' => 1,
        ]);
        $source->questions()->create([
            'description' => 'Deixe um comentário.',
            'answer_type' => 'text',
            'is_visible' => false,
            'is_required' => false,
            'display_order' => 2,
        ]);

        $copy = app(SatisfactionSurveyDuplicator::class)->duplicate(
            $source,
            'Setembro Amarelo 2026',
            now()->setDate(2026, 9, 1)->setTime(8, 0),
            now()->setDate(2026, 9, 30)->setTime(18, 0),
        );

        $this->assertNotSame($source->id, $copy->id);
        $this->assertNotSame($source->public_token, $copy->public_token);
        $this->assertSame('Setembro Amarelo 2026', $copy->description);
        $this->assertFalse($copy->is_active);
        $this->assertSame($source->introduction, $copy->introduction);
        $this->assertSame($source->thank_you_message, $copy->thank_you_message);
        $this->assertTrue($copy->allow_multiple_submissions);
        $this->assertCount(2, $copy->questions);
        $this->assertSame(
            $source->questions->pluck('description', 'display_order')->all(),
            $copy->questions->pluck('description', 'display_order')->all(),
        );
        $this->assertSame(['scale', 'text'], $copy->questions->pluck('answer_type')->all());
        $this->assertDatabaseCount('satisfaction_submissions', 0);
        $this->assertDatabaseCount('satisfaction_answers', 0);
    }
}
