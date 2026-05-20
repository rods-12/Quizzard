<?php
// app/Http/Controllers/AdminAnalyticsController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Throwable;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\Question;
use App\Exports\StudentAnalyticsExport;
use App\Exports\StudentProfileExport;
use App\Exports\QuizPerformanceExport;
use App\Exports\ClassAnalyticsExport;
use App\Exports\ClassShowExport;
use Maatwebsite\Excel\Facades\Excel;

class AdminAnalyticsController extends Controller
{
    // ════════════════════════════════════════════════════════════
    // OVERVIEW
    // ════════════════════════════════════════════════════════════

    public function overview(Request $request)
    {
        try {
        $filters = $this->analyticsFilters($request);
        $dateFrom = $filters['date_from'];
        $dateTo   = $filters['date_to'];

        /** @var \App\Models\User $authUser */
        $authUser     = auth()->user();
        $isSuperAdmin = $authUser->role === 'superadmin';
        $passingCase = $this->passingCaseSql();

        // ── KPI Cards ────────────────────────────────────────────
        $totalStudents = User::where('role', 'student')->where('status', 'active')->count();
        $totalTeachers = User::where('role', 'teacher')->where('status', 'active')->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $activeAdmins = User::where('role', 'admin')->where('status', 'active')->count();
        $pendingAdmins = User::where('role', 'admin')->where('status', 'pending')->count();
        $deactivatedAdmins = User::where('role', 'admin')->where('status', 'deactivated')->count();
        $totalQuizzes  = Quiz::where('is_published', true)->count();

        $attemptsInPeriod = $this->applyCompletedDateFilter(
            QuizAttempt::whereNotNull('completed_at'),
            $dateFrom,
            $dateTo
        );

        $totalAttempts  = (clone $attemptsInPeriod)->count();
        $systemPassRate = $totalAttempts > 0
            ? (clone $attemptsInPeriod)->selectRaw("SUM({$passingCase}) as passed")->value('passed') / $totalAttempts * 100
            : 0;
        $systemAvgScore = $totalAttempts > 0
            ? (clone $attemptsInPeriod)->selectRaw('AVG(score / NULLIF(total_points,0) * 100) as avg')->value('avg') ?? 0
            : 0;

        $activeStudents = User::where('role', 'student')->where('status', 'active')
            ->whereHas('quizAttempts', fn($q) => $this->applyCompletedDateFilter($q->whereNotNull('completed_at'), $dateFrom, $dateTo))
            ->count();

        $activeTeachers = User::where('role', 'teacher')->where('status', 'active')
            ->whereHas('quizzes.attempts', fn($q) => $this->applyCompletedDateFilter($q->whereNotNull('completed_at'), $dateFrom, $dateTo))
            ->count();

        $inactiveStudents = max($totalStudents - $activeStudents, 0);
        $inactiveTeachers = max($totalTeachers - $activeTeachers, 0);
        $studentParticipationRate = $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0;
        $teacherActivityRate = $totalTeachers > 0 ? round(($activeTeachers / $totalTeachers) * 100, 1) : 0;

        $mostActiveClass = ClassRoom::withCount(['quizzes as attempt_count' => function ($q) use ($dateFrom, $dateTo) {
            $q->whereHas('attempts', fn($a) => $this->applyCompletedDateFilter($a, $dateFrom, $dateTo));
        }])->orderByDesc('attempt_count')->first();

        $mostActiveTeacher = User::where('role', 'teacher')
            ->withCount(['quizzes as attempt_count' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereHas('attempts', fn($a) => $this->applyCompletedDateFilter($a, $dateFrom, $dateTo));
            }])->orderByDesc('attempt_count')->first();

        // ── Activity Chart (daily attempts last 30 days) ─────────
        $activityData = QuizAttempt::selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
            ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
            ->whereNotNull('completed_at')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $activityLabels = collect();
        $activityValues = collect();
        if ($dateFrom && $dateTo) {
            $current = Carbon::parse($dateFrom);
            $end     = Carbon::parse($dateTo);
            while ($current->lte($end)) {
                $key = $current->toDateString();
                $activityLabels->push($current->format('M d'));
                $activityValues->push($activityData->get($key, 0));
                $current->addDay();
            }
        } else {
            $activityData->each(function ($count, $date) use ($activityLabels, $activityValues) {
                $activityLabels->push(Carbon::parse($date)->format('M d, Y'));
                $activityValues->push($count);
            });
        }

        // ── System Donut (Pass vs Fail) ──────────────────────────
        $systemPassed = (int) ((clone $attemptsInPeriod)->selectRaw("SUM({$passingCase}) as passed")->value('passed') ?? 0);
        $systemFailed = $totalAttempts - $systemPassed;

        // ── Class stacked bar ────────────────────────────────────
        $classBarData = ClassRoom::with('quizzes')->get()->map(function ($class) use ($dateFrom, $dateTo) {
            $quizIds  = $class->quizzes->pluck('id');
            $studentIds = $class->students()->pluck('users.id');
            $attempts = QuizAttempt::whereIn('quiz_id', $quizIds)
                ->whereIn('student_id', $studentIds)
                ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->whereNotNull('completed_at')
                ->get();
            return [
                'name'   => $class->name,
                'passed' => $attempts->filter(fn($attempt) => $this->attemptIsPassing($attempt))->count(),
                'failed' => $attempts->filter(fn($attempt) => !$this->attemptIsPassing($attempt))->count(),
            ];
        })->filter(fn($c) => $c['passed'] + $c['failed'] > 0)->values();

        // ── Quick Leaderboard: Top 5 Students ────────────────────
        $topStudents = $this->studentQuery($dateFrom, $dateTo)
            ->having('attempt_count', '>', 0)
            ->orderByDesc('avg_score')
            ->take(5)
            ->get();

        // ── Quick Leaderboard: At-Risk Students ──────────────────
        $atRiskStudents = $this->studentQuery($dateFrom, $dateTo)
            ->having('attempt_count', '>', 0)
            ->orderBy('avg_score')
            ->take(5)
            ->get();
        $topStudentIdsForOverview = $topStudents->pluck('id')->all();
        $atRiskStudents = $atRiskStudents
            ->reject(fn($student) => in_array($student->id, $topStudentIdsForOverview, true))
            ->values();

        // ── Quick Leaderboard: Top 5 Quizzes ─────────────────────
        $topQuizzes = $this->quizQuery($dateFrom, $dateTo)
            ->having('attempt_count', '>', 0)
            ->orderByDesc('pass_rate')
            ->take(5)
            ->get();

        // ── Insight Cards ────────────────────────────────────────
        $insights = $this->generateInsights($dateFrom, $dateTo);

        // ── Period-over-period change ────────────────────────────
        $activityChange = null;
        if ($dateFrom && $dateTo) {
            $periodDays   = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) ?: 1;
            $prevDateTo   = Carbon::parse($dateFrom)->subDay()->toDateString();
            $prevDateFrom = Carbon::parse($prevDateTo)->subDays($periodDays)->toDateString();

            $prevAttempts = QuizAttempt::whereBetween('completed_at', [
                $prevDateFrom . ' 00:00:00', $prevDateTo . ' 23:59:59',
            ])->whereNotNull('completed_at')->count();

            $activityChange = $prevAttempts > 0
                ? round((($totalAttempts - $prevAttempts) / $prevAttempts) * 100, 1)
                : null;
        }

        $from = $dateFrom ? Carbon::parse($dateFrom) : null;
        $to = $dateTo ? Carbon::parse($dateTo) : null;
        $totalClasses = ClassRoom::count();
        $attemptChange = $activityChange;
        $systemPassRate = round($systemPassRate, 1);
        $systemAvgScore = round($systemAvgScore, 1);
        $passCount = $systemPassed;
        $systemFail = $systemFailed;

        $attemptsOverTime = collect($activityData)->map(
            fn($count, $date) => (object) ['date' => Carbon::parse($date)->format('M d'), 'count' => $count]
        )->values();

