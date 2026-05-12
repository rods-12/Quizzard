<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\QuizFullReportExport;
use App\Models\ClassRoom;


class TeacherController extends Controller
{
    public function dashboard(Request $request)
    {
        $teacher = $request->user();

        // Get all quizzes by this teacher
        $quizzes = Quiz::where('teacher_id', $teacher->id)
            ->withCount('questions')
            ->get()
            ->map(function ($quiz) {
                $attemptCount = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('status', QuizAttempt::STATUS_REVIEWED)
                    ->count();

                $avgScore = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('status', QuizAttempt::STATUS_REVIEWED)
                    ->avg('score');

                return [
                    'id'              => $quiz->id,
                    'title'           => $quiz->title,
                    'description'     => $quiz->description,
                    'is_published'    => $quiz->is_published,
                    'questions_count' => $quiz->questions_count,
                    'attempts_count'  => $attemptCount,
                    'has_attempts'    => $attemptCount > 0,
                    'average_score'   => $avgScore ? round($avgScore, 1) : null,
                    'created_at'      => $quiz->created_at,
                ];
            });

        // Get all classes by this teacher
        $classes = ClassRoom::where('teacher_id', $teacher->id)
            ->withCount(['students', 'quizzes'])
            ->get()
            ->map(function ($class) {
                return [
                    'id'             => $class->id,
                    'name'           => $class->name,
                    'description'    => $class->description,
                    'code'           => $class->class_code,
                    'students_count' => $class->students_count,
                    'quizzes_count'  => $class->quizzes_count,
                    'created_at'     => $class->created_at,
                ];
            });

        // Total distinct students with reviewed attempts on this teacher's quizzes
        $quizIds = Quiz::where('teacher_id', $teacher->id)->pluck('id');

        $totalStudents = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->distinct('student_id')
            ->count('student_id');

        // Manual review counts across all teacher's quizzes
        $pendingReviewCount = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->whereIn('status', [
                QuizAttempt::STATUS_SUBMITTED,
                QuizAttempt::STATUS_UNDER_REVIEW,
            ])
            ->count();

        $reviewedCount = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->count();

        return response()->json([
            'teacher' => [
                'id'              => $teacher->id,
                'name'            => $teacher->name,
                'first_name'      => $teacher->first_name,
                'middle_initial'  => $teacher->middle_initial,
                'surname'         => $teacher->surname,
                'email'           => $teacher->email,
                'profile_picture' => $teacher->profile_picture,
            ],
            'quizzes' => $quizzes,
            'classes' => $classes,
            'stats'   => [
                'total_quizzes'        => $quizzes->count(),
                'published_quizzes'    => $quizzes->where('is_published', true)->count(),
                'total_students'       => $totalStudents,
                'total_classes'        => $classes->count(),
                'pending_review_count' => $pendingReviewCount,
                'reviewed_count'       => $reviewedCount,
            ],
        ]);
    }

