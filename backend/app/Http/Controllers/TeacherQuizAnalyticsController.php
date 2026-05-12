<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Quiz;
use App\Models\QuizAttempt;

class TeacherQuizAnalyticsController extends Controller
{
    public function quizAnalytics(Request $request, $quizId)
    {
        // --- Security ---
        $quiz = Quiz::with(['questions'])->findOrFail($quizId);

        if ($quiz->teacher_id !== auth()->id()) {
            abort(403);
        }

        // --- Quiz metadata ---
        $questionCount = $quiz->questions->count();
        $totalPoints   = $quiz->questions->sum('points');

        // --- Summary cards (reviewed attempts only) ---
        // Phase 3: avgScore folded in here — eliminates a second DB round-trip.
        $summary = DB::table('quiz_attempts')
            ->selectRaw("
                COUNT(*) as total_attempts,
                AVG(score) as avg_score_raw,
                SUM(CASE WHEN total_points > 0 THEN (score / total_points * 100) ELSE 0 END) as pct_sum,
                MAX(score) as highest_score,
                MIN(score) as lowest_score,
                SUM(CASE WHEN total_points > 0 AND (score / total_points * 100) >= 60 THEN 1 ELSE 0 END) as passed_count,
                SUM(CASE WHEN total_points = 0 OR (score / total_points * 100) < 60 THEN 1 ELSE 0 END) as failed_count
            ")
            ->where('quiz_id', $quizId)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->first();

        $totalAttempts = (int) $summary->total_attempts;

        $avgScore = $totalAttempts > 0 ? round($summary->avg_score_raw, 2) : null;

        $avgPct = $totalAttempts > 0
            ? round($summary->pct_sum / $totalAttempts, 2)
            : null;

        $passRate = $totalAttempts > 0
            ? round(($summary->passed_count / $totalAttempts) * 100, 2)
            : null;

        $failRate = $totalAttempts > 0
            ? round(($summary->failed_count / $totalAttempts) * 100, 2)
            : null;

        // --- Score distribution ---
        $allPcts = DB::table('quiz_attempts')
            ->selectRaw("CASE WHEN total_points > 0 THEN ROUND((score / total_points) * 100, 2) ELSE 0 END as pct")
            ->where('quiz_id', $quizId)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
            ->pluck('pct');

        $distribution = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
        foreach ($allPcts as $pct) {
            if ($pct <= 20)       $distribution['0-20']++;
            elseif ($pct <= 40)   $distribution['21-40']++;
            elseif ($pct <= 60)   $distribution['41-60']++;
            elseif ($pct <= 80)   $distribution['61-80']++;
            else                  $distribution['81-100']++;
        }

        // --- Question analytics ---
        $questions = DB::table('questions')
            ->leftJoin('student_answers', function ($join) {
                $join->on('student_answers.question_id', '=', 'questions.id')
                     ->join('quiz_attempts as qa_inner', function ($j2) {
                         $j2->on('qa_inner.id', '=', 'student_answers.attempt_id')
                            ->where('qa_inner.status', '=', QuizAttempt::STATUS_REVIEWED);
                     });
            })
            ->leftJoin('student_answer_reviews', 'student_answer_reviews.student_answer_id', '=', 'student_answers.id')
            ->selectRaw("
                questions.id,
                questions.question_text,
                questions.question_type,
                questions.points,
                COUNT(student_answers.id) as total_answers,
                SUM(CASE
                    WHEN student_answers.id IS NOT NULL
                     AND COALESCE(student_answer_reviews.points_awarded, student_answers.points_earned) >= questions.points
                    THEN 1 ELSE 0
                END) as correct_count,
                AVG(COALESCE(student_answer_reviews.points_awarded, student_answers.points_earned)) as avg_points_earned
            ")
            ->where('questions.quiz_id', $quizId)
            ->groupBy('questions.id', 'questions.question_text', 'questions.question_type', 'questions.points')
            ->orderBy('questions.order')
            ->get()
            ->map(function ($q) {
                $q->correct_rate   = $q->total_answers > 0 ? round(($q->correct_count / $q->total_answers) * 100, 2) : null;
                $q->incorrect_rate = $q->correct_rate !== null ? round(100 - $q->correct_rate, 2) : null;
                $q->avg_points     = $q->avg_points_earned !== null ? round($q->avg_points_earned, 2) : null;
                $q->difficulty     = match(true) {
                    $q->correct_rate === null => 'N/A',
                    $q->correct_rate >= 80    => 'Easy',
                    $q->correct_rate >= 50    => 'Moderate',
                    default                   => 'Hard',
                };
                return $q;
            });

        // --- Top 10 highest & lowest performing students ---
        $topStudentsBase = DB::table('quiz_attempts')
            ->join('users', 'users.id', '=', 'quiz_attempts.student_id')
            ->selectRaw("
                users.name,
                quiz_attempts.score,
                quiz_attempts.total_points,
                CASE WHEN quiz_attempts.total_points > 0
                     THEN ROUND((quiz_attempts.score / quiz_attempts.total_points) * 100, 2)
                     ELSE 0
                END as percentage,
                quiz_attempts.reviewed_at
            ")
            ->where('quiz_attempts.quiz_id', $quizId)
            ->where('quiz_attempts.status', QuizAttempt::STATUS_REVIEWED);

        $topPerformers    = (clone $topStudentsBase)->orderByDesc('percentage')->limit(10)->get();
        $lowestPerformers = (clone $topStudentsBase)->orderBy('percentage')->limit(10)->get();

        return view('teacher.analytics.quiz', compact(
            'quiz',
            'questionCount',
            'totalPoints',
            'totalAttempts',
            'avgScore',
            'avgPct',
            'summary',
            'passRate',
            'failRate',
            'topPerformers',
            'lowestPerformers',
            'distribution',
            'questions',
        ));
    }

    public function globalAnalytics(Request $request)
    {
        $teacherId = auth()->id();

        // --- Phase 3: Validate all date inputs before using them ---
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'tl_start'   => 'nullable|date',
            'tl_end'     => 'nullable|date|after_or_equal:tl_start',
            'student_id' => 'nullable|integer|min:1',
            'class_id'   => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // --- A & B: Top/Bottom 10 quizzes by pass rate ---
        // Phase 3: Cached 5 minutes per teacher. Does not vary by date filter
        // (these are teacher-wide totals, not date-filtered).
        $quizPassRates = Cache::remember(
            "teacher_{$teacherId}_quiz_pass_rates",
            300,
            function () use ($teacherId) {
                return DB::table('quizzes')
                    ->join('quiz_attempts', function ($join) {
                        $join->on('quiz_attempts.quiz_id', '=', 'quizzes.id')
                            ->where('quiz_attempts.status', '=', QuizAttempt::STATUS_REVIEWED);
                    })
                    ->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('class_quizzes')
                            ->whereColumn('class_quizzes.quiz_id', 'quizzes.id');
                    })
                    ->selectRaw("
                        quizzes.id,
                        quizzes.title,
                        COUNT(quiz_attempts.id) as total_attempts,
                        AVG(CASE WHEN quiz_attempts.total_points > 0
                            THEN (quiz_attempts.score / quiz_attempts.total_points) * 100
                            ELSE 0 END) as avg_pct,
                        SUM(CASE WHEN quiz_attempts.total_points > 0
                            AND (quiz_attempts.score / quiz_attempts.total_points * 100) >= 60
                            THEN 1 ELSE 0 END) as passed_count
                    ")
                    ->where('quizzes.teacher_id', $teacherId)
                    ->groupBy('quizzes.id', 'quizzes.title')
                    ->havingRaw('COUNT(quiz_attempts.id) >= 5')
                    ->get()
                    ->map(function ($q) {
                        $q->pass_rate = $q->total_attempts > 0
                            ? round(($q->passed_count / $q->total_attempts) * 100, 2)
                            : 0;
                        $q->avg_pct = round($q->avg_pct, 2);
                        return $q;
                    });
            }
        );

        $topQuizzes    = $quizPassRates->sortByDesc('pass_rate')->take(10)->values();
        $bottomQuizzes = $quizPassRates->sortBy('pass_rate')->take(10)->values();

        // --- C & D: Overall performance + monthly trend ---
        // These ARE date-filtered, so they are never cached.
        $overallBase = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->where('quizzes.teacher_id', $teacherId)
            ->where('quiz_attempts.status', QuizAttempt::STATUS_REVIEWED)
            ->when($startDate, fn($q) => $q->whereDate('quiz_attempts.reviewed_at', '>=', $startDate))
            ->when($endDate,   fn($q) => $q->whereDate('quiz_attempts.reviewed_at', '<=', $endDate));

        $overallStats = (clone $overallBase)
            ->selectRaw("
                COUNT(*) as total_attempts,
                AVG(CASE WHEN quiz_attempts.total_points > 0
                    THEN (quiz_attempts.score / quiz_attempts.total_points) * 100
                    ELSE 0 END) as avg_pct,
                SUM(CASE WHEN quiz_attempts.total_points > 0
                    AND (quiz_attempts.score / quiz_attempts.total_points * 100) >= 60
                    THEN 1 ELSE 0 END) as passed_count
            ")
            ->first();

        $overallTotal    = (int) $overallStats->total_attempts;
        $overallAvgPct   = $overallTotal > 0 ? round($overallStats->avg_pct, 2) : null;
        $overallPassRate = $overallTotal > 0
            ? round(($overallStats->passed_count / $overallTotal) * 100, 2)
            : null;

        // D: Monthly trend
        $monthlyTrend = (clone $overallBase)
            ->selectRaw("
                DATE_FORMAT(quiz_attempts.reviewed_at, '%Y-%m') as month,
                AVG(CASE WHEN quiz_attempts.total_points > 0
                    THEN (quiz_attempts.score / quiz_attempts.total_points) * 100
                    ELSE 0 END) as avg_pct
            ")
            ->groupByRaw("DATE_FORMAT(quiz_attempts.reviewed_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(quiz_attempts.reviewed_at, '%Y-%m') ASC")
            ->get();

        // --- E: Student performance timeline ---
        $selectedStudent = $request->input('student_id');
        $selectedClass   = $request->input('class_id');
        $tlStart         = $request->input('tl_start');
        $tlEnd           = $request->input('tl_end');

        // Phase 3: Cached 5 minutes per teacher. Student list doesn't change
        // frequently and is expensive (3-table join with GROUP BY).
        $studentList = Cache::remember(
            "teacher_{$teacherId}_student_list",
            300,
            function () use ($teacherId) {
                return DB::table('users')
                    ->join('quiz_attempts', 'quiz_attempts.student_id', '=', 'users.id')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                    ->where('quizzes.teacher_id', $teacherId)
                    ->where('quiz_attempts.status', QuizAttempt::STATUS_REVIEWED)
                    ->selectRaw('users.id, users.first_name, users.middle_initial, users.surname, users.name as full_name')
                    ->groupBy('users.id', 'users.first_name', 'users.middle_initial', 'users.surname', 'users.name')
                    ->orderBy('users.surname')
                    ->get();
            }
        );

        // Phase 3: Cached 5 minutes per teacher.
        $classList = Cache::remember(
            "teacher_{$teacherId}_class_list",
            300,
            function () use ($teacherId) {
                return DB::table('classes')
                    ->where('teacher_id', $teacherId)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        );

        // Timeline data (only when a student is selected — never cached, always fresh).
        $timelineData = collect();
        if ($selectedStudent) {
            $tlQuery = DB::table('quiz_attempts')
                ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                ->where('quizzes.teacher_id', $teacherId)
                ->where('quiz_attempts.student_id', $selectedStudent)
                ->where('quiz_attempts.status', QuizAttempt::STATUS_REVIEWED)
                ->when($selectedClass, function ($q) use ($selectedClass) {
                    $q->whereExists(function ($sub) use ($selectedClass) {
                        $sub->select(DB::raw(1))
                            ->from('class_quizzes')
                            ->whereColumn('class_quizzes.quiz_id', 'quizzes.id')
                            ->where('class_quizzes.class_id', $selectedClass);
                    });
                })
                ->when($tlStart, fn($q) => $q->whereDate('quiz_attempts.reviewed_at', '>=', $tlStart))
                ->when($tlEnd,   fn($q) => $q->whereDate('quiz_attempts.reviewed_at', '<=', $tlEnd))
                ->selectRaw("
                    quizzes.title,
                    DATE_FORMAT(quiz_attempts.reviewed_at, '%Y-%m-%d') as reviewed_date,
                    CASE WHEN quiz_attempts.total_points > 0
                        THEN ROUND((quiz_attempts.score / quiz_attempts.total_points) * 100, 2)
                        ELSE 0 END as pct
                ")
                ->orderBy('quiz_attempts.reviewed_at')
                ->get();

            $timelineData = $tlQuery;
        }

        // --- F: At-risk students ---
        // Phase 3: Cached 5 minutes per teacher.
        $atRisk = Cache::remember(
            "teacher_{$teacherId}_at_risk",
            300,
            function () use ($teacherId) {
                return DB::table('users')
                    ->join('quiz_attempts', 'quiz_attempts.student_id', '=', 'users.id')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                    ->where('quizzes.teacher_id', $teacherId)
                    ->where('quiz_attempts.status', QuizAttempt::STATUS_REVIEWED)
                    ->selectRaw("
                        users.id,
                        users.first_name, users.middle_initial, users.surname, users.name as full_name,
                        COUNT(quiz_attempts.id) as total_attempts,
                        AVG(CASE WHEN quiz_attempts.total_points > 0
                            THEN (quiz_attempts.score / quiz_attempts.total_points) * 100
                            ELSE 0 END) as avg_pct,
                        SUM(CASE WHEN quiz_attempts.total_points > 0
                            AND (quiz_attempts.score / quiz_attempts.total_points * 100) < 60
                            THEN 1 ELSE 0 END) as fail_count
                    ")
                    ->groupBy('users.id', 'users.first_name', 'users.middle_initial', 'users.surname', 'users.name')
                    ->havingRaw('avg_pct < 50 OR fail_count >= 2')
                    ->orderBy('avg_pct')
                    ->limit(10)
                    ->get()
                    ->map(fn($s) => tap($s, fn($s) => $s->avg_pct = round($s->avg_pct, 2)));
            }
        );

        // --- G: Strongest students ---
        // Phase 3: Cached 5 minutes per teacher.
        $strongest = Cache::remember(
            "teacher_{$teacherId}_strongest",
            300,
            function () use ($teacherId) {
                return DB::table('users')
                    ->join('quiz_attempts', 'quiz_attempts.student_id', '=', 'users.id')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                    ->where('quizzes.teacher_id', $teacherId)
                    ->where('quiz_attempts.status', QuizAttempt::STATUS_REVIEWED)
                    ->selectRaw("
                        users.id,
                        users.first_name, users.middle_initial, users.surname, users.name as full_name,
                        COUNT(quiz_attempts.id) as total_attempts,
                        AVG(CASE WHEN quiz_attempts.total_points > 0
                            THEN (quiz_attempts.score / quiz_attempts.total_points) * 100
                            ELSE 0 END) as avg_pct
                    ")
                    ->groupBy('users.id', 'users.first_name', 'users.middle_initial', 'users.surname', 'users.name')
                    ->havingRaw('avg_pct >= 80 AND COUNT(quiz_attempts.id) >= 2')
                    ->orderByRaw('avg_pct DESC')
                    ->limit(10)
                    ->get()
                    ->map(fn($s) => tap($s, fn($s) => $s->avg_pct = round($s->avg_pct, 2)));
            }
        );

        return view('teacher.analytics.global', compact(
            'topQuizzes',
            'bottomQuizzes',
            'overallTotal',
            'overallAvgPct',
            'overallPassRate',
            'startDate',
            'endDate',
            'monthlyTrend',
            'studentList',
            'classList',
            'selectedStudent',
            'selectedClass',
            'tlStart',
            'tlEnd',
            'timelineData',
            'atRisk',
            'strongest',
        ));
    }
}