        $classBars = $classBarData->map(fn($class) => [
            'name' => $class['name'],
            'pass' => $class['passed'],
            'fail' => $class['failed'],
        ]);

        $formatStudent = fn($student) => [
            'id' => $student->id,
            'name' => $student->full_name ?: $student->name ?: $student->email,
            'total' => (int) $student->attempt_count,
            'avg_pct' => round((float) $student->avg_score, 1),
            'pass_rate' => round((float) $student->pass_rate, 1),
        ];

        $top5Students = $topStudents->map($formatStudent);
        $bottom5Students = $atRiskStudents->map($formatStudent);

        $top5Quizzes = $topQuizzes->map(fn($quiz) => [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'total_attempts' => (int) $quiz->attempt_count,
            'teacher_name' => $quiz->teacher?->name ?? 'Unassigned',
            'pass_rate' => round((float) $quiz->pass_rate, 1),
        ]);

        $criticalInsights = collect([
            [
                'label' => 'Students needing intervention',
                'value' => $bottom5Students->where('avg_pct', '<', 75)->count(),
                'tone' => $bottom5Students->where('avg_pct', '<', 60)->count() > 0 ? 'danger' : 'warning',
                'support' => 'Students below target may need remediation or adviser follow-up.',
                'link' => route('admin.analytics.students', ['sort' => 'avg_score_asc']),
            ],
            [
                'label' => 'Teacher activity coverage',
                'value' => $teacherActivityRate . '%',
                'tone' => $teacherActivityRate >= 80 ? 'good' : ($teacherActivityRate >= 50 ? 'warning' : 'danger'),
                'support' => 'Low coverage can indicate inactive classes or teachers who need support.',
                'link' => route('admin.analytics.teachers'),
            ],
            [
                'label' => 'Student participation',
                'value' => $studentParticipationRate . '%',
                'tone' => $studentParticipationRate >= 80 ? 'good' : ($studentParticipationRate >= 50 ? 'warning' : 'danger'),
                'support' => 'Participation gaps help identify classes that need reminders or schedule review.',
                'link' => route('admin.analytics.students'),
            ],
        ]);

