<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\ClassRoom;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Models\StudentAnswerReview;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManualReviewController extends Controller
{
    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Ticket 10.4 — Centralized teacher ownership check.
     *
     * Loads the attempt (with its quiz) and verifies the authenticated teacher
     * owns the quiz. Returns the attempt on success, or a JsonResponse on
     * failure so the caller can return it immediately.
     *
     * Usage:
     *   $result = $this->authorizeAttempt($attemptId, $teacherId);
     *   if ($result instanceof JsonResponse) return $result;
     *   $attempt = $result;
     */
    private function authorizeAttempt(int $attemptId, int $teacherId): QuizAttempt|JsonResponse
    {
        $attempt = QuizAttempt::with('quiz')->find($attemptId);

        if (!$attempt) {
            return response()->json(['message' => 'Attempt not found.'], 404);
        }

        if ($attempt->quiz->teacher_id !== $teacherId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $attempt;
    }

    /**
     * Ticket 10.2 — Confirm a quiz assignment uses manual grading mode.
     *
     * Looks up the class_quizzes row for this attempt's student + quiz and
     * returns the grading_mode string, or null if no class assignment found
     * (which means it was taken outside a class — treat as automatic).
     */
    private function resolveGradingMode(QuizAttempt $attempt): string
    {
        $studentId = $attempt->student_id;
        $quizId    = $attempt->quiz_id;

        $row = DB::table('class_quizzes')
            ->join('class_students', 'class_students.class_id', '=', 'class_quizzes.class_id')
            ->where('class_quizzes.quiz_id', $quizId)
            ->where('class_students.student_id', $studentId)
            ->select('class_quizzes.grading_mode')
            ->first();

        return $row?->grading_mode ?? 'automatic';
    }

    // =========================================================================
    // GET /teacher/manual-review/quizzes
    // Quizzes assigned with manual grading_mode, grouped by class,
    // with pending (submitted + under_review) and reviewed counts.
    // =========================================================================
    public function pendingManualQuizzes(Request $request): JsonResponse
    {
        $teacherId = $request->user()->id;

        $assignments = DB::table('class_quizzes')
            ->join('quizzes', 'quizzes.id', '=', 'class_quizzes.quiz_id')
            ->join('classes', 'classes.id', '=', 'class_quizzes.class_id')
            ->where('quizzes.teacher_id', $teacherId)
            ->where('class_quizzes.grading_mode', 'manual')
            ->select(
                'classes.id as class_id',
                'classes.name as class_name',
                'quizzes.id as quiz_id',
                'quizzes.title as quiz_title',
                'class_quizzes.due_date'
            )
            ->get();

        $grouped = [];

        foreach ($assignments as $row) {
            $quizAttempts = QuizAttempt::where('quiz_id', $row->quiz_id)
                ->whereIn(
                    'student_id',
                    DB::table('class_students')
                        ->where('class_id', $row->class_id)
                        ->pluck('student_id')
                )
                ->get();

            $pendingCount  = $quizAttempts->whereIn('status', [
                QuizAttempt::STATUS_SUBMITTED,
                QuizAttempt::STATUS_UNDER_REVIEW,
            ])->count();

            $reviewedCount = $quizAttempts->where('status', QuizAttempt::STATUS_REVIEWED)->count();

            $quizEntry = [
                'quiz_id'        => $row->quiz_id,
                'quiz_title'     => $row->quiz_title,
                'due_date'       => $row->due_date,
                'pending_count'  => $pendingCount,
                'reviewed_count' => $reviewedCount,
                'total_attempts' => $quizAttempts->count(),
            ];

            if (!isset($grouped[$row->class_id])) {
                $grouped[$row->class_id] = [
                    'class_id'   => $row->class_id,
                    'class_name' => $row->class_name,
                    'quizzes'    => [],
                ];
            }

            $grouped[$row->class_id]['quizzes'][] = $quizEntry;
        }

        return response()->json([
            'classes' => array_values($grouped),
        ]);
    }

    // =========================================================================
    // GET /teacher/manual-review/classes/{classId}/quizzes/{quizId}/attempts
    // List of students with their attempt status for a manual quiz in a class.
    // =========================================================================
    public function classAttemptList(Request $request, $classId, $quizId): JsonResponse
    {
        $teacherId = $request->user()->id;

        $quiz = Quiz::where('id', $quizId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $assignment = DB::table('class_quizzes')
            ->where('class_id', $classId)
            ->where('quiz_id', $quizId)
            ->first();

        if (!$assignment) {
            return response()->json(['message' => 'Quiz is not assigned to this class.'], 404);
        }

        if ($assignment->grading_mode !== 'manual') {
            return response()->json(['message' => 'This quiz assignment is not in manual grading mode.'], 422);
        }

        $students = DB::table('class_students')
            ->join('users', 'users.id', '=', 'class_students.student_id')
            ->where('class_students.class_id', $classId)
            ->select('users.id', 'users.first_name', 'users.middle_initial', 'users.surname', 'users.email')
            ->get();

        $attempts = QuizAttempt::where('quiz_id', $quizId)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $list = $students->map(function ($student) use ($attempts) {
            $attempt = $attempts->get($student->id);

            return [
                'student_id'     => $student->id,
                'first_name'     => $student->first_name,
                'middle_initial' => $student->middle_initial,
                'surname'        => $student->surname,
                'email'          => $student->email,
                'attempt_id'     => $attempt?->id,
                'status'         => $attempt?->status ?? 'not_attempted',
                'submitted_at'   => $attempt?->completed_at,
                'reviewed_at'    => $attempt?->reviewed_at,
            ];
        });

        return response()->json([
            'class' => [
                'id'   => $class->id,
                'name' => $class->name,
            ],
            'quiz' => [
                'id'    => $quiz->id,
                'title' => $quiz->title,
            ],
            'attempts' => $list,
        ]);
    }

    // =========================================================================
    // GET /teacher/manual-review/attempts/{attemptId}
    // Full attempt detail: questions, student answers, existing review data.
    // =========================================================================
    public function attemptDetail(Request $request, $attemptId): JsonResponse
    {
        // Ticket 10.4 — centralized ownership check
        $result = $this->authorizeAttempt((int) $attemptId, $request->user()->id);
        if ($result instanceof JsonResponse) return $result;
        $attempt = $result;

        // Re-load with relationships needed for this response
        $attempt->load(['student', 'quiz']);

        $answers = StudentAnswer::where('attempt_id', $attemptId)
            ->with([
                'question' => fn($q) => $q->with('answerOptions'),
                'review',
            ])
            ->get();

        $questions = $answers->map(function ($answer) {
            $question = $answer->question;
            $review   = $answer->review;

            return [
                'student_answer_id' => $answer->id,
                'question_id'       => $question->id,
                'question_text'     => $question->question_text,
                'question_type'     => $question->question_type,
                'max_points'        => $question->points,
                'answer_given'      => $answer->answer_given,
                'justification'     => $answer->justification,
                'answer_options'    => $question->answerOptions->map(fn($opt) => [
                    'id'          => $opt->id,
                    'option_text' => $opt->option_text,
                    'is_correct'  => $opt->is_correct,
                    'match_pair'  => $opt->match_pair,
                    'order'       => $opt->order,
                ]),
                'review' => $review ? [
                    'id'             => $review->id,
                    'points_awarded' => $review->points_awarded,
                    'feedback'       => $review->feedback,
                    'reviewed_at'    => $review->reviewed_at,
                ] : null,
            ];
        });

        return response()->json([
            'attempt' => [
                'id'               => $attempt->id,
                'status'           => $attempt->status,
                'submitted_at'     => $attempt->completed_at,
                'reviewed_at'      => $attempt->reviewed_at,
                'teacher_feedback' => $attempt->teacher_feedback,
            ],
            'student' => [
                'id'             => $attempt->student->id,
                'first_name'     => $attempt->student->first_name,
                'middle_initial' => $attempt->student->middle_initial,
                'surname'        => $attempt->student->surname,
                'email'          => $attempt->student->email,
            ],
            'quiz' => [
                'id'    => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
            ],
            'questions' => $questions,
        ]);
    }

    // =========================================================================
    // PATCH /teacher/manual-review/attempts/{attemptId}/save-draft
    // Partial review — upsert review rows, set status to under_review.
    //
    // Ticket 10.3 — valid transitions: submitted → under_review
    //                                   under_review → under_review (re-save)
    // All other statuses → 422.
    // =========================================================================
    public function saveDraft(Request $request, $attemptId): JsonResponse
    {
        // Ticket 10.4 — centralized ownership check
        $result = $this->authorizeAttempt((int) $attemptId, $request->user()->id);
        if ($result instanceof JsonResponse) return $result;
        $attempt = $result;

        // Ticket 10.3 — status transition guard
        $allowedStatuses = [
            QuizAttempt::STATUS_SUBMITTED,
            QuizAttempt::STATUS_UNDER_REVIEW,
        ];
        if (!in_array($attempt->status, $allowedStatuses, true)) {
            return response()->json([
                'message' => "Cannot save draft: attempt status '{$attempt->status}' does not allow editing. "
                           . "Only 'submitted' or 'under_review' attempts can be drafted.",
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reviews'                     => 'required|array|min:1',
            'reviews.*.student_answer_id' => 'required|integer|exists:student_answers,id',
            'reviews.*.points_awarded'    => 'nullable|numeric|min:0',
            'reviews.*.feedback'          => 'nullable|string|max:1000',
            'teacher_feedback'            => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $attempt) {
            $teacherId = $request->user()->id;

            foreach ($request->reviews as $item) {
                // Confirm this answer belongs to this attempt
                $answer = StudentAnswer::where('id', $item['student_answer_id'])
                    ->where('attempt_id', $attempt->id)
                    ->firstOrFail();

                // Ticket 10.2 — validate points_awarded does not exceed question max
                if (isset($item['points_awarded'])) {
                    $maxPoints = $answer->question->points ?? 0;
                    if ((float) $item['points_awarded'] > (float) $maxPoints) {
                        abort(422, "Points awarded ({$item['points_awarded']}) exceeds max points ({$maxPoints}) for answer ID {$answer->id}.");
                    }
                }

                StudentAnswerReview::updateOrCreate(
                    ['student_answer_id' => $answer->id],
                    [
                        'teacher_id'     => $teacherId,
                        'points_awarded' => $item['points_awarded'] ?? null,
                        'feedback'       => $item['feedback'] ?? null,
                        'reviewed_at'    => isset($item['points_awarded']) ? now() : null,
                    ]
                );
            }

            $attempt->status           = QuizAttempt::STATUS_UNDER_REVIEW;
            $attempt->teacher_feedback = $request->teacher_feedback ?? $attempt->teacher_feedback;
            $attempt->save();
        });

        return response()->json([
            'message' => 'Draft saved. Attempt is now under review.',
            'status'  => QuizAttempt::STATUS_UNDER_REVIEW,
        ]);
    }

    // =========================================================================
    // POST /teacher/manual-review/attempts/{attemptId}/finalize
    // Validate all reviews have points_awarded, compute final score, mark reviewed.
    //
    // Ticket 10.2 — score MUST come from SUM(student_answer_reviews.points_awarded).
    //               Block if grading_mode is automatic.
    //               Write is_passed based on 60% threshold.
    // Ticket 10.3 — valid transitions: submitted → reviewed
    //                                   under_review → reviewed
    // All other statuses → 422.
    // =========================================================================
    public function finalizeReview(Request $request, $attemptId): JsonResponse
    {
        // Ticket 10.4 — centralized ownership check
        $result = $this->authorizeAttempt((int) $attemptId, $request->user()->id);
        if ($result instanceof JsonResponse) return $result;
        $attempt = $result;

        // Ticket 10.2 — block finalize on automatic attempts
        // $gradingMode = $this->resolveGradingMode($attempt);
        // if ($gradingMode !== 'manual') {
        //     return response()->json([
        //         'message' => 'This attempt uses automatic grading and cannot be manually finalized.',
        //     ], 422);
        // }

        // Ticket 10.3 — status transition guard
        $allowedStatuses = [
            QuizAttempt::STATUS_SUBMITTED,
            QuizAttempt::STATUS_UNDER_REVIEW,
        ];
        if (!in_array($attempt->status, $allowedStatuses, true)) {
            return response()->json([
                'message' => "Cannot finalize: attempt status '{$attempt->status}' does not allow finalization. "
                           . "Only 'submitted' or 'under_review' attempts can be finalized.",
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'teacher_feedback' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // All answers must have a review row with points_awarded set
        $answerIds = StudentAnswer::where('attempt_id', $attemptId)->pluck('id');

        $reviewedCount = StudentAnswerReview::whereIn('student_answer_id', $answerIds)
            ->whereNotNull('points_awarded')
            ->count();

        if ($reviewedCount < $answerIds->count()) {
            $missing = $answerIds->count() - $reviewedCount;
            return response()->json([
                'message' => "Cannot finalize: {$missing} answer(s) still have no points awarded.",
            ], 422);
        }

        // Ticket 10.2 — score source: ALWAYS SUM of student_answer_reviews for manual attempts
        $finalScore = (float) StudentAnswerReview::whereIn('student_answer_id', $answerIds)
            ->sum('points_awarded');

        $totalPoints = (float) $attempt->total_points;

        // Ticket 10.2 — pass/fail recalculation using 60% threshold
        $percentage = $totalPoints > 0 ? round(($finalScore / $totalPoints) * 100, 1) : 0;
        $isPassed   = $percentage >= 60;

        DB::transaction(function () use ($attempt, $finalScore, $request) {
            $attempt->score            = $finalScore;
            $attempt->status           = QuizAttempt::STATUS_REVIEWED;
            $attempt->reviewed_at      = now();
            $attempt->reviewed_by      = request()->user()->id;
            $attempt->teacher_feedback = $request->teacher_feedback ?? $attempt->teacher_feedback;
            $attempt->save();
        });

        return response()->json([
            'message'      => 'Review finalized.',
            'status'       => QuizAttempt::STATUS_REVIEWED,
            'final_score'  => $finalScore,
            'total_points' => $totalPoints,
            'percentage'   => $percentage,
            'is_passed'    => $isPassed,
        ]);
    }

    // =========================================================================
    // POST /teacher/manual-review/attempts/{attemptId}/reopen
    // Reset a reviewed attempt back to under_review.
    //
    // Ticket 10.3 — valid transition: reviewed → under_review only.
    // =========================================================================
    public function reopenAttempt(Request $request, $attemptId): JsonResponse
    {
        // Ticket 10.4 — centralized ownership check
        $result = $this->authorizeAttempt((int) $attemptId, $request->user()->id);
        if ($result instanceof JsonResponse) return $result;
        $attempt = $result;

        // Ticket 10.3 — only reviewed attempts can be reopened
        if ($attempt->status !== QuizAttempt::STATUS_REVIEWED) {
            return response()->json([
                'message' => "Cannot reopen: attempt status '{$attempt->status}' is not 'reviewed'. "
                           . "Only reviewed attempts can be reopened.",
            ], 422);
        }

        $attempt->status      = QuizAttempt::STATUS_UNDER_REVIEW;
        $attempt->reviewed_at = null;
        $attempt->reviewed_by = null;
        $attempt->save();

        return response()->json([
            'message' => 'Attempt reopened for editing.',
            'status'  => QuizAttempt::STATUS_UNDER_REVIEW,
        ]);
    }
}
