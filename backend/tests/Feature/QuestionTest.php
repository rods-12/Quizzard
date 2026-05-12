<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    // ─── HELPERS ─────────────────────────────────────────────

    protected function createUser(string $role = 'teacher', array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'   => $role,
            'status' => 'active',
        ], $overrides));
    }

    protected function createQuiz(User $teacher, array $overrides = []): Quiz
    {
        return Quiz::create(array_merge([
            'teacher_id'   => $teacher->id,
            'title'        => 'Sample Quiz',
            'description'  => 'Sample Description',
            'is_published' => false,
        ], $overrides));
    }

    protected function createQuestion(Quiz $quiz, array $overrides = []): Question
    {
        return Question::create(array_merge([
            'quiz_id'       => $quiz->id,
            'question_text' => 'Sample Question',
            'question_type' => 'identification',
            'points'        => 1,
            'order'         => 1,
        ], $overrides));
    }

    // ─── INDEX ───────────────────────────────────────────────

    public function test_teacher_can_get_all_questions_for_a_quiz(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->createQuestion($quiz);

        $this->actingAs($teacher)
             ->getJson("/api/quizzes/{$quiz->id}/questions")
             ->assertOk()
             ->assertJsonPath('quiz.id', $quiz->id)
             ->assertJsonCount(1, 'questions');
    }

    // ─── MULTIPLE CHOICE ─────────────────────────────────────

    public function test_teacher_can_create_multiple_choice_question(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/quizzes/{$quiz->id}/questions/multiple-choice", [
                 'question_text' => 'What is the capital of France?',
                 'points'        => 2,
                 'options'       => [
                     ['option_text' => 'Paris',  'is_correct' => true],
                     ['option_text' => 'London', 'is_correct' => false],
                     ['option_text' => 'Berlin', 'is_correct' => false],
                     ['option_text' => 'Madrid', 'is_correct' => false],
                 ],
             ])
             ->assertCreated()
             ->assertJsonPath('message', 'Multiple choice question created successfully.');
    }

    public function test_multiple_choice_fails_if_no_correct_answer(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/quizzes/{$quiz->id}/questions/multiple-choice", [
                 'question_text' => 'What is the capital of France?',
                 'options' => [
                     ['option_text' => 'Paris', 'is_correct' => false],
                     ['option_text' => 'London', 'is_correct' => false],
                 ],
             ])
             ->assertUnprocessable()
             ->assertJsonPath('message', 'Multiple choice questions must have exactly one correct answer.');
    }

    public function test_multiple_choice_fails_if_more_than_one_correct_answer(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/quizzes/{$quiz->id}/questions/multiple-choice", [
                 'question_text' => 'What is the capital of France?',
                 'options' => [
                     ['option_text' => 'Paris', 'is_correct' => true],
                     ['option_text' => 'London', 'is_correct' => true],
                 ],
             ])
             ->assertUnprocessable()
             ->assertJsonPath('message', 'Multiple choice questions must have exactly one correct answer.');
    }

    // ─── TRUE/FALSE ─────────────────────────────────────────

    public function test_teacher_can_create_true_false_question(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/quizzes/{$quiz->id}/questions/true-false", [
                 'question_text'  => 'The sky is blue.',
                 'points'         => 1,
                 'correct_answer' => true,
             ])
             ->assertCreated()
             ->assertJsonPath('message', 'True or False question created successfully.');
    }

    // ─── IDENTIFICATION ──────────────────────────────────────

    public function test_teacher_can_create_identification_question(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/quizzes/{$quiz->id}/questions/identification", [
                 'question_text' => 'What is H2O?',
                 'points'        => 1,
                 'answer'        => 'H2O',
             ])
             ->assertCreated()
             ->assertJsonPath('message', 'Identification question created successfully.');
    }

    // ─── MATCHING ───────────────────────────────────────────

    public function test_teacher_can_create_matching_question(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/quizzes/{$quiz->id}/questions/matching", [
                 'question_text' => 'Match countries',
                 'points'        => 3,
                 'pairs' => [
                     ['left' => 'France', 'right' => 'Paris'],
                     ['left' => 'Germany', 'right' => 'Berlin'],
                 ],
             ])
             ->assertCreated()
             ->assertJsonPath('message', 'Matching type question created successfully.');
    }

    // ─── UPDATE ─────────────────────────────────────────────

    public function test_teacher_can_update_a_question(): void
    {
        $teacher  = $this->createUser('teacher');
        $quiz     = $this->createQuiz($teacher);
        $question = $this->createQuestion($quiz, [
            'question_text' => 'Old question text'
        ]);

        $this->actingAs($teacher)
             ->putJson("/api/quizzes/{$quiz->id}/questions/{$question->id}", [
                 'question_text' => 'Updated question text',
                 'points'        => 2,
                 'answer'        => 'Updated Answer',
             ])
             ->assertOk()
             ->assertJsonPath('message', 'Question updated successfully.');
    }

    // ─── DELETE ─────────────────────────────────────────────

    public function test_teacher_can_delete_a_question(): void
    {
        $teacher  = $this->createUser('teacher');
        $quiz     = $this->createQuiz($teacher);
        $question = $this->createQuestion($quiz);

        $this->actingAs($teacher)
             ->deleteJson("/api/quizzes/{$quiz->id}/questions/{$question->id}")
             ->assertOk()
             ->assertJsonPath('message', 'Question deleted successfully.');

        $this->assertDatabaseMissing('questions', [
            'id' => $question->id
        ]);
    }

    // ─── AUTHORIZATION ──────────────────────────────────────

    public function test_non_owner_teacher_cannot_add_question(): void
    {
        $owner = $this->createUser('teacher');
        $other = $this->createUser('teacher');
        $quiz  = $this->createQuiz($owner);

        $this->actingAs($other)
             ->postJson("/api/quizzes/{$quiz->id}/questions/identification", [
                 'question_text' => 'Unauthorized',
                 'answer' => 'Answer',
             ])
             ->assertForbidden();
    }

    public function test_non_owner_teacher_cannot_update_question(): void
    {
        $owner = $this->createUser('teacher');
        $other = $this->createUser('teacher');
        $quiz  = $this->createQuiz($owner);
        $question = $this->createQuestion($quiz);

        $this->actingAs($other)
             ->putJson("/api/quizzes/{$quiz->id}/questions/{$question->id}", [
                 'question_text' => 'Hacked',
                 'answer' => 'Hacked',
             ])
             ->assertForbidden();
    }

    public function test_non_owner_teacher_cannot_delete_question(): void
    {
        $owner = $this->createUser('teacher');
        $other = $this->createUser('teacher');
        $quiz  = $this->createQuiz($owner);
        $question = $this->createQuestion($quiz);

        $this->actingAs($other)
             ->deleteJson("/api/quizzes/{$quiz->id}/questions/{$question->id}")
             ->assertForbidden();
    }

    // ─── QUIZ HAS ATTEMPTS ──────────────────────────────────

    protected function createAttempt(Quiz $quiz, User $student)
    {
        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => 0,
            'total_points' => 10,
            'status' => 'completed',
            'started_at' => now(),
        ]);
    }

    // public function test_cannot_add_question_if_quiz_has_attempts(): void
    // {
    //     $teacher = $this->createUser('teacher');
    //     $student = $this->createUser('student');
    //     $quiz    = $this->createQuiz($teacher);

    //     $this->createAttempt($quiz, $student);

    //     $this->actingAs($teacher)
    //          ->postJson("/api/quizzes/{$quiz->id}/questions/identification", [
    //              'question_text' => 'New',
    //              'answer' => 'Answer',
    //          ])
    //          ->assertForbidden()
    //          ->assertJsonPath('message', 'This quiz cannot be modified because students have already taken it.');
    // }

    // public function test_cannot_update_question_if_quiz_has_attempts(): void
    // {
    //     $teacher  = $this->createUser('teacher');
    //     $student  = $this->createUser('student');
    //     $quiz     = $this->createQuiz($teacher);
    //     $question = $this->createQuestion($quiz);

    //     $this->createAttempt($quiz, $student);

    //     $this->actingAs($teacher)
    //          ->putJson("/api/quizzes/{$quiz->id}/questions/{$question->id}", [
    //              'question_text' => 'Updated',
    //              'answer' => 'Updated',
    //          ])
    //          ->assertForbidden()
    //          ->assertJsonPath('message', 'This question cannot be edited because students have already taken this quiz.');
    // }

    // public function test_cannot_delete_question_if_quiz_has_attempts(): void
    // {
    //     $teacher  = $this->createUser('teacher');
    //     $student  = $this->createUser('student');
    //     $quiz     = $this->createQuiz($teacher);
    //     $question = $this->createQuestion($quiz);

    //     $this->createAttempt($quiz, $student);

    //     $this->actingAs($teacher)
    //          ->deleteJson("/api/quizzes/{$quiz->id}/questions/{$question->id}")
    //          ->assertForbidden()
    //          ->assertJsonPath('message', 'This question cannot be deleted because students have already taken this quiz.');
    // }
}
