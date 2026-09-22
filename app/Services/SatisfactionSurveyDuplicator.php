<?php

namespace App\Services;

use App\Models\SatisfactionSurvey;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SatisfactionSurveyDuplicator
{
    public function duplicate(
        SatisfactionSurvey $source,
        string $description,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $createdBy = null,
    ): SatisfactionSurvey {
        return DB::transaction(function () use ($source, $description, $startsAt, $endsAt, $createdBy): SatisfactionSurvey {
            $copy = $source->replicate(['public_token']);
            $copy->forceFill([
                'description' => $description,
                'is_active' => false,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'created_by' => $createdBy,
            ]);
            $copy->save();

            $source->questions()->each(function ($question) use ($copy): void {
                $copy->questions()->save($question->replicate(['survey_id']));
            });

            return $copy->load('questions');
        });
    }
}