    // Get all student attempts for a specific quiz
    public function quizResults(Request $request, $quizId)
    {
        $quiz = Quiz::where('id', $quizId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $attempts = QuizAttempt::where('quiz_id', $quizId)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->with('student')
            ->orderBy('completed_at', 'desc')
            ->get();

        $results = $attempts->map(function ($attempt) {
            $percentage = $attempt->total_points > 0
                ? round(($attempt->score / $attempt->total_points) * 100)
                : 0;

            return [
                'attempt_id'             => $attempt->id,
                'student_id'             => $attempt->student->id,
                'student_name'           => $attempt->student->name,
                'student_first_name'     => $attempt->student->first_name,
                'student_middle_initial' => $attempt->student->middle_initial,
                'student_surname'        => $attempt->student->surname,
                'student_email'          => $attempt->student->email,
                'score'                  => $attempt->score,
                'total_points'           => $attempt->total_points,
                'percentage'             => $percentage,
                'is_passed'              => $percentage >= 60,
                'completed_at'           => $attempt->completed_at,
            ];
        });

        $passCount          = $results->where('is_passed', true)->count();
        $failCount          = $results->where('is_passed', false)->count();
        $averagePercentage  = $results->count() > 0 ? round($results->avg('percentage'), 1) : 0;

        return response()->json([
            'quiz' => [
                'id'    => $quiz->id,
                'title' => $quiz->title,
            ],
            'total_attempts'     => $attempts->count(),
            'average_score'      => $attempts->count() > 0 ? round($attempts->avg('score'), 1) : 0,
            'average_percentage' => $averagePercentage,
            'pass_count'         => $passCount,
            'fail_count'         => $failCount,
            'results'            => $results,
        ]);
    }

    public function attemptDetail(Request $request, $quizId, $attemptId)
    {
        $teacherId = $request->user()->id;

        $quiz = Quiz::where('id', $quizId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('quiz_id', $quiz->id)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->with('student')
            ->firstOrFail();

        $studentAnswers = StudentAnswer::where('attempt_id', $attemptId)
            ->with(['question' => fn($q) => $q->with('answerOptions')])
            ->get();

        $percentage = $attempt->total_points > 0
            ? round(($attempt->score / $attempt->total_points) * 100)
            : 0;

        $questionResults = $studentAnswers->map(function ($answer) {
            $question = $answer->question;
            return [
                'id'             => $question->id,
                'question_text'  => $question->question_text,
                'question_type'  => $question->question_type,
                'points'         => $question->points,
                'points_earned'  => $answer->points_earned,
                'is_correct'     => $answer->is_correct,
                'answer_given'   => $answer->answer_given,
                'answer_options' => $question->answerOptions->map(fn($opt) => [
                    'id'          => $opt->id,
                    'option_text' => $opt->option_text,
                    'is_correct'  => $opt->is_correct,
                    'match_pair'  => $opt->match_pair,
                    'order'       => $opt->order,
                ]),
            ];
        });

        return response()->json([
            'quiz' => [
                'id'    => $quiz->id,
                'title' => $quiz->title,
            ],
            'attempt' => [
                'id'           => $attempt->id,
                'score'        => $attempt->score,
                'total_points' => $attempt->total_points,
                'percentage'   => $percentage,
                'completed_at' => $attempt->completed_at,
            ],
            'student' => [
                'id'             => $attempt->student->id,
                'name'           => $attempt->student->name,
                'full_name'      => $attempt->student->name,
                'first_name'     => $attempt->student->first_name,
                'middle_initial' => $attempt->student->middle_initial,
                'surname'        => $attempt->student->surname,
                'email'          => $attempt->student->email,
            ],
            'question_results' => $questionResults,
        ]);
    }

    public function quizAnalytics(Request $request, $quizId)
    {
        $teacherId = $request->user()->id;

        $quiz = Quiz::where('id', $quizId)
            ->where('teacher_id', $teacherId)
            ->with('questions')
            ->firstOrFail();

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->get();

        $attemptCount = $attempts->count();
        $passMark     = 60;

        $averageScore  = $attemptCount > 0 ? round($attempts->avg('score'), 1) : 0;
        $highestScore  = $attemptCount > 0 ? $attempts->max('score') : 0;
        $lowestScore   = $attemptCount > 0 ? $attempts->min('score') : 0;

        $passedCount = 0;
        $percentages = [];

        foreach ($attempts as $attempt) {
            $pct = ($attempt->total_points ?? 0) > 0
                ? round(($attempt->score / $attempt->total_points) * 100, 1)
                : 0;

            $percentages[] = $pct;

            if ($pct >= $passMark) {
                $passedCount++;
            }
        }

        $passRate = $attemptCount > 0
            ? round(($passedCount / $attemptCount) * 100, 1)
            : 0;

        $standardDeviation = 0;
        if ($attemptCount > 0) {
            $mean     = array_sum($percentages) / count($percentages);
            $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $percentages)) / count($percentages);
            $standardDeviation = round(sqrt($variance), 1);
        }

        $difficultyAnalysis = [];

        foreach ($quiz->questions as $index => $question) {
            $answerRows = StudentAnswer::where('question_id', $question->id)
                ->whereIn('attempt_id', $attempts->pluck('id'))
                ->get();

            $totalAnswered = $answerRows->count();
            $correctCount  = $answerRows->where('is_correct', true)->count();
            $correctRate   = $totalAnswered > 0 ? round(($correctCount / $totalAnswered) * 100, 1) : 0;

            $difficulty = $correctRate >= 80 ? 'Easy' : ($correctRate < 50 ? 'Hard' : 'Moderate');

            $difficultyAnalysis[] = [
                'question_id'    => $question->id,
                'question_label' => 'Q' . ($index + 1),
                'question_text'  => $question->question_text,
                'correct_rate'   => $correctRate,
                'difficulty'     => $difficulty,
                'correct_count'  => $correctCount,
                'attempt_count'  => $totalAnswered,
            ];
        }

        $comparisonQuizzes = Quiz::where('teacher_id', $teacherId)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $quizComparison = [];

        foreach ($comparisonQuizzes as $cq) {
            $cqAttempts = QuizAttempt::where('quiz_id', $cq->id)
                ->where('status', QuizAttempt::STATUS_REVIEWED)
                ->get();

            $cqCount   = $cqAttempts->count();
            $cqAvg     = $cqCount > 0 ? round($cqAttempts->avg('score'), 1) : 0;
            $cqPassed  = 0;

            foreach ($cqAttempts as $a) {
                $pct = ($a->total_points ?? 0) > 0 ? (($a->score / $a->total_points) * 100) : 0;
                if ($pct >= $passMark) $cqPassed++;
            }

            $quizComparison[] = [
                'quiz_id'       => $cq->id,
                'quiz_title'    => $cq->title,
                'average_score' => $cqAvg,
                'attempt_count' => $cqCount,
                'pass_rate'     => $cqCount > 0 ? round(($cqPassed / $cqCount) * 100, 1) : 0,
            ];
        }

        return response()->json([
            'summary' => [
                'average_score'      => $averageScore,
                'highest_score'      => $highestScore,
                'lowest_score'       => $lowestScore,
                'attempt_count'      => $attemptCount,
                'pass_rate'          => $passRate,
                'standard_deviation' => $standardDeviation,
            ],
            'difficulty_analysis' => $difficultyAnalysis,
            'quiz_comparison'     => $quizComparison,
        ]);
    }