        return view('admin.analytics.overview', compact(
            'dateFrom', 'dateTo', 'isSuperAdmin',
            'totalStudents', 'totalTeachers', 'totalAdmins', 'activeAdmins',
            'pendingAdmins', 'deactivatedAdmins', 'totalQuizzes', 'totalAttempts',
            'systemPassRate', 'systemAvgScore',
            'mostActiveClass', 'mostActiveTeacher',
            'activityLabels', 'activityValues',
            'systemPassed', 'systemFailed',
            'classBarData',
            'topStudents', 'atRiskStudents', 'topQuizzes',
            'insights', 'activityChange',
            'from', 'to', 'totalClasses', 'attemptChange',
            'activeStudents', 'activeTeachers', 'inactiveStudents', 'inactiveTeachers',
            'studentParticipationRate', 'teacherActivityRate', 'criticalInsights',
            'attemptsOverTime', 'passCount', 'systemFail',
            'classBars', 'top5Students', 'bottom5Students', 'top5Quizzes',
            'filters'
        ));
        } catch (Throwable $exception) {
            return $this->analyticsError('Overview', $exception);
        }
    }

    // ════════════════════════════════════════════════════════════
    // STUDENTS
    // ════════════════════════════════════════════════════════════

    public function students(Request $request)
    {
        try {
        $filters = $this->analyticsFilters($request);
        $dateFrom   = $filters['date_from'];
        $dateTo     = $filters['date_to'];
        $gradeLevel = $filters['grade_level'];
        $quizId     = $filters['quiz_id'];
        $classId    = $filters['class_id'];
        $sort       = $filters['sort'] ?: 'avg_score_desc';
        $search     = $filters['search'];

        $sortMap = [
            'avg_score_desc'  => ['avg_score',     'desc'],
            'avg_score_asc'   => ['avg_score',     'asc'],
            'pass_rate_desc'  => ['pass_rate',     'desc'],
            'attempts_desc'   => ['attempt_count', 'desc'],
            'name_asc'        => ['full_name',     'asc'],
        ];
        [$sortCol, $sortDir] = $sortMap[$sort] ?? ['avg_score', 'desc'];

        $base = $this->studentQuery($dateFrom, $dateTo, $gradeLevel, $quizId, $classId);

        // KPIs
        $all            = (clone $base)->get();
        $attemptedStudents = $all->where('attempt_count', '>', 0)->values();
        $topStudents = $attemptedStudents->sortByDesc('avg_score')->take(10)->values();
        $topStudentIds = $topStudents->pluck('id')->all();
        $bottomStudents = $attemptedStudents
            ->reject(fn($student) => in_array($student->id, $topStudentIds, true))
            ->sortBy('avg_score')
            ->take(10)
            ->values();
        $totalStudents  = $all->count();
        $activeStudents = $all->where('attempt_count', '>', 0)->count();
        $avgScore       = $all->where('attempt_count', '>', 0)->avg('avg_score') ?? 0;
        $passRate       = $all->where('attempt_count', '>', 0)->avg('pass_rate') ?? 0;

        // Score distribution buckets
        $dist = [0, 0, 0, 0, 0];
        foreach ($all->where('attempt_count', '>', 0) as $s) {
            $score = $s->avg_score ?? 0;
            if ($score <= 20)      $dist[0]++;
            elseif ($score <= 40)  $dist[1]++;
            elseif ($score <= 60)  $dist[2]++;
            elseif ($score <= 80)  $dist[3]++;
            else                   $dist[4]++;
        }
        $scoreDistribution = $dist;

        // Grade performance
        $gradePerformance = $all->where('attempt_count', '>', 0)
            ->filter(fn($s) => $s->grade_level)
            ->groupBy('grade_level')
            ->map(fn($g) => round($g->avg('avg_score'), 1))
            ->sortKeys()
            ->toArray();

        // Grade levels for filter dropdown
        $gradeLevels = StudentProfile::distinct()->pluck('grade_level')->filter()->sort()->values();
        $classesForFilter = ClassRoom::orderBy('name')->get(['id', 'name']);
        $quizzesForFilter = Quiz::orderBy('title')->get(['id', 'title']);
        $selectedQuiz = $quizId ? Quiz::find($quizId) : null;
        $selectedClass = $classId ? ClassRoom::find($classId) : null;
        $basisLabel = $selectedQuiz
            ? 'Quiz: ' . $selectedQuiz->title
            : ($selectedClass ? 'Class: ' . $selectedClass->name : 'All quizzes and classes');

        // Paginated full table
        $allStudents = $this->studentQuery($dateFrom, $dateTo, $gradeLevel, $quizId, $classId)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereRaw("TRIM(CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.middle_initial,''), ' ', COALESCE(users.surname,''))) like ?", ["%{$search}%"])
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortCol, $sortDir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.analytics.students.index', compact(
            'topStudents', 'bottomStudents', 'allStudents',
            'totalStudents', 'activeStudents', 'avgScore', 'passRate',
            'scoreDistribution', 'gradePerformance', 'gradeLevels',
            'classesForFilter', 'quizzesForFilter', 'selectedQuiz', 'selectedClass', 'basisLabel',
            'dateFrom', 'dateTo', 'filters'
        ));
        } catch (Throwable $exception) {
            return $this->analyticsError('Students', $exception);
        }
    }

    public function studentShow(User $user)
    {
        abort_if($user->role !== 'student', 404);

        $profile = $user->studentProfile;

        // All attempts (paginated)
        $attempts = $user->quizAttempts()
            ->with(['quiz.classes'])
            ->orderByDesc('completed_at')
            ->paginate(15);

        // Stats from all completed attempts
        $allAttempts = $user->quizAttempts()->whereNotNull('completed_at')->get();
        $stats = [
            'total_attempts' => $allAttempts->count(),
            'avg_score'      => $allAttempts->count()
                ? $allAttempts->avg(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0)
                : 0,
            'pass_rate'      => $allAttempts->count()
                ? ($allAttempts->filter(fn($attempt) => $this->attemptIsPassing($attempt))->count() / $allAttempts->count()) * 100
                : 0,
            'best_score'     => $allAttempts->count()
                ? $allAttempts->max(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0)
                : 0,
            'worst_score'    => $allAttempts->count()
                ? $allAttempts->min(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0)
                : 0,
            'passed'         => $allAttempts->filter(fn($attempt) => $this->attemptIsPassing($attempt))->count(),
        ];

        // Score trend (chronological)
        $trendAttempts = $user->quizAttempts()
            ->whereNotNull('completed_at')
            ->orderBy('completed_at')
            ->with('quiz')
            ->get();

        $trendLabels = $trendAttempts->map(fn($a) =>
            ($a->quiz->title ?? 'Quiz') . ' ' . Carbon::parse($a->completed_at)->format('m/d')
        )->values()->toArray();

        $trendData = $trendAttempts->map(fn($a) =>
            $a->total_points > 0 ? round(($a->score / $a->total_points) * 100, 1) : 0
        )->values()->toArray();

        // Performance by class (radar)
        $classPerformance = ClassRoom::whereHas('students', fn($q) => $q->where('users.id', $user->id))
            ->whereHas('quizzes.attempts', fn($q) => $q
                ->where('quiz_attempts.student_id', $user->id)
                ->whereNotNull('quiz_attempts.completed_at'))
            ->get()
            ->map(function ($class) use ($user) {
                $quizIds  = $class->quizzes()->pluck('quizzes.id');
                $attempts = $user->quizAttempts()
                    ->whereIn('quiz_id', $quizIds)
                    ->whereNotNull('completed_at')
                    ->get();
                return (object) [
                    'class_name' => $class->name,
                    'attempt_count' => $attempts->count(),
                    'avg_score'  => $attempts->count()
                        ? $attempts->avg(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0)
                        : 0,
                ];
            })
            ->filter(fn($class) => $class->attempt_count > 0)
            ->values();

        // Weak areas: questions answered wrong most often
        $weakAreas = DB::table('student_answers as sa')
            ->join('questions as q', 'q.id', '=', 'sa.question_id')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'sa.attempt_id')
            ->join('quizzes', 'quizzes.id', '=', 'qa.quiz_id')
            ->where('qa.student_id', $user->id)
            ->where('sa.is_correct', false)
            ->select(
                'q.question_text',
                'quizzes.title as quiz_title',
                DB::raw('COUNT(*) as wrong_count'),
                DB::raw('(SELECT COUNT(*) FROM student_answers sa2
                           JOIN quiz_attempts qa2 ON qa2.id = sa2.attempt_id
                           WHERE qa2.student_id = qa.student_id
                           AND sa2.question_id = sa.question_id) as total_seen')
            )
            ->groupBy('sa.question_id', 'q.question_text', 'quizzes.title', 'qa.student_id')
            ->orderByDesc('wrong_count')
            ->limit(10)
            ->get();

        // System rank
        $allStudentScores = User::where('role', 'student')->where('status', 'active')
            ->get()
            ->map(fn($s) => [
                'id'    => $s->id,
                'score' => $s->quizAttempts()->whereNotNull('completed_at')->get()
                    ->avg(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0) ?? 0,
            ])
            ->sortByDesc('score')
            ->values();

        $systemRank = ($allStudentScores->search(fn($s) => $s['id'] === $user->id) !== false)
            ? $allStudentScores->search(fn($s) => $s['id'] === $user->id) + 1
            : null;
        $systemTotalStudents = $allStudentScores->count();

        // Class rank
        $classRank = $classTotalStudents = null;
        if ($profile && $profile->section) {
            $sectionStudents = User::where('role', 'student')
                ->whereHas('studentProfile', fn($q) => $q
                    ->where('section', $profile->section)
                    ->where('grade_level', $profile->grade_level))
                ->get()
                ->map(fn($s) => [
                    'id'    => $s->id,
                    'score' => $s->quizAttempts()->whereNotNull('completed_at')->get()
                        ->avg(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0) ?? 0,
                ])
                ->sortByDesc('score')
                ->values();

            $classRank = ($sectionStudents->search(fn($s) => $s['id'] === $user->id) !== false)
                ? $sectionStudents->search(fn($s) => $s['id'] === $user->id) + 1
                : null;
            $classTotalStudents = $sectionStudents->count();
        }

        $student = $user;

        return view('admin.analytics.students.show', compact(
            'student', 'profile', 'attempts', 'stats',
            'trendLabels', 'trendData', 'classPerformance', 'weakAreas',
            'systemRank', 'systemTotalStudents', 'classRank', 'classTotalStudents'
        ));
    }

    public function exportStudents(Request $request)
    {
        $dateFrom   = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo     = $request->input('date_to',   now()->toDateString());
        $gradeLevel = $request->input('grade_level');
        $quizId     = $request->integer('quiz_id') ?: null;
        $classId    = $request->integer('class_id') ?: null;

        $students = $this->studentQuery($dateFrom, $dateTo, $gradeLevel, $quizId, $classId)->get();
        $filters  = ['date_from' => $dateFrom, 'date_to' => $dateTo, 'grade_level' => $gradeLevel, 'quiz_id' => $quizId, 'class_id' => $classId];

        $filename = 'admin_student_analytics_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new StudentAnalyticsExport($students, $filters), $filename);
    }

    public function exportStudentProfile(User $user)
    {
        abort_if($user->role !== 'student', 404);

        $allAttempts = $user->quizAttempts()->whereNotNull('completed_at')->get();
        $stats = [
            'total_attempts' => $allAttempts->count(),
            'avg_score'      => $allAttempts->count()
                ? $allAttempts->avg(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0) : 0,
            'pass_rate'      => $allAttempts->count()
                ? ($allAttempts->filter(fn($attempt) => $this->attemptIsPassing($attempt))->count() / $allAttempts->count()) * 100 : 0,
            'best_score'     => $allAttempts->count()
                ? $allAttempts->max(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0) : 0,
            'worst_score'    => $allAttempts->count()
                ? $allAttempts->min(fn($a) => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0) : 0,
            'passed'         => $allAttempts->filter(fn($attempt) => $this->attemptIsPassing($attempt))->count(),
        ];

        $attempts = $user->quizAttempts()->with(['quiz.classes'])->orderByDesc('completed_at')->get();

        $weakAreas = DB::table('student_answers as sa')
            ->join('questions as q', 'q.id', '=', 'sa.question_id')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'sa.attempt_id')
            ->join('quizzes', 'quizzes.id', '=', 'qa.quiz_id')
            ->where('qa.student_id', $user->id)
            ->where('sa.is_correct', false)
            ->select(
                'q.question_text', 'quizzes.title as quiz_title',
                DB::raw('COUNT(*) as wrong_count'),
                DB::raw('(SELECT COUNT(*) FROM student_answers sa2
                           JOIN quiz_attempts qa2 ON qa2.id = sa2.attempt_id
                           WHERE qa2.student_id = qa.student_id
                           AND sa2.question_id = sa.question_id) as total_seen')
            )
            ->groupBy('sa.question_id', 'q.question_text', 'quizzes.title', 'qa.student_id')
            ->orderByDesc('wrong_count')
            ->limit(20)
            ->get();

        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $user->full_name);
        $filename = 'admin_student_profile_' . $safeName . '_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new StudentProfileExport($user, $stats, $attempts, $weakAreas), $filename);
    }

    // ════════════════════════════════════════════════════════════
    // QUIZZES
    // ════════════════════════════════════════════════════════════

    public function quizzes(Request $request)
    {
        try {
        $filters = $this->analyticsFilters($request);
        $filters['sort'] = $filters['sort'] ?: 'pass_rate';

        /** @var \App\Models\User $authUser */
        $authUser     = auth()->user();
        $isSuperAdmin = $authUser->role === 'superadmin';

        $allForKpi = $this->quizAnalyticsQuery($filters)->get()
            ->map(function ($quiz) {
                $quiz->pass_rate = min(100, max(0, (float) ($quiz->pass_rate ?? 0)));
                return $quiz;
            });
        $kpis = [
            'total_quizzes'  => $allForKpi->count(),
            'total_attempts' => $allForKpi->sum('total_attempts'),
            'avg_pass_rate'  => $allForKpi->avg('pass_rate') ?? 0,
            'avg_score'      => $allForKpi->avg('avg_score') ?? 0,
        ];

        $attemptedQuizzes = $allForKpi->where('total_attempts', '>', 0)->values();
        $topPassRate = $attemptedQuizzes
            ->sortByDesc('pass_rate')
            ->take(10)
            ->values();
        $topQuizIds = $topPassRate->pluck('id')->all();
        $bottomPassRate = $attemptedQuizzes
            ->reject(fn($quiz) => in_array($quiz->id, $topQuizIds, true))
            ->sortBy('pass_rate')
            ->take(10)
            ->values();

        $totalAttemptSum = $allForKpi->sum('total_attempts');
        $passCount = $allForKpi->sum(fn($q) => (int) ($q->pass_count ?? 0));
        $failCount = $allForKpi->sum(fn($q) => (int) ($q->fail_count ?? 0));

        $brackets = ['0-40%' => 0, '41-75%' => 0, '76-100%' => 0];
        foreach ($attemptedQuizzes as $q) {
            $pr = $q->pass_rate ?? 0;
            if ($pr <= 40) {
                $brackets['0-40%']++;
            } elseif ($pr <= 75) {
                $brackets['41-75%']++;
            } else {
                $brackets['76-100%']++;
            }
        }

        $scatter = $allForKpi->take(100)->map(fn($q) => [
            'title'     => Str::limit($q->title, 25),
            'attempts'  => (int) $q->total_attempts,
            'avg_score' => round($q->avg_score ?? 0, 1),
            'pass_rate' => round(min(100, max(0, $q->pass_rate ?? 0)), 1),
        ])->values()->toArray();

        $chartData = [
            'pass_count'          => $passCount,
            'fail_count'          => $failCount,
            'distribution_labels' => array_keys($brackets),
            'distribution_counts' => array_values($brackets),
            'scatter'             => $scatter,
        ];

        $teachers = User::where('role', 'teacher')->orderBy('surname')->get();

        $sortCol   = in_array($filters['sort'], ['pass_rate', 'avg_score', 'total_attempts', 'title'])
            ? $filters['sort'] : 'pass_rate';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        $allQuizzes = $this->quizAnalyticsQuery($filters)
            ->orderBy($sortCol, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('admin.analytics.quizzes.index', compact(
            'filters', 'kpis', 'topPassRate', 'bottomPassRate',
            'chartData', 'allQuizzes', 'teachers', 'isSuperAdmin'
        ));
        } catch (Throwable $exception) {
            return $this->analyticsError('Quizzes', $exception);
        }
    }

    public function exportQuizzes(Request $request)
    {
        $filters = [
            'date_from'  => $request->input('date_from'),
            'date_to'    => $request->input('date_to'),
            'teacher_id' => $request->input('teacher_id'),
            'search'     => $request->input('search'),
            'sort'       => $request->input('sort', 'pass_rate'),
            'direction'  => $request->input('direction', 'desc'),
        ];

        $quizzes = $this->quizAnalyticsQuery($filters)
            ->orderBy($filters['sort'] ?? 'pass_rate', $filters['direction'] ?? 'desc')
            ->get();

        $kpis = [
            'total_attempts' => $quizzes->sum('total_attempts'),
            'avg_pass_rate'  => $quizzes->avg('pass_rate') ?? 0,
            'avg_score'      => $quizzes->avg('avg_score') ?? 0,
        ];

        $filename = 'admin_quiz_analytics_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new QuizPerformanceExport($quizzes, $filters, $kpis), $filename);
    }

    // ════════════════════════════════════════════════════════════
    // CLASSES — Part 5
    // ════════════════════════════════════════════════════════════

    public function quizShow(Quiz $quiz)
    {
        try {
            $filters = $this->analyticsFilters(request());
            $dateFrom = $filters['date_from'] ?? null;
            $dateTo = $filters['date_to'] ?? null;
            $passingCase = $this->passingCaseSql();

            $quiz->load('teacher', 'classes');

            $attempts = $this->applyCompletedDateFilter(
                QuizAttempt::where('quiz_id', $quiz->id)->whereNotNull('completed_at'),
                $dateFrom,
                $dateTo
            );

            $totalAttempts = (clone $attempts)->count();
            $passCount = (int) ((clone $attempts)->selectRaw("SUM({$passingCase}) as passed")->value('passed') ?? 0);
            $avgScore = (clone $attempts)->avg(DB::raw('(score / NULLIF(total_points, 0)) * 100')) ?? 0;

            $kpis = [
                'total_attempts' => $totalAttempts,
                'avg_score' => $avgScore,
                'pass_rate' => $totalAttempts > 0 ? min(100, max(0, ($passCount / $totalAttempts) * 100)) : 0,
                'pass_count' => $passCount,
                'fail_count' => max(0, $totalAttempts - $passCount),
            ];

            $students = User::query()
                ->where('users.role', 'student')
                ->whereHas('quizAttempts', fn($q) => $q->where('quiz_id', $quiz->id)->whereNotNull('completed_at'))
                ->select(
                    'users.id',
                    'users.email',
                    DB::raw("TRIM(CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.middle_initial,''), ' ', COALESCE(users.surname,''))) as full_name")
                )
                ->selectSub(
                    $this->applyCompletedDateFilter(
                        QuizAttempt::selectRaw('COUNT(*)')->whereColumn('student_id', 'users.id')->where('quiz_id', $quiz->id)->whereNotNull('completed_at'),
                        $dateFrom,
                        $dateTo
                    ),
                    'attempt_count'
                )
                ->selectSub(
                    $this->applyCompletedDateFilter(
                        QuizAttempt::selectRaw('AVG((score / NULLIF(total_points,0)) * 100)')->whereColumn('student_id', 'users.id')->where('quiz_id', $quiz->id)->whereNotNull('completed_at'),
                        $dateFrom,
                        $dateTo
                    ),
                    'avg_score'
                )
                ->selectSub(
                    $this->applyCompletedDateFilter(
                        QuizAttempt::selectRaw("SUM({$passingCase}) / NULLIF(COUNT(*),0) * 100")->whereColumn('student_id', 'users.id')->where('quiz_id', $quiz->id)->whereNotNull('completed_at'),
                        $dateFrom,
                        $dateTo
                    ),
                    'pass_rate'
                )
                ->orderByDesc('avg_score')
                ->paginate(15)
                ->withQueryString();

            return view('admin.analytics.quizzes.show', compact('quiz', 'kpis', 'students', 'filters'));
        } catch (Throwable $exception) {
            return $this->analyticsError('Quiz Detail', $exception);
        }
    }

    public function classes(Request $request)
    {
        try {
        $filters = $this->analyticsFilters($request);
        $filters['sort'] = $filters['sort'] ?: 'pass_rate';

        $allClasses = $this->classAnalyticsQuery($filters)->get();
        $teachers = User::where('role', 'teacher')->where('status', 'active')->orderBy('surname')->orderBy('first_name')->get();

        $kpis = [
            'total_classes'  => $allClasses->count(),
            'total_students' => ClassRoom::withCount('students')->get()->sum('students_count'),
            'avg_pass_rate'  => $allClasses->avg('pass_rate') ?? 0,
            'avg_score'      => $allClasses->avg('avg_score') ?? 0,
        ];

        $topClasses    = $allClasses->sortByDesc('pass_rate')->values()->take(5);
        $bottomClasses = $allClasses->sortBy('pass_rate')->values()->take(5);

        $chartSet = $allClasses->sortByDesc('total_attempts')->take(10)->values();
        $chartData = [
            'labels'      => $chartSet->pluck('name')->toArray(),
            'avg_scores'  => $chartSet->map(fn($c) => round($c->avg_score ?? 0, 1))->toArray(),
            'pass_counts' => $chartSet->map(fn($c) => $c->pass_count ?? 0)->toArray(),
            'fail_counts' => $chartSet->map(fn($c) => $c->fail_count ?? 0)->toArray(),
        ];

        $classes = $this->classAnalyticsQuery($filters)
            ->paginate(15)
            ->withQueryString();

        return view('admin.analytics.classes.index', compact(
            'kpis', 'topClasses', 'bottomClasses', 'chartData', 'classes', 'filters', 'teachers'
        ));
        } catch (Throwable $exception) {
            return $this->analyticsError('Classes', $exception);
        }
    }

    public function classShow(ClassRoom $classroom)
    {
        $classroom->load('teacher');

        $classQuizIds = $classroom->quizzes()->pluck('quizzes.id');
        $classStudentIds = $classroom->students()->pluck('users.id');

        $attempts = QuizAttempt::whereIn('quiz_id', $classQuizIds)
            ->whereIn('student_id', $classStudentIds)
            ->whereNotNull('completed_at');
        $passingCase = $this->passingCaseSql();

        $totalAttempts = (clone $attempts)->count();
        $avgScore      = (clone $attempts)->avg(DB::raw('(score / NULLIF(total_points, 0)) * 100')) ?? 0;
        $passCount     = (int) ((clone $attempts)->selectRaw("SUM({$passingCase}) as passed")->value('passed') ?? 0);
        $passRate      = $totalAttempts > 0 ? ($passCount / $totalAttempts) * 100 : 0;

        $kpis = [
            'total_students' => $classStudentIds->count(),
            'total_attempts' => $totalAttempts,
            'avg_score'      => $avgScore,
            'pass_rate'      => $passRate,
        ];

        // Top / Bottom students
        $studentStats = User::whereIn('id', $classStudentIds)
            ->select('users.id', 'users.first_name', 'users.surname')
            ->selectSub(
                QuizAttempt::selectRaw('AVG((score / NULLIF(total_points,0)) * 100)')
                    ->whereColumn('student_id', 'users.id')
                    ->whereIn('quiz_id', $classQuizIds),
                'avg_score'
            )
            ->selectSub(
                QuizAttempt::selectRaw('COUNT(*)')
                    ->whereColumn('student_id', 'users.id')
                    ->whereIn('quiz_id', $classQuizIds),
                'total_attempts'
            )
            ->selectSub(
                QuizAttempt::selectRaw("LEAST(100, GREATEST(0, COALESCE(SUM({$passingCase}) / NULLIF(COUNT(*), 0) * 100, 0)))")
                    ->whereColumn('student_id', 'users.id')
                    ->whereIn('quiz_id', $classQuizIds),
                'pass_rate'
            )
            ->get();

        $topStudents    = $studentStats->sortByDesc('avg_score')->values()->take(5);
        $bottomStudents = $studentStats->filter(fn($s) => $s->total_attempts > 0)
                                       ->sortBy('avg_score')->values()->take(5);

        // Quiz performance
        $quizPerformance = Quiz::whereIn('id', $classQuizIds)
            ->select('quizzes.id', 'quizzes.title')
            ->selectSub(
                QuizAttempt::selectRaw('COUNT(*)')->whereColumn('quiz_id', 'quizzes.id'),
                'total_attempts'
            )
            ->selectSub(
                QuizAttempt::selectRaw('AVG((score / NULLIF(total_points,0)) * 100)')->whereColumn('quiz_id', 'quizzes.id'),
                'avg_score'
            )
            ->selectSub(
                QuizAttempt::selectRaw("SUM({$passingCase}) / NULLIF(COUNT(*),0) * 100")->whereColumn('quiz_id', 'quizzes.id'),
                'pass_rate'
            )
            ->orderByDesc('total_attempts')
            ->get();

        // Score trend (monthly)
        $trendRaw = QuizAttempt::selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as month, AVG((score / NULLIF(total_points,0)) * 100) as avg_score")
            ->whereIn('quiz_id', $classQuizIds)
            ->whereIn('student_id', $classStudentIds)
            ->whereNotNull('completed_at')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Score distribution
        $distBuckets = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
        foreach ((clone $attempts)->get() as $a) {
            $pct = $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0;
            if ($pct <= 20)      $distBuckets['0-20']++;
            elseif ($pct <= 40)  $distBuckets['21-40']++;
            elseif ($pct <= 60)  $distBuckets['41-60']++;
            elseif ($pct <= 80)  $distBuckets['61-80']++;
            else                 $distBuckets['81-100']++;
        }

        $chartData = [
            'trend_labels' => $trendRaw->pluck('month')->toArray(),
            'trend_scores' => $trendRaw->map(fn($r) => round($r->avg_score, 1))->toArray(),
            'dist_labels'  => array_keys($distBuckets),
            'dist_counts'  => array_values($distBuckets),
        ];

        return view('admin.analytics.classes.show', compact(
            'classroom', 'kpis', 'topStudents', 'bottomStudents',
            'quizPerformance', 'chartData'
        ));
    }

    public function exportClasses(Request $request)
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to'   => $request->input('date_to'),
            'teacher_id' => $request->input('teacher_id'),
            'search'    => $request->input('search'),
            'sort'      => $request->input('sort', 'pass_rate'),
            'direction' => $request->input('direction', 'desc'),
        ];

        $classes = $this->classAnalyticsQuery($filters)->get();

        $kpis = [
            'total_classes'  => $classes->count(),
            'total_students' => ClassRoom::withCount('students')->get()->sum('students_count'),
            'avg_pass_rate'  => $classes->avg('pass_rate') ?? 0,
            'avg_score'      => $classes->avg('avg_score') ?? 0,
        ];

        $filename = 'admin_class_analytics_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new ClassAnalyticsExport($classes, $filters, $kpis), $filename);
    }

    public function exportClassShow(ClassRoom $classroom)
    {
        $classroom->load('teacher');

        $classQuizIds    = $classroom->quizzes()->pluck('quizzes.id');
        $classStudentIds = $classroom->students()->pluck('users.id');

        $attempts = QuizAttempt::whereIn('quiz_id', $classQuizIds)
            ->whereIn('student_id', $classStudentIds)
            ->whereNotNull('completed_at');
        $passingCase = $this->passingCaseSql();

        $totalAttempts = (clone $attempts)->count();
        $avgScore      = (clone $attempts)->avg(DB::raw('(score / NULLIF(total_points, 0)) * 100')) ?? 0;
        $passCount     = (int) ((clone $attempts)->selectRaw("SUM({$passingCase}) as passed")->value('passed') ?? 0);
        $passRate      = $totalAttempts > 0 ? ($passCount / $totalAttempts) * 100 : 0;

        $kpis = [
            'total_students' => $classStudentIds->count(),
            'total_attempts' => $totalAttempts,
            'avg_score'      => $avgScore,
            'pass_rate'      => $passRate,
        ];

        $students = User::whereIn('id', $classStudentIds)
            ->select('users.id', 'users.first_name', 'users.surname')
            ->selectSub(
                QuizAttempt::selectRaw('AVG((score / NULLIF(total_points,0)) * 100)')
                    ->whereColumn('student_id', 'users.id')
                    ->whereIn('quiz_id', $classQuizIds),
                'avg_score'
            )
            ->selectSub(
                QuizAttempt::selectRaw('COUNT(*)')
                    ->whereColumn('student_id', 'users.id')
                    ->whereIn('quiz_id', $classQuizIds),
                'total_attempts'
            )
            ->selectSub(
                QuizAttempt::selectRaw("SUM({$passingCase})/NULLIF(COUNT(*),0)*100")
                    ->whereColumn('student_id', 'users.id')
                    ->whereIn('quiz_id', $classQuizIds),
                'pass_rate'
            )
            ->orderByDesc('avg_score')
            ->get();

        $quizPerformance = Quiz::whereIn('id', $classQuizIds)
            ->select('quizzes.id', 'quizzes.title')
            ->selectSub(
                QuizAttempt::selectRaw('COUNT(*)')->whereColumn('quiz_id', 'quizzes.id'),
                'total_attempts'
            )
            ->selectSub(
                QuizAttempt::selectRaw('AVG((score / NULLIF(total_points,0)) * 100)')->whereColumn('quiz_id', 'quizzes.id'),
                'avg_score'
            )
            ->selectSub(
                QuizAttempt::selectRaw("SUM({$passingCase})/NULLIF(COUNT(*),0)*100")->whereColumn('quiz_id', 'quizzes.id'),
                'pass_rate'
            )
            ->orderByDesc('total_attempts')
            ->get();

        $safeName = Str::slug($classroom->name);
        $filename = "admin_class_{$safeName}_" . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ClassShowExport($classroom, $kpis, $students, $quizPerformance),
            $filename
        );
    }

    // ════════════════════════════════════════════════════════════
    // TEACHERS
    // ════════════════════════════════════════════════════════════

    public function teachers(Request $request)
    {
        try {
            $filters = $this->analyticsFilters($request);
            $filters['sort'] = $filters['sort'] ?: 'pass_rate';
            $summaryFilters = array_merge($filters, ['search' => null]);

            $teachersForKpi = $this->teacherAnalyticsQuery($summaryFilters)->get();
            $kpis = [
                'total_teachers' => $teachersForKpi->count(),
                'total_quizzes' => $teachersForKpi->sum('quizzes_count'),
                'total_attempts' => $teachersForKpi->sum('total_attempts'),
                'avg_pass_rate' => $teachersForKpi->avg('pass_rate') ?? 0,
            ];

            $topTeachers = $teachersForKpi
                ->where('total_attempts', '>', 0)
                ->sortByDesc('pass_rate')
                ->take(5)
                ->values();

            $teachers = $this->teacherAnalyticsQuery($filters)
                ->paginate(15)
                ->withQueryString();

            return view('admin.analytics.teachers.index', compact(
                'filters', 'kpis', 'topTeachers', 'teachers'
            ));
        } catch (Throwable $exception) {
            return $this->analyticsError('Teachers', $exception);
        }
    }

    public function teacherShow(User $user)
    {
        abort_if($user->role !== 'teacher', 404);

        try {
            $filters = $this->analyticsFilters(request());
            $filters['teacher_id'] = $user->id;

            $teacher = $this->teacherAnalyticsQuery($filters)
                ->where('users.id', $user->id)
                ->firstOrFail();

            $quizzes = $this->quizAnalyticsQuery($filters)
                ->where('quizzes.teacher_id', $user->id)
                ->orderByDesc('total_attempts')
                ->paginate(15)
                ->withQueryString();

            return view('admin.analytics.teachers.show', compact('teacher', 'quizzes', 'filters'));
        } catch (Throwable $exception) {
            return $this->analyticsError('Teacher Profile', $exception);
        }
    }

    public function exportTeachers(Request $request)
    {
        return redirect()
            ->route('admin.analytics.teachers', $request->query())
            ->with('success', 'Teacher analytics export is not configured yet.');
    }

    public function exportTeacherProfile(User $user)
    {
        return redirect()
            ->route('admin.analytics.teachers.show', $user)
            ->with('success', 'Teacher profile export is not configured yet.');
    }

    // ════════════════════════════════════════════════════════════
    // PRIVATE SHARED QUERY BUILDERS
    // ════════════════════════════════════════════════════════════

    /**
     * Validate and normalize filters shared by Admin Analytics pages.
     *
     * date_mode=all leaves completed_at unfiltered.
     * date_mode=range requires both dates and validates date_to >= date_from.
     */
    private function analyticsFilters(Request $request, array $overrides = []): array
    {
        $rules = array_merge([
            'date_mode'  => ['nullable', 'in:all,range'],
            'date_from'  => ['nullable', 'date_format:Y-m-d', 'regex:/^\d{4}-\d{2}-\d{2}$/', 'after_or_equal:2010-01-01', 'before_or_equal:2026-12-31'],
            'date_to'    => ['nullable', 'date_format:Y-m-d', 'regex:/^\d{4}-\d{2}-\d{2}$/', 'after_or_equal:date_from', 'before_or_equal:2026-12-31'],
            'search'     => ['nullable', 'string', 'max:100'],
            'sort'       => ['nullable', 'string', 'max:50'],
            'direction'  => ['nullable', 'in:asc,desc'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'quiz_id'    => ['nullable', 'integer', 'exists:quizzes,id'],
            'class_id'   => ['nullable', 'integer', 'exists:classes,id'],
            'grade_level'=> ['nullable', 'string', 'max:50'],
        ], $overrides);

        $validated = $request->validate($rules);
        $hasDate = !empty($validated['date_from']) || !empty($validated['date_to']);
        $dateMode = $validated['date_mode'] ?? ($hasDate ? 'range' : 'all');

        if ($dateMode === 'range' && (empty($validated['date_from']) || empty($validated['date_to']))) {
            throw ValidationException::withMessages([
                'date_from' => 'Choose both start and end dates, or select All Time.',
            ]);
        }

        if ($dateMode === 'all') {
            $validated['date_from'] = null;
            $validated['date_to'] = null;
        }

        return array_merge([
            'date_mode' => $dateMode,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'search' => $validated['search'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'direction' => $validated['direction'] ?? 'desc',
            'teacher_id' => $validated['teacher_id'] ?? null,
            'quiz_id' => $validated['quiz_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
        ], $validated);
    }

    /**
     * Apply the analytics completed_at filter only when a date range is active.
     */
    private function applyCompletedDateFilter($query, ?string $dateFrom, ?string $dateTo)
    {
        return $query
            ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
            ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'));
    }

    private function passingCaseSql(string $alias = ''): string
    {
        $prefix = $alias ? $alias . '.' : '';

        return "CASE WHEN {$prefix}total_points > 0 AND (({$prefix}score / {$prefix}total_points) * 100) >= 60 THEN 1 ELSE 0 END";
    }

    private function failingCaseSql(string $alias = ''): string
    {
        $prefix = $alias ? $alias . '.' : '';

        return "CASE WHEN {$prefix}total_points > 0 AND (({$prefix}score / {$prefix}total_points) * 100) < 60 THEN 1 ELSE 0 END";
    }

    private function attemptIsPassing(QuizAttempt $attempt): bool
    {
        return $attempt->total_points > 0 && (($attempt->score / $attempt->total_points) * 100) >= 60;
    }

    private function applyAttemptBasisFilter($query, ?int $quizId = null, ?int $classId = null)
    {
        if ($quizId) {
            return $query->where('quiz_id', $quizId);
        }

        return $query
            ->when($classId, function ($q) use ($classId) {
                $q->whereIn('quiz_id',
                    Quiz::select('quizzes.id')
                        ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                        ->where('class_quizzes.class_id', $classId)
                );
            });
    }

    /**
     * Keep Admin Analytics failures visible without taking down the whole admin UI.
     */
    private function analyticsError(string $section, Throwable $exception)
    {
        report($exception);

        return view('admin.analytics.error', [
            'section' => $section,
            'message' => 'Analytics data could not be loaded right now. Please check the filters and try again.',
        ]);
    }

    private function studentQuery(?string $dateFrom, ?string $dateTo, ?string $gradeLevel = null, ?int $quizId = null, ?int $classId = null)
    {
        $passingCase = $this->passingCaseSql();

        return User::query()
            ->where('users.role', 'student')
            ->where('users.status', 'active')
            ->with('studentProfile')
            ->leftJoin('student_profiles as sp', 'sp.user_id', '=', 'users.id')
            ->select(
                'users.*',
                'sp.grade_level',
                'sp.section',
                'sp.gender',
                DB::raw("TRIM(CONCAT(
                    COALESCE(users.first_name,''), ' ',
                    COALESCE(users.middle_initial,''), ' ',
                    COALESCE(users.surname,'')
                )) as full_name")
            )
            ->withCount(['quizAttempts as attempt_count' => function ($q) use ($dateFrom, $dateTo, $quizId, $classId) {
                $this->applyCompletedDateFilter($q->whereNotNull('completed_at'), $dateFrom, $dateTo);
                $this->applyAttemptBasisFilter($q, $quizId, $classId);
            }])
            ->addSelect([
                'avg_score' => QuizAttempt::selectRaw(
                    'COALESCE(AVG(score / NULLIF(total_points,0) * 100), 0)'
                )
                ->whereColumn('student_id', 'users.id')
                ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->when($quizId, fn($q) => $q->where('quiz_id', $quizId))
                ->when(!$quizId && $classId, fn($q) => $q->whereIn('quiz_id',
                    Quiz::select('quizzes.id')
                        ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                        ->where('class_quizzes.class_id', $classId)
                ))
                ->whereNotNull('completed_at'),

                'pass_rate' => QuizAttempt::selectRaw(
                    "LEAST(100, GREATEST(0, COALESCE(SUM({$passingCase}) * 100.0 / NULLIF(COUNT(*), 0), 0)))"
                )
                ->whereColumn('student_id', 'users.id')
                ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->when($quizId, fn($q) => $q->where('quiz_id', $quizId))
                ->when(!$quizId && $classId, fn($q) => $q->whereIn('quiz_id',
                    Quiz::select('quizzes.id')
                        ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                        ->where('class_quizzes.class_id', $classId)
                ))
                ->whereNotNull('completed_at'),

                'latest_attempt_at' => QuizAttempt::selectRaw('MAX(completed_at)')
                ->whereColumn('student_id', 'users.id')
                ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->when($quizId, fn($q) => $q->where('quiz_id', $quizId))
                ->when(!$quizId && $classId, fn($q) => $q->whereIn('quiz_id',
                    Quiz::select('quizzes.id')
                        ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                        ->where('class_quizzes.class_id', $classId)
                ))
                ->whereNotNull('completed_at'),
            ])
            ->when($gradeLevel, fn($q) => $q->where('sp.grade_level', $gradeLevel));
    }

    private function quizQuery(?string $dateFrom, ?string $dateTo, ?int $teacherId = null, ?int $classId = null)
    {
        $passingCase = $this->passingCaseSql();

        return Quiz::query()
            ->where('is_published', true)
            ->with(['teacher', 'classes'])
            ->withCount(['attempts as attempt_count' => function ($q) use ($dateFrom, $dateTo) {
                $this->applyCompletedDateFilter($q->whereNotNull('completed_at'), $dateFrom, $dateTo);
            }])
            ->addSelect([
                'avg_score' => QuizAttempt::selectRaw(
                    'COALESCE(AVG(score / NULLIF(total_points,0) * 100), 0)'
                )
                ->whereColumn('quiz_id', 'quizzes.id')
                ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->whereNotNull('completed_at'),

                'pass_rate' => QuizAttempt::selectRaw(
                    "LEAST(100, GREATEST(0, COALESCE(SUM({$passingCase}) * 100.0 / NULLIF(COUNT(*), 0), 0)))"
                )
                ->whereColumn('quiz_id', 'quizzes.id')
                ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->whereNotNull('completed_at'),
            ])
            ->when($teacherId, fn($q) => $q->where('teacher_id', $teacherId))
            ->when($classId,   fn($q) => $q->whereHas('classes', fn($c) => $c->where('classes.id', $classId)));
    }

    private function quizAnalyticsQuery(array $filters)
    {
        $dateFrom  = $filters['date_from'] ?? null;
        $dateTo    = $filters['date_to']   ?? null;
        $teacherId = $filters['teacher_id'] ?? null;
        $search    = $filters['search'] ?? null;
        $passingCase = $this->passingCaseSql('qa');
        $failingCase = $this->failingCaseSql('qa');

        return Quiz::query()
            ->select([
                'quizzes.id',
                'quizzes.title',
                'quizzes.created_at',
                'quizzes.is_published',
                'quizzes.teacher_id',
                DB::raw('CONCAT(u.first_name, " ", u.surname) as teacher_name'),
                DB::raw('COUNT(DISTINCT qa.id) as total_attempts'),
                DB::raw('COALESCE(AVG(CASE WHEN qa.total_points > 0 THEN (qa.score / qa.total_points) * 100 ELSE NULL END), 0) as avg_score'),
                DB::raw("LEAST(100, GREATEST(0, COALESCE(SUM({$passingCase}) / NULLIF(COUNT(DISTINCT qa.id), 0) * 100, 0))) as pass_rate"),
                DB::raw("COALESCE(SUM({$passingCase}), 0) as pass_count"),
                DB::raw("COALESCE(SUM({$failingCase}), 0) as fail_count"),
                DB::raw('COALESCE(MAX(CASE WHEN qa.total_points > 0 THEN (qa.score / qa.total_points) * 100 ELSE NULL END), 0) as highest_score'),
                DB::raw('COALESCE(MIN(CASE WHEN qa.total_points > 0 THEN (qa.score / qa.total_points) * 100 ELSE NULL END), 0) as lowest_score'),
                DB::raw('(SELECT COUNT(*) FROM questions WHERE questions.quiz_id = quizzes.id) as questions_count'),
                DB::raw('MAX(qa.completed_at) as latest_attempt_at'),
            ])
            ->leftJoin('users as u', 'u.id', '=', 'quizzes.teacher_id')
            ->leftJoin('quiz_attempts as qa', function ($join) use ($dateFrom, $dateTo) {
                $join->on('qa.quiz_id', '=', 'quizzes.id')
                     ->whereNotNull('qa.completed_at');
                if ($dateFrom) $join->where('qa.completed_at', '>=', $dateFrom . ' 00:00:00');
                if ($dateTo)   $join->where('qa.completed_at', '<=', $dateTo . ' 23:59:59');
            })
            ->groupBy('quizzes.id', 'quizzes.title', 'quizzes.created_at', 'quizzes.is_published', 'quizzes.teacher_id', 'teacher_name')
            ->when($teacherId, fn($q) => $q->where('quizzes.teacher_id', $teacherId))
            ->when($search,    fn($q) => $q->where('quizzes.title', 'like', '%' . $search . '%'));
    }

    private function classAnalyticsQuery(array $filters)
    {
        $dateFrom  = $filters['date_from'] ?? null;
        $dateTo    = $filters['date_to']   ?? null;
        $search    = $filters['search']    ?? null;
        $teacherId = $filters['teacher_id'] ?? null;
        $sortable  = ['pass_rate', 'avg_score', 'total_attempts', 'students_count', 'name'];
        $sortCol   = in_array($filters['sort'] ?? '', $sortable) ? $filters['sort'] : 'pass_rate';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $passingCase = $this->passingCaseSql();
        $failingCase = $this->failingCaseSql();

        return ClassRoom::query()
            ->select(
                'classes.id',
                'classes.name',
                'classes.created_at',
                'classes.class_code',
                'classes.teacher_id',
                DB::raw('CONCAT(u.first_name, " ", u.surname) as teacher_name')
            )
            ->leftJoin('users as u', 'u.id', '=', 'classes.teacher_id')
            ->selectSub(
                User::selectRaw('COUNT(*)')
                    ->join('class_students', 'class_students.student_id', '=', 'users.id')
                    ->whereColumn('class_students.class_id', 'classes.id'),
                'students_count'
            )
            ->selectSub(
                Quiz::selectRaw('COUNT(*)')
                    ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                    ->whereColumn('class_quizzes.class_id', 'classes.id'),
                'quizzes_count'
            )
            ->selectSub(
                QuizAttempt::selectRaw('COUNT(*)')
                    ->whereIn('quiz_id',
                        Quiz::select('quizzes.id')
                            ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                            ->whereColumn('class_quizzes.class_id', 'classes.id')
                    )
                    ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo,   fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59')),
                'total_attempts'
            )
            ->selectSub(
                QuizAttempt::selectRaw('AVG((score / NULLIF(total_points, 0)) * 100)')
                    ->whereIn('quiz_id',
                        Quiz::select('quizzes.id')
                            ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                            ->whereColumn('class_quizzes.class_id', 'classes.id')
                    )
                    ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo,   fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59')),
                'avg_score'
            )
            ->selectSub(
                QuizAttempt::selectRaw("SUM({$passingCase}) / NULLIF(COUNT(*), 0) * 100")
                    ->whereIn('quiz_id',
                        Quiz::select('quizzes.id')
                            ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                            ->whereColumn('class_quizzes.class_id', 'classes.id')
                    )
                    ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo,   fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59')),
                'pass_rate'
            )
            ->selectSub(
                QuizAttempt::selectRaw("SUM({$passingCase})")
                    ->whereIn('quiz_id',
                        Quiz::select('quizzes.id')
                            ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                            ->whereColumn('class_quizzes.class_id', 'classes.id')
                    )
                    ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo,   fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59')),
                'pass_count'
            )
            ->selectSub(
                QuizAttempt::selectRaw("SUM({$failingCase})")
                    ->whereIn('quiz_id',
                        Quiz::select('quizzes.id')
                            ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                            ->whereColumn('class_quizzes.class_id', 'classes.id')
                    )
                    ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo,   fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59')),
                'fail_count'
            )
            ->selectSub(
                QuizAttempt::selectRaw('MAX(completed_at)')
                    ->whereIn('quiz_id',
                        Quiz::select('quizzes.id')
                            ->join('class_quizzes', 'class_quizzes.quiz_id', '=', 'quizzes.id')
                            ->whereColumn('class_quizzes.class_id', 'classes.id')
                    )
                    ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo,   fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59')),
                'latest_attempt_at'
            )
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('classes.name', 'like', "%{$search}%")
                          ->orWhere('u.first_name', 'like', "%{$search}%")
                          ->orWhere('u.surname', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortCol, $direction);
    }

    // ════════════════════════════════════════════════════════════
    // INSIGHT CARD GENERATOR
    // ════════════════════════════════════════════════════════════

    /**
     * Shared teacher analytics query for the Teachers tab and teacher detail page.
     * Each metric is a subquery so pagination does not distort aggregate values.
     */
    private function teacherAnalyticsQuery(array $filters)
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $search = $filters['search'] ?? null;
        $sortable = ['pass_rate', 'avg_score', 'total_attempts', 'quizzes_count', 'name'];
        $sortCol = in_array($filters['sort'] ?? '', $sortable) ? $filters['sort'] : 'pass_rate';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $passingCase = $this->passingCaseSql('quiz_attempts');

        return User::query()
            ->where('users.role', 'teacher')
            ->where('users.status', 'active')
            ->select(
                'users.id',
                'users.email',
                'users.first_name',
                'users.middle_initial',
                'users.surname',
                DB::raw("TRIM(CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.middle_initial,''), ' ', COALESCE(users.surname,''))) as name")
            )
            ->selectSub(Quiz::selectRaw('COUNT(*)')->whereColumn('teacher_id', 'users.id'), 'quizzes_count')
            ->selectSub(ClassRoom::selectRaw('COUNT(*)')->whereColumn('teacher_id', 'users.id'), 'classes_count')
            ->selectSub(
                QuizAttempt::selectRaw('COUNT(*)')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                    ->whereColumn('quizzes.teacher_id', 'users.id')
                    ->whereNotNull('quiz_attempts.completed_at')
                    ->when($dateFrom, fn($q) => $q->where('quiz_attempts.completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo, fn($q) => $q->where('quiz_attempts.completed_at', '<=', $dateTo . ' 23:59:59')),
                'total_attempts'
            )
            ->selectSub(
                QuizAttempt::selectRaw('COALESCE(AVG((score / NULLIF(total_points,0)) * 100), 0)')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                    ->whereColumn('quizzes.teacher_id', 'users.id')
                    ->whereNotNull('quiz_attempts.completed_at')
                    ->when($dateFrom, fn($q) => $q->where('quiz_attempts.completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo, fn($q) => $q->where('quiz_attempts.completed_at', '<=', $dateTo . ' 23:59:59')),
                'avg_score'
            )
            ->selectSub(
                QuizAttempt::selectRaw("LEAST(100, GREATEST(0, COALESCE(SUM({$passingCase}) / NULLIF(COUNT(*), 0) * 100, 0)))")
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                    ->whereColumn('quizzes.teacher_id', 'users.id')
                    ->whereNotNull('quiz_attempts.completed_at')
                    ->when($dateFrom, fn($q) => $q->where('quiz_attempts.completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo, fn($q) => $q->where('quiz_attempts.completed_at', '<=', $dateTo . ' 23:59:59')),
                'pass_rate'
            )
            ->selectSub(
                QuizAttempt::selectRaw('MAX(quiz_attempts.completed_at)')
                    ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                    ->whereColumn('quizzes.teacher_id', 'users.id')
                    ->whereNotNull('quiz_attempts.completed_at')
                    ->when($dateFrom, fn($q) => $q->where('quiz_attempts.completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo, fn($q) => $q->where('quiz_attempts.completed_at', '<=', $dateTo . ' 23:59:59')),
                'latest_attempt_at'
            )
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('users.first_name', 'like', "%{$search}%")
                        ->orWhere('users.surname', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortCol, $direction);
    }

    private function generateInsights(?string $dateFrom, ?string $dateTo): array
    {
        $insights = [];
        $passingCase = $this->passingCaseSql();

        $lowPassQuizzes = Quiz::where('is_published', true)
            ->withCount(['attempts as attempt_count' => fn($q) =>
                $this->applyCompletedDateFilter($q->whereNotNull('completed_at'), $dateFrom, $dateTo)
            ])
            ->addSelect([
                'pass_rate' => QuizAttempt::selectRaw(
                    "LEAST(100, GREATEST(0, COALESCE(SUM({$passingCase}) * 100.0 / NULLIF(COUNT(*), 0), 0)))"
                )
                ->whereColumn('quiz_id', 'quizzes.id')
                ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                ->whereNotNull('completed_at'),
            ])
            ->having('pass_rate', '<', 30)
            ->having('attempt_count', '>', 0)
            ->count();

        if ($lowPassQuizzes > 0) {
            $insights[] = [
                'type'    => 'warning',
                'icon'    => '⚠️',
                'message' => "{$lowPassQuizzes} " . str('quiz')->plural($lowPassQuizzes) . " ha" . ($lowPassQuizzes === 1 ? 's' : 've') . " a pass rate below 30% — review recommended.",
                'link'    => route('admin.analytics.quizzes'),
                'label'   => 'View Quizzes',
            ];
        }

        $inactiveCount = User::where('role', 'student')->where('status', 'active')
            ->whereDoesntHave('quizAttempts', fn($q) =>
                $this->applyCompletedDateFilter($q->whereNotNull('completed_at'), $dateFrom, $dateTo)
            )->count();

        if ($inactiveCount > 0) {
            $insights[] = [
                'type'    => 'info',
                'icon'    => '📉',
                'message' => "{$inactiveCount} " . str('student')->plural($inactiveCount) . " ha" . ($inactiveCount === 1 ? 's' : 've') . " not attempted any quiz in the selected period.",
                'link'    => route('admin.analytics.students'),
                'label'   => 'View Students',
            ];
        }

        $topClass = ClassRoom::with('quizzes')
            ->get()
            ->map(function ($class) use ($dateFrom, $dateTo) {
                $quizIds = $class->quizzes->pluck('id');
                $avg = QuizAttempt::whereIn('quiz_id', $quizIds)
                    ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom . ' 00:00:00'))
                    ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
                    ->whereNotNull('completed_at')
                    ->avg(DB::raw('score / NULLIF(total_points,0) * 100'));
                return ['name' => $class->name, 'avg' => round($avg ?? 0, 1)];
            })
            ->filter(fn($c) => $c['avg'] > 0)
            ->sortByDesc('avg')
            ->first();

        if ($topClass) {
            $insights[] = [
                'type'    => 'success',
                'icon'    => '🏆',
                'message' => "Top performing class this period: {$topClass['name']} with an average of {$topClass['avg']}%.",
                'link'    => route('admin.analytics.classes'),
                'label'   => 'View Classes',
            ];
        }

        return $insights;
    }
}
