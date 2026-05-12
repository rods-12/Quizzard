<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Quiz;
use App\Models\ClassRoom;
use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
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
        return Quiz::factory()->create(array_merge([
            'teacher_id' => $teacher->id,
        ], $overrides));
    }

    protected function createClass(User $teacher, array $overrides = []): ClassRoom
    {
        return ClassRoom::factory()->create(array_merge([
            'teacher_id' => $teacher->id,
        ], $overrides));
    }

    protected function createAttempt(Quiz $quiz, User $student): QuizAttempt
    {
        return QuizAttempt::create([
            'quiz_id'      => $quiz->id,
            'student_id'   => $student->id,
            'score'        => 0,
            'total_points' => 10,
            'status'       => 'completed',
            'started_at'   => now(),
        ]);
    }

    // ─── CREATE ─────────────────────────────────────────────

    public function test_teacher_can_create_a_quiz(): void
    {
        $teacher = $this->createUser('teacher');

        $this->actingAs($teacher)
             ->postJson('/api/quizzes', [
                 'title'       => 'My New Quiz',
                 'description' => 'A quiz about science.',
             ])
             ->assertCreated()
             ->assertJsonPath('message', 'Quiz created successfully.');

        $this->assertDatabaseHas('quizzes', [
            'title'      => 'My New Quiz',
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_quiz_creation_fails_without_title(): void
    {
        $teacher = $this->createUser('teacher');

        $this->actingAs($teacher)
             ->postJson('/api/quizzes', [
                 'description' => 'No title provided.',
             ])
             ->assertUnprocessable();
    }

    // ─── READ ───────────────────────────────────────────────

    public function test_teacher_can_get_all_their_quizzes(): void
    {
        $teacher = $this->createUser('teacher');

        Quiz::factory()->count(3)->create([
            'teacher_id' => $teacher->id
        ]);

        $this->actingAs($teacher)
             ->getJson('/api/quizzes')
             ->assertOk()
             ->assertJsonCount(3, 'data');
    }

    public function test_teacher_only_sees_their_own_quizzes(): void
    {
        $teacher = $this->createUser('teacher');
        $other   = $this->createUser('teacher');

        Quiz::factory()->count(2)->create(['teacher_id' => $teacher->id]);
        Quiz::factory()->count(3)->create(['teacher_id' => $other->id]);

        $this->actingAs($teacher)
             ->getJson('/api/quizzes')
             ->assertOk()
             ->assertJsonCount(2, 'data');
    }

    public function test_teacher_can_get_a_single_quiz(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->getJson("/api/quizzes/{$quiz->id}")
             ->assertOk()
             ->assertJsonPath('data.id', $quiz->id);
    }

    // ─── UPDATE ─────────────────────────────────────────────

    public function test_teacher_can_update_a_quiz(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->putJson("/api/quizzes/{$quiz->id}", [
                 'title'       => 'Updated Title',
                 'description' => 'Updated description.',
             ])
             ->assertOk()
             ->assertJsonPath('message', 'Quiz updated successfully.');

        $this->assertDatabaseHas('quizzes', [
            'id'    => $quiz->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_non_owner_teacher_cannot_update_a_quiz(): void
    {
        $owner = $this->createUser('teacher');
        $other = $this->createUser('teacher');
        $quiz  = $this->createQuiz($owner);

        $this->actingAs($other)
             ->putJson("/api/quizzes/{$quiz->id}", [
                 'title' => 'Hacked',
             ])
             ->assertForbidden();
    }

    // public function test_cannot_update_quiz_if_it_has_attempts(): void
    // {
    //     $teacher = $this->createUser('teacher');
    //     $student = $this->createUser('student');
    //     $quiz    = $this->createQuiz($teacher);

    //     $this->createAttempt($quiz, $student);

    //     $this->actingAs($teacher)
    //          ->putJson("/api/quizzes/{$quiz->id}", [
    //              'title' => 'Updated',
    //          ])
    //          ->assertForbidden()
    //          ->assertJsonPath('message', 'This quiz cannot be edited because students have already taken it.');
    // }

    // ─── DELETE ─────────────────────────────────────────────

    public function test_teacher_can_delete_a_quiz(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);

        $this->actingAs($teacher)
             ->deleteJson("/api/quizzes/{$quiz->id}")
             ->assertOk()
             ->assertJsonPath('message', 'Quiz deleted successfully.');

        $this->assertDatabaseMissing('quizzes', [
            'id' => $quiz->id
        ]);
    }

    public function test_non_owner_teacher_cannot_delete_a_quiz(): void
    {
        $owner = $this->createUser('teacher');
        $other = $this->createUser('teacher');
        $quiz  = $this->createQuiz($owner);

        $this->actingAs($other)
             ->deleteJson("/api/quizzes/{$quiz->id}")
             ->assertForbidden();
    }

    // public function test_cannot_delete_quiz_if_it_has_attempts(): void
    // {
    //     $teacher = $this->createUser('teacher');
    //     $student = $this->createUser('student');
    //     $quiz    = $this->createQuiz($teacher);

    //     $this->createAttempt($quiz, $student);

    //     $this->actingAs($teacher)
    //          ->deleteJson("/api/quizzes/{$quiz->id}")
    //          ->assertForbidden()
    //          ->assertJsonPath('message', 'This quiz cannot be deleted because students have already taken it.');
    // }

    // ─── PUBLISH / UNPUBLISH ────────────────────────────────

    public function test_teacher_can_publish_a_quiz(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher, ['is_published' => false]);

        $this->actingAs($teacher)
             ->patchJson("/api/quizzes/{$quiz->id}/publish-toggle")
             ->assertOk()
             ->assertJsonPath('message', 'Quiz published.');

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'is_published' => true
        ]);
    }

    public function test_teacher_can_unpublish_a_quiz(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher, ['is_published' => true]);

        $this->actingAs($teacher)
             ->patchJson("/api/quizzes/{$quiz->id}/publish-toggle")
             ->assertOk()
             ->assertJsonPath('message', 'Quiz unpublished.');

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'is_published' => false
        ]);
    }

    public function test_non_owner_teacher_cannot_publish_or_unpublish_a_quiz(): void
    {
        $owner = $this->createUser('teacher');
        $other = $this->createUser('teacher');
        $quiz  = $this->createQuiz($owner);

        $this->actingAs($other)
             ->patchJson("/api/quizzes/{$quiz->id}/publish-toggle")
             ->assertForbidden();
    }

    // ─── ASSIGN TO CLASS ────────────────────────────────────

    public function test_teacher_can_assign_a_quiz_to_a_class(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);
        $class   = $this->createClass($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/classes/{$class->id}/assign-quiz", [
                 'quiz_id' => $quiz->id,
             ])
             ->assertOk()
             ->assertJsonPath('message', 'Quiz assigned to class successfully.');
    }

    public function test_cannot_assign_same_quiz_twice_to_same_class(): void
    {
        $teacher = $this->createUser('teacher');
        $quiz    = $this->createQuiz($teacher);
        $class   = $this->createClass($teacher);

        $class->quizzes()->attach($quiz->id, ['assigned_at' => now()]);

        $this->actingAs($teacher)
             ->postJson("/api/classes/{$class->id}/assign-quiz", [
                 'quiz_id' => $quiz->id,
             ])
             ->assertStatus(409)
             ->assertJsonPath('message', 'Quiz is already assigned to this class.');
    }

    public function test_cannot_assign_another_teachers_quiz_to_class(): void
    {
        $teacher = $this->createUser('teacher');
        $other   = $this->createUser('teacher');

        $quiz  = $this->createQuiz($other);
        $class = $this->createClass($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/classes/{$class->id}/assign-quiz", [
                 'quiz_id' => $quiz->id,
             ])
             ->assertNotFound();
    }
}
