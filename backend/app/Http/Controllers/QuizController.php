<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Models\StudentAnswerReview;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    // ─── GET /api/quizzes ─────────────────────────────────────────
    public function index()
    {
        $quizzes = Quiz::where('teacher_id', Auth::id())
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $quizzes,
        ]);
    }

    // ─── POST /api/quizzes ────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $quiz = Quiz::create([
            'teacher_id'   => Auth::id(),
            'title'        => $validated['title'],
            'description'  => $validated['description'] ?? null,
            'is_published' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz created successfully.',
            'data'    => $quiz,
        ], 201);
    }

    // ─── GET /api/quizzes/{quizId} ────────────────────────────────
    public function show($quizId)
    {
        $quiz = Quiz::with(['questions' => function ($q) {
            $q->orderBy('order')->with(['answerOptions' => function ($ao) {
                $ao->orderBy('order');
            }]);
        }])->findOrFail($quizId);

        $user = Auth::user();
        if ($user->role === 'student') {
            $quiz->questions->each(function ($question) {
                $question->answerOptions->each(function ($option) {
                    unset($option->is_correct);
                });
            });
        }

        $hasAttempts = QuizAttempt::where('quiz_id', $quizId)->exists();

        return response()->json([
            'success'      => true,
            'has_attempts' => $hasAttempts,
            'data'         => $quiz,
        ]);
    }

    // ─── PUT /api/quizzes/{quizId} ────────────────────────────────
    public function update(Request $request, $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        if ($quiz->teacher_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $hasAttempts = QuizAttempt::where('quiz_id', $quizId)->exists();
        if ($hasAttempts) {
            return response()->json([
                'message' => 'This quiz cannot be edited because students have already taken it.',
            ], 403);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $quiz->update([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? $quiz->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully.',
            'data'    => $quiz,
        ]);
    }

    // ─── DELETE /api/quizzes/{quizId} ─────────────────────────────
    public function destroy($quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        if ($quiz->teacher_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $hasAttempts = QuizAttempt::where('quiz_id', $quizId)->exists();
        if ($hasAttempts) {
            return response()->json([
                'message' => 'This quiz cannot be deleted because students have already taken it.',
            ], 403);
        }

        $quiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz deleted successfully.',
        ]);
    }

    // ─── PATCH /api/quizzes/{quizId}/publish-toggle ───────────────
    public function publishToggle($quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        if ($quiz->teacher_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $quiz->is_published = !$quiz->is_published;
        $quiz->save();

        return response()->json([
            'success' => true,
            'message' => $quiz->is_published ? 'Quiz published.' : 'Quiz unpublished.',
            'data'    => $quiz,
        ]);
    }

    // ─── POST /api/quizzes/{quizId}/start ─────────────────────────
    public function startAttempt(Request $request, $quizId)
    {
        $quiz = Quiz::where('id', $quizId)
            ->where('is_published', true)
            ->firstOrFail();

        // 'completed' no longer exists in the ENUM — check all terminal statuses
        $terminalAttempt = QuizAttempt::where('student_id', $request->user()->id)
            ->where('quiz_id', $quizId)
            ->whereIn('status', ['submitted', 'under_review', 'reviewed'])
            ->first();

        if ($terminalAttempt) {
            return response()->json([
                'message' => 'You have already submitted this quiz.',
                'attempt' => $terminalAttempt,
            ], 409);
        }

        $inProgressAttempt = QuizAttempt::where('student_id', $request->user()->id)
            ->where('quiz_id', $quizId)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgressAttempt) {
            return response()->json([
                'message' => 'Resuming existing attempt.',
                'attempt' => $inProgressAttempt,
            ]);
        }

        $attempt = QuizAttempt::create([
            'student_id'   => $request->user()->id,
            'quiz_id'      => $quizId,
            'score'        => 0,
            'total_points' => $quiz->questions()->sum('points'),
            'status'       => 'in_progress',
            'started_at'   => now(),
        ]);

        return response()->json([
            'message' => 'Quiz started successfully.',
            'attempt' => $attempt,
        ], 201);
    }

    // ─── POST /api/quizzes/{quizId}/submit ────────────────────────
    public function submitQuiz(Request $request, $quizId)
    {
        $validator = Validator::make($request->all(), [
            'attempt_id' => 'required|integer',
            'answers'    => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $attempt = QuizAttempt::where('id', $request->attempt_id)
            ->where('student_id', $request->user()->id)
            ->where('quiz_id', $quizId)
            ->where('status', 'in_progress')
            ->firstOrFail();

        // ── Determine grading_mode from class_quizzes ──────────────
        // Find the class this student belongs to that has this quiz assigned.
        $studentId = $request->user()->id;

        $classQuiz = ClassRoom::whereHas('students', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);  // ← corrected from user_id
            })
            ->whereHas('quizzes', function ($q) use ($quizId) {
                $q->where('quiz_id', $quizId);
            })
            ->with(['quizzes' => function ($q) use ($quizId) {
                $q->where('quiz_id', $quizId);
            }])
            ->first();

        // Default to automatic if quiz was not assigned via a class
        $gradingMode = 'automatic';
        if ($classQuiz && $classQuiz->quizzes->isNotEmpty()) {
            $gradingMode = $classQuiz->quizzes->first()->pivot->grading_mode ?? 'automatic';
        }

        // ── Build and score answers ────────────────────────────────
        $quiz = Quiz::with(['questions' => function ($q) {
            $q->orderBy('order')->with('answerOptions');
        }])->findOrFail($quizId);

        $actualTotalPoints = $quiz->questions()->sum('points');
        $totalScore        = 0;
        $answersToSave     = [];

        foreach ($request->answers as $answerData) {
            $questionId = $answerData['question_id'];
            $answerType = $answerData['answer_type'];

            $question = Question::with('answerOptions')->findOrFail($questionId);

            $isCorrect    = false;
            $pointsEarned = 0;
            $answerGiven  = '';

            if ($gradingMode === 'automatic') {
                if ($answerType === 'multiple_choice' || $answerType === 'true_false') {
                    $selectedOptionId = $answerData['selected_option_id'] ?? null;
                    $answerGiven      = (string) $selectedOptionId;

                    if ($selectedOptionId) {
                        $correctOption = $question->answerOptions->where('is_correct', true)->first();
                        if ($correctOption && $correctOption->id == $selectedOptionId) {
                            $isCorrect    = true;
                            $pointsEarned = $question->points;
                        }
                    }
                } elseif ($answerType === 'identification') {
                    $answerText  = trim($answerData['answer_text'] ?? '');
                    $answerGiven = $answerText;

                    $correctOption = $question->answerOptions->where('is_correct', true)->first();
                    if ($correctOption && strtolower($answerText) === strtolower(trim($correctOption->option_text))) {
                        $isCorrect    = true;
                        $pointsEarned = $question->points;
                    }
                } elseif ($answerType === 'matching') {
                    $matches     = $answerData['matches'] ?? [];
                    $answerGiven = json_encode($matches);

                    $correctPairs  = $question->answerOptions;
                    $totalPairs    = $correctPairs->count();
                    $correctCount  = 0;
                    $pointsPerPair = $totalPairs > 0 ? $question->points / $totalPairs : 0;

                    foreach ($correctPairs as $pair) {
                        $studentB = $matches[$pair->option_text] ?? '';
                        if (strtolower(trim($studentB)) === strtolower(trim($pair->match_pair))) {
                            $correctCount++;
                        }
                    }

                    $pointsEarned = round($correctCount * $pointsPerPair);
                    $isCorrect    = $correctCount === $totalPairs;
                }

                $totalScore += $pointsEarned;
            } else {
                // Manual mode — capture the answer as-is, no scoring
                if ($answerType === 'multiple_choice' || $answerType === 'true_false') {
                    $answerGiven = (string) ($answerData['selected_option_id'] ?? '');
                } elseif ($answerType === 'identification') {
                    $answerGiven = trim($answerData['answer_text'] ?? '');
                } elseif ($answerType === 'matching') {
                    $answerGiven = json_encode($answerData['matches'] ?? []);
                }
            }

            $answersToSave[] = [
                'attempt_id'    => $attempt->id,
                'question_id'   => $questionId,
                'answer_given'  => $answerGiven,
                'is_correct'    => $isCorrect,
                'points_earned' => $pointsEarned,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        StudentAnswer::insert($answersToSave);

        // ── Finalize attempt per grading mode ─────────────────────
        if ($gradingMode === 'automatic') {
            $attempt->update([
                'score'        => $totalScore,
                'total_points' => $actualTotalPoints,
                'status'       => 'reviewed',
                'completed_at' => now(),
            ]);

            $questionResults = [];
            foreach ($quiz->questions as $question) {
                $savedAnswer = StudentAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->first();

                $questionResults[] = [
                    'id'             => $question->id,
                    'question_text'  => $question->question_text,
                    'question_type'  => $question->question_type,
                    'points'         => $question->points,
                    'points_earned'  => $savedAnswer?->points_earned ?? 0,
                    'is_correct'     => $savedAnswer?->is_correct ?? false,
                    'answer_given'   => $savedAnswer?->answer_given ?? '',
                    'answer_options' => $question->answerOptions->map(function ($opt) {
                        return [
                            'id'          => $opt->id,
                            'option_text' => $opt->option_text,
                            'is_correct'  => $opt->is_correct,
                            'match_pair'  => $opt->match_pair,
                            'order'       => $opt->order,
                        ];
                    }),
                ];
            }

            $percentage = $actualTotalPoints > 0
                ? round(($totalScore / $actualTotalPoints) * 100)
                : 0;

            return response()->json([
                'message'          => 'Quiz submitted successfully!',
                'attempt_id'       => $attempt->id,
                'score'            => $totalScore,
                'total_points'     => $actualTotalPoints,
                'percentage'       => $percentage,
                'quiz_title'       => $quiz->title,
                'question_results' => $questionResults,
            ]);
        }

        // ── Manual mode: create review rows, return minimal response
        $attempt->update([
            'score'        => 0,
            'total_points' => $actualTotalPoints,
            'status'       => 'submitted',
            'completed_at' => now(),
        ]);

        $savedAnswers = StudentAnswer::where('attempt_id', $attempt->id)->get();

        $reviewRows = $savedAnswers->map(function ($answer) use ($studentId) {
            return [
                'student_answer_id' => $answer->id,
                'teacher_id'        => null,
                'points_awarded'    => null,
                'feedback'          => null,
                'reviewed_at'       => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        })->toArray();

        StudentAnswerReview::insert($reviewRows);

        return response()->json([
            'message'    => 'Quiz submitted. Your answers are pending teacher review.',
            'attempt_id' => $attempt->id,
            'status'     => 'submitted',
        ], 202);
    }
}