    public function exportFullReport(Request $request, $quizId)
    {
        $teacherId = $request->user()->id;

        $quiz = Quiz::where('id', $quizId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $attempts = QuizAttempt::where('quiz_id', $quizId)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->with('student')
            ->get();

        $results = $attempts->map(function ($attempt, $index) {
            $percentage = $attempt->total_points > 0
                ? round(($attempt->score / $attempt->total_points) * 100)
                : 0;

            return [
                'rank'           => $index + 1,
                'student_id'     => $attempt->student->id,
                'surname'        => $attempt->student->surname,
                'first_name'     => $attempt->student->first_name,
                'middle_initial' => $attempt->student->middle_initial,
                'gender'         => $attempt->student->gender ?? '',
                'grade_level'    => $attempt->student->grade_level ?? '',
                'section'        => $attempt->student->section ?? '',
                'score'          => $attempt->score,
                'total_points'   => $attempt->total_points,
                'percentage'     => $percentage,
            ];
        });

        $questions = $quiz->questions;

        $analytics = $questions->map(function ($question, $index) use ($attempts) {
            $answers   = StudentAnswer::where('question_id', $question->id)
                ->whereIn('attempt_id', $attempts->pluck('id'))
                ->get();

            $attempted   = $answers->count();
            $correct     = $answers->where('is_correct', true)->count();
            $correctRate = $attempted > 0 ? round(($correct / $attempted) * 100) : 0;

            return [
                'order'           => $index + 1,
                'question_text'   => $question->question_text,
                'question_type'   => $question->question_type,
                'points'          => $question->points,
                'attempted_count' => $attempted,
                'correct_count'   => $correct,
                'correct_rate'    => $correctRate,
                'average_points'  => 0,
                'difficulty'      => $correctRate >= 80 ? 'Easy' : ($correctRate < 50 ? 'Hard' : 'Moderate'),
            ];
        });

        return Excel::download(
            new \App\Exports\QuizFullReportExport($results, $analytics, $quiz->title),
            'quiz_' . $quizId . '_report.xlsx'
        );
    }

    public function exportClassQuizResults(Request $request, $classId, $quizId)
    {
        $teacherId = $request->user()->id;

        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $quiz = Quiz::where('id', $quizId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $isAssigned = $class->quizzes()->where('quiz_id', $quizId)->exists();
        if (!$isAssigned) {
            return response()->json(['success' => false, 'message' => 'Quiz is not assigned to this class.'], 404);
        }

        $totalPoints = (float) $quiz->questions()->sum('points') ?: 0;

        $students = $class->students()
            ->with('studentProfile')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();

        $attempts = QuizAttempt::where('quiz_id', $quizId)
            ->whereIn('student_id', $students->pluck('id'))
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->get()
            ->keyBy('student_id');

        $exportData = $students->map(function ($student, $index) use ($attempts, $totalPoints) {
            $attempt    = $attempts->get($student->id);
            $hasTaken   = $attempt !== null;
            $score      = $hasTaken ? ($attempt->score ?? 0) : 0;
            $percentage = $totalPoints > 0 ? round(($score / $totalPoints) * 100, 1) : 0;

            return [
                'rank'         => $index + 1,
                'student_id'   => $student->studentProfile?->student_id ?? $student->id,
                'first_name'   => $student->first_name,
                'surname'      => $student->surname,
                'score'        => $score,
                'total_points' => $totalPoints,
                'percentage'   => $percentage,
                'status'       => $hasTaken ? 'Taken' : 'Not Taken',
            ];
        });

        $filename = str_replace(' ', '_', $class->name) . '_' . str_replace(' ', '_', $quiz->title) . '_results.xlsx';

        return Excel::download(
            new \App\Exports\ClassQuizResultsExport($exportData, $class->name, $quiz->title),
            $filename
        );
    }
}
