<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index on quiz_attempts(quiz_id, status, reviewed_at)
        // Covers the most expensive repeated pattern in analytics:
        // WHERE quiz_id = ? AND status = 'reviewed' ORDER BY reviewed_at
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['quiz_id', 'status', 'reviewed_at'], 'idx_quiz_attempts_quiz_status_reviewed');
        });

        // Composite index on student_answers(question_id, attempt_id)
        // Covers the LEFT JOIN in question analytics:
        // ON student_answers.question_id = questions.id (+ attempt_id for the inner join)
        Schema::table('student_answers', function (Blueprint $table) {
            $table->index(['question_id', 'attempt_id'], 'idx_student_answers_question_attempt');
        });

        // Index on student_answer_reviews(student_answer_id)
        // Covers the LEFT JOIN in question analytics:
        // ON student_answer_reviews.student_answer_id = student_answers.id
        // foreignId() creates a FK constraint but NOT a separate index in all MySQL engines.
        Schema::table('student_answer_reviews', function (Blueprint $table) {
            $table->index('student_answer_id', 'idx_student_answer_reviews_answer_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_quiz_attempts_quiz_status_reviewed');
        });

        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropIndex('idx_student_answers_question_attempt');
        });

        Schema::table('student_answer_reviews', function (Blueprint $table) {
            $table->dropIndex('idx_student_answer_reviews_answer_id');
        });
    }
};
