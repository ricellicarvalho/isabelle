<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satisfaction_surveys', function (Blueprint $table): void {
            $table->id();
            $table->string('description');
            $table->text('introduction')->nullable();
            $table->string('response_type', 30);
            $table->boolean('is_active')->default(false)->index();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->uuid('public_token')->unique();
            $table->text('thank_you_message')->nullable();
            $table->boolean('allow_multiple_submissions')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('satisfaction_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_id')->constrained('satisfaction_surveys')->cascadeOnDelete();
            $table->text('description');
            $table->boolean('is_visible')->default(true)->index();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['survey_id', 'display_order']);
        });

        Schema::create('satisfaction_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_id')->constrained('satisfaction_surveys')->cascadeOnDelete();
            $table->uuid('public_identifier')->unique();
            $table->string('respondent_fingerprint_hash', 64)->nullable()->index();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();
            $table->index(['survey_id', 'submitted_at']);
        });

        Schema::create('satisfaction_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained('satisfaction_submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('satisfaction_questions')->restrictOnDelete();
            $table->unsignedTinyInteger('numeric_value');
            $table->text('question_snapshot');
            $table->string('response_type_snapshot', 30);
            $table->timestamps();
            $table->unique(['submission_id', 'question_id']);
            $table->index(['question_id', 'numeric_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_answers');
        Schema::dropIfExists('satisfaction_submissions');
        Schema::dropIfExists('satisfaction_questions');
        Schema::dropIfExists('satisfaction_surveys');
    }
};
