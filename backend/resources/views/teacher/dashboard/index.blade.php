@extends('teacher.layouts.app')

@section('content')
    @php
        $teacher = auth()->user();
        $teacherName = trim((string) ($teacher?->first_name ?: $teacher?->name ?: 'Teacher'));

        $teacherQuizzes = \App\Models\Quiz::query()
            ->where('teacher_id', $teacher->id)
            ->withCount(['questions', 'classes'])
            ->with([
                'attempts' => function ($query) {
                    $query->where('status', 'completed')
                        ->with('student:id,first_name,surname,name')
                        ->latest('completed_at');
                },
            ])
            ->latest()
            ->get();

        $classes = \App\Models\ClassRoom::query()
            ->where('teacher_id', $teacher->id)
            ->withCount(['students', 'quizzes'])
            ->with([
                'students:id',
                'quizzes' => function ($query) {
                    $query->with([
                        'attempts' => function ($attemptsQuery) {
                            $attemptsQuery->where('status', 'completed');
                        },
                    ]);
                },
            ])
            ->latest()
            ->get();

        $allAttempts = $teacherQuizzes->flatMap(fn($q) => $q->attempts);

        $totalClasses       = $classes->count();
        $totalStudents      = $classes->flatMap(fn($c) => $c->students->pluck('id'))->unique()->count();
        $totalQuizzes       = $teacherQuizzes->count();
        $publishedQuizzes   = $teacherQuizzes->where('is_published', true)->count();
        $completedAttempts  = $allAttempts->count();

        $scorePct = fn($a) => (float) $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0;

        $overallAverageScore = $completedAttempts > 0
            ? round($allAttempts->avg($scorePct), 1)
            : null;

        if ($totalQuizzes === 0) {
            $heroInsight = 'Create quizzes and assign them to classes to start tracking progress.';
        } elseif ($completedAttempts === 0) {
            $heroInsight = 'Content is ready — waiting on the first completed student submissions.';
        } else {
            $heroInsight = number_format($completedAttempts) . ' completed attempts across all quizzes.';
        }

        $recentQuizzes = $teacherQuizzes->take(5)->map(function ($quiz) use ($scorePct) {
            $cnt = $quiz->attempts->count();
            return (object) [
                'id'                       => $quiz->id,
                'title'                    => $quiz->title,
                'is_published'             => $quiz->is_published,
                'classes_count'            => $quiz->classes_count,
                'questions_count'          => $quiz->questions_count,
                'attempts_count'           => $cnt,
                'students_attempted_count' => $quiz->attempts->pluck('student_id')->unique()->count(),
                'average_score'            => $cnt > 0 ? round($quiz->attempts->avg($scorePct), 1) : null,
                'created_at'               => $quiz->created_at,
            ];
        });

        $classSnapshots = $classes->map(function ($class) use ($scorePct) {
            $attempts       = $class->quizzes->flatMap(fn($q) => $q->attempts);
            $completedPairs = $class->quizzes->sum(fn($q) => $q->attempts->pluck('student_id')->unique()->count());
            $possible       = $class->students_count * $class->quizzes_count;

            return (object) [
                'id'                 => $class->id,
                'name'               => $class->name,
                'class_code'         => $class->class_code,
                'students_count'     => $class->students_count,
                'quizzes_count'      => $class->quizzes_count,
                'attempts_count'     => $attempts->count(),
                'participation_rate' => $possible > 0 ? round(($completedPairs / $possible) * 100, 1) : null,
                'average_score'      => $attempts->count() > 0 ? round($attempts->avg($scorePct), 1) : null,
            ];
        });

        $spotlightClasses = $classSnapshots
            ->sortByDesc(fn($c) => (($c->participation_rate ?? 0) * 1000) + ($c->average_score ?? 0))
            ->take(4)->values();

        $quizzesWithoutAttempts  = $teacherQuizzes->filter(fn($q) => $q->attempts->isEmpty())->count();
        $lowParticipationClasses = $classSnapshots->filter(fn($c) => !is_null($c->participation_rate) && $c->participation_rate < 50)->count();
        $classesNeedingSetup     = $classSnapshots->filter(fn($c) => $c->quizzes_count === 0 || $c->students_count === 0)->count();

        $needsAttention = [
            ['label' => 'Unpublished quizzes',          'count' => $totalQuizzes - $publishedQuizzes, 'description' => 'Students can\'t access these yet.',     'route' => route('teacher.reports.quizzes'), 'color' => 'amber'],
            ['label' => 'Quizzes with no attempts',     'count' => $quizzesWithoutAttempts,           'description' => 'May need a reminder or class check.',   'route' => route('teacher.reports.quizzes'), 'color' => 'rose'],
            ['label' => 'Classes below 50% completion', 'count' => $lowParticipationClasses,          'description' => 'Follow-up could improve rates.',         'route' => route('teacher.reports.classes'), 'color' => 'sky'],
            ['label' => 'Classes needing setup',        'count' => $classesNeedingSetup,              'description' => 'Still need students, quizzes, or both.', 'route' => route('teacher.reports.classes'), 'color' => 'slate'],
        ];

        $recentActivity = $teacherQuizzes->flatMap(function ($quiz) use ($scorePct) {
            return $quiz->attempts->map(fn($a) => (object) [
                'quiz_title'   => $quiz->title,
                'student_name' => $a->student?->name ?? 'Student',
                'completed_at' => $a->completed_at,
                'percentage'   => (float) $a->total_points > 0 ? round(($a->score / $a->total_points) * 100, 1) : null,
            ]);
        })->sortByDesc('completed_at')->take(6)->values();
    @endphp

    {{-- ────────────────────────────────────────────────────────
         Page header
    ──────────────────────────────────────────────────────────── --}}
    <div style="margin-bottom:28px;">
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Overview</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">
            Good to see you, {{ $teacherName }}
        </h1>
        <p style="font-size:13px; color:var(--text-2);">
            {{ number_format($totalClasses) }} {{ Str::plural('class', $totalClasses) }} ·
            {{ number_format($totalStudents) }} {{ Str::plural('student', $totalStudents) }} ·
            {{ $heroInsight }}
        </p>
    </div>

    {{-- ────────────────────────────────────────────────────────
         Stat cards row
    ──────────────────────────────────────────────────────────── --}}
    @php
        $stats = [
            ['label' => 'Classes',    'value' => number_format($totalClasses),     'sub' => 'Under your account',      'path' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5'],
            ['label' => 'Students',   'value' => number_format($totalStudents),    'sub' => 'Unique learners enrolled', 'path' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Total Quizzes','value'=> number_format($totalQuizzes),    'sub' => 'Created so far',           'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Published',  'value' => number_format($publishedQuizzes), 'sub' => 'Available to students',    'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Attempts',   'value' => number_format($completedAttempts),'sub' => 'Completed submissions',    'path' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10'],
            ['label' => 'Avg Score',  'value' => !is_null($overallAverageScore) ? number_format($overallAverageScore,1).'%' : '—', 'sub' => 'Across all attempts', 'path' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
        ];
    @endphp

    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:12px; margin-bottom:24px;">
        @foreach ($stats as $stat)
        <div class="card" style="padding:18px 20px; display:flex; flex-direction:column; gap:12px; transition:border-color 0.15s;"
             onmouseenter="this.style.borderColor='rgba(255,255,255,0.13)'"
             onmouseleave="this.style.borderColor='rgba(255,255,255,0.07)'">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                <p style="font-size:10.5px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">{{ $stat['label'] }}</p>
                <div style="height:26px; width:26px; border-radius:7px; background:var(--surface-3); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg style="width:13px;height:13px;color:var(--accent);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['path'] }}"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="num" style="font-size:26px; font-weight:700; color:var(--text); line-height:1; letter-spacing:-0.04em;">{{ $stat['value'] }}</p>
                <p style="font-size:11px; color:var(--text-3); margin-top:4px;">{{ $stat['sub'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ────────────────────────────────────────────────────────
         Main two-column layout
    ──────────────────────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start;">

        {{-- ── LEFT COLUMN ── --}}
        <div style="display:flex; flex-direction:column; gap:20px; min-width:0;">

            {{-- Recent Quizzes card --}}
            <div class="card" style="overflow:hidden;">
                {{-- Header --}}
                <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
                    <div>
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Latest</p>
                        <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Recent Quizzes</h2>
                        <p style="font-size:12px; color:var(--text-2);">Publishing status, reach, and performance at a glance.</p>
                    </div>
                    <a href="{{ route('teacher.reports.quizzes') }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                        Quiz Report
                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                @if ($recentQuizzes->isEmpty())
                    <div style="padding:52px 24px; text-align:center;">
                        <div style="height:40px;width:40px;border-radius:10px;background:var(--surface-3);margin:0 auto 12px;display:flex;align-items:center;justify-content:center;">
                            <svg style="width:18px;height:18px;color:var(--text-3);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <p style="font-size:13px;font-weight:600;color:var(--text-2);">No quizzes yet</p>
                        <p style="font-size:11px;color:var(--text-3);margin-top:4px;">Create quizzes to see them here.</p>
                    </div>
                @else
                    @foreach ($recentQuizzes as $quiz)
                        <div class="divider" style="padding:18px 22px; transition:background 0.12s;"
                             onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                             onmouseleave="this.style.background='transparent'">
                            <div style="display:flex; align-items:flex-start; gap:16px; justify-content:space-between;">

                                {{-- Left: title, badges, chips, actions --}}
                                <div style="min-width:0; flex:1;">
                                    {{-- Title + status badge --}}
                                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:10px;">
                                        <h3 style="font-size:13.5px; font-weight:600; color:var(--text); letter-spacing:-0.01em; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:340px;">{{ $quiz->title }}</h3>
                                        @if ($quiz->is_published)
                                            <span class="badge badge-green">
                                                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                                                Published
                                            </span>
                                        @else
                                            <span class="badge badge-amber">
                                                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                                                Draft
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Metadata chips --}}
                                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;">
                                        <span class="chip">{{ $quiz->questions_count }} questions</span>
                                        <span class="chip">{{ $quiz->classes_count }} {{ Str::plural('class', $quiz->classes_count) }}</span>
                                        <span class="chip">{{ $quiz->students_attempted_count }} attempted</span>
                                        <span class="chip">{{ $quiz->created_at?->format('M d, Y') ?? 'Recently' }}</span>
                                    </div>

                                    {{-- Action buttons --}}
                                    <div style="display:flex; gap:8px;">
                                        <a href="{{ route('teacher.reports.quiz.questions', $quiz->id) }}" class="btn btn-ghost btn-sm">Questions</a>
                                        <a href="{{ route('teacher.reports.quiz.answers', $quiz->id) }}" class="btn btn-ghost btn-sm"
                                           style="color:var(--accent); border-color:rgba(74,222,128,0.22);">Answers</a>
                                    </div>
                                </div>

                                {{-- Right: attempts + avg score boxes --}}
                                <div style="display:flex; gap:8px; flex-shrink:0; padding-left:16px; border-left:1px solid var(--border);">
                                    <div style="text-align:center; padding:12px 16px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border); min-width:72px;">
                                        <p style="font-size:9.5px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); margin-bottom:6px;">Attempts</p>
                                        <p class="num" style="font-size:20px; font-weight:700; color:var(--text); line-height:1;">{{ number_format($quiz->attempts_count) }}</p>
                                    </div>
                                    <div style="text-align:center; padding:12px 16px; border-radius:10px; background:var(--surface-2); border:1px solid var(--border); min-width:72px;">
                                        <p style="font-size:9.5px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); margin-bottom:6px;">Avg Score</p>
                                        <p class="num" style="font-size:20px; font-weight:700; color:var(--accent); line-height:1;">
                                            {{ !is_null($quiz->average_score) ? number_format($quiz->average_score, 1).'%' : '—' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Class Performance --}}
            <div class="card" style="overflow:hidden;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
                    <div>
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Snapshot</p>
                        <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Class Performance</h2>
                        <p style="font-size:12px; color:var(--text-2);">Top classes ranked by participation and average score.</p>
                    </div>
                    <a href="{{ route('teacher.reports.classes') }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                        Class Report
                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                @if ($spotlightClasses->isEmpty())
                    <div style="padding:52px 24px; text-align:center;">
                        <p style="font-size:13px; font-weight:600; color:var(--text-2);">No classes yet</p>
                        <p style="font-size:11px; color:var(--text-3); margin-top:4px;">Set up classes and assign quizzes to see data here.</p>
                    </div>
                @else
                    {{-- 2-column grid; inner items separated by a 1px border gap --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1px; background:var(--border);">
                        @foreach ($spotlightClasses as $class)
                            @php
                                $pr = $class->participation_rate;
                                $prCls = is_null($pr) ? 'score-none' : ($pr >= 75 ? 'score-high' : ($pr >= 50 ? 'score-mid' : 'score-low'));
                            @endphp
                            <div style="background:var(--surface); padding:20px;">

                                {{-- Class name + participation badge --}}
                                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:14px;">
                                    <div style="min-width:0;">
                                        <h3 style="font-size:13px; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $class->name }}</h3>
                                        <p class="num" style="font-size:10px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-top:2px;">{{ $class->class_code }}</p>
                                    </div>
                                    <span class="badge {{ $prCls }}" style="flex-shrink:0;">
                                        {{ !is_null($pr) ? number_format($pr, 1).'%' : '—' }}
                                    </span>
                                </div>

                                {{-- 2×2 mini stat grid --}}
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:14px;">
                                    @php
                                        $cs = [
                                            ['l' => 'Students', 'v' => number_format($class->students_count)],
                                            ['l' => 'Quizzes',  'v' => number_format($class->quizzes_count)],
                                            ['l' => 'Attempts', 'v' => number_format($class->attempts_count)],
                                            ['l' => 'Avg Score','v' => !is_null($class->average_score) ? number_format($class->average_score, 1).'%' : '—'],
                                        ];
                                    @endphp
                                    @foreach ($cs as $s)
                                        <div style="padding:10px 12px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border);">
                                            <p style="font-size:9.5px; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:var(--text-3); margin-bottom:4px;">{{ $s['l'] }}</p>
                                            <p class="num" style="font-size:16px; font-weight:700; color:var(--text); line-height:1;">{{ $s['v'] }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Buttons --}}
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                                    <a href="{{ route('teacher.reports.class.detail', $class->id) }}" class="btn btn-primary btn-sm">View Report</a>
                                    <a href="{{ route('teacher.reports.class.quizzes', $class->id) }}" class="btn btn-secondary btn-sm">Quizzes</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ── RIGHT COLUMN ── --}}
        <div style="display:flex; flex-direction:column; gap:16px; min-width:0;">

            {{-- Quick Actions --}}
            <div class="card" style="padding:16px;">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--text-3); margin-bottom:10px;">Quick Actions</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                    @php
                        $actions = [
                            ['label' => 'Classes',  'route' => route('teacher.reports.classes'),         'path' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5'],
                            ['label' => 'Quizzes',  'route' => route('teacher.reports.quizzes'),         'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                            ['label' => 'Students', 'route' => route('teacher.reports.students'),        'path' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                            ['label' => 'Export',   'route' => route('teacher.reports.students.export'), 'path' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'],
                        ];
                    @endphp
                    @foreach ($actions as $action)
                        <a href="{{ $action['route'] }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start; gap:7px;">
                            <svg style="width:13px;height:13px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['path'] }}"/>
                            </svg>
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Needs Attention --}}
            <div class="card" style="overflow:hidden;">
                <div style="padding:16px 18px 14px; border-bottom:1px solid var(--border);">
                    <h2 style="font-size:13.5px; font-weight:700; color:var(--text);">Needs Attention</h2>
                    <p style="font-size:11.5px; color:var(--text-2); margin-top:2px;">Areas that may need your next action.</p>
                </div>
                <div style="padding:12px; display:flex; flex-direction:column; gap:6px;">
                    @foreach ($needsAttention as $item)
                        @php
                            $cm = [
                                'amber' => ['panel' => 'attention-amber', 'dot' => '#fbbf24', 'badge' => 'badge-amber'],
                                'rose'  => ['panel' => 'attention-rose',  'dot' => '#f87171', 'badge' => 'badge-rose'],
                                'sky'   => ['panel' => 'attention-sky',   'dot' => '#60a5fa', 'badge' => 'badge-sky'],
                                'slate' => ['panel' => 'attention-slate', 'dot' => '#94a3b8', 'badge' => 'badge-slate'],
                            ][$item['color']];
                        @endphp
                        <a href="{{ $item['route'] }}"
                           class="{{ $cm['panel'] }}"
                           style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:11px 13px; border-radius:10px; border:1px solid transparent; text-decoration:none; transition:transform 0.12s, box-shadow 0.12s;"
                           onmouseenter="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.3)'"
                           onmouseleave="this.style.transform='none'; this.style.boxShadow='none'">
                            <div style="display:flex; align-items:flex-start; gap:9px; min-width:0;">
                                <span style="width:6px; height:6px; border-radius:50%; background:{{ $cm['dot'] }}; flex-shrink:0; margin-top:5px;"></span>
                                <div style="min-width:0;">
                                    <p style="font-size:12px; font-weight:600; color:var(--text);">{{ $item['label'] }}</p>
                                    <p style="font-size:11px; color:var(--text-2); margin-top:1px; line-height:1.4;">{{ $item['description'] }}</p>
                                </div>
                            </div>
                            <span class="badge {{ $cm['badge'] }} num" style="flex-shrink:0;">{{ number_format($item['count']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="card" style="overflow:hidden;">
                <div style="padding:16px 18px 14px; border-bottom:1px solid var(--border);">
                    <h2 style="font-size:13.5px; font-weight:700; color:var(--text);">Recent Activity</h2>
                    <p style="font-size:11.5px; color:var(--text-2); margin-top:2px;">Latest completed submissions.</p>
                </div>

                @if ($recentActivity->isEmpty())
                    <div style="padding:36px 18px; text-align:center;">
                        <p style="font-size:12px; color:var(--text-3); line-height:1.6;">No completed attempts yet.<br>Activity appears once submissions arrive.</p>
                    </div>
                @else
                    @foreach ($recentActivity as $activity)
                        @php
                            $pct = $activity->percentage;
                            $sCls = is_null($pct) ? 'score-none' : ($pct >= 75 ? 'score-high' : ($pct >= 50 ? 'score-mid' : 'score-low'));
                        @endphp
                        <div class="divider"
                             style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:11px 18px; transition:background 0.12s;"
                             onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                             onmouseleave="this.style.background='transparent'">
                            <div style="min-width:0;">
                                <p style="font-size:12.5px; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $activity->student_name }}</p>
                                <p style="font-size:11px; color:var(--text-2); margin-top:1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $activity->quiz_title }}</p>
                                <p style="font-size:10px; color:var(--text-3); margin-top:2px;">{{ $activity->completed_at?->diffForHumans() ?? 'Recently' }}</p>
                            </div>
                            <span class="badge {{ $sCls }} num" style="flex-shrink:0;">
                                {{ !is_null($pct) ? number_format($pct, 1).'%' : '—' }}
                            </span>
                        </div>
                    @endforeach
                @endif
            </div>

        </div>{{-- /.right column --}}
    </div>{{-- /.main grid --}}

    {{-- ── Responsive: collapse to single column on narrow screens ── --}}
    <style>
        @media (max-width: 900px) {
            .dash-grid { grid-template-columns: 1fr !important; }
            .stat-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 540px) {
            .stat-grid { grid-template-columns: 1fr 1fr !important; }
        }
    </style>

@endsection
