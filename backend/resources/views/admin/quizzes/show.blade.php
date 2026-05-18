@extends('admin.layouts.app')

@section('title', 'Quiz Details')

@section('content')

@php
    $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin';

    $buckets = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
    foreach ($resultsRows as $row) {
        $pct = $row['percentage'];
        if ($pct <= 20)      $buckets['0-20']++;
        elseif ($pct <= 40)  $buckets['21-40']++;
        elseif ($pct <= 60)  $buckets['41-60']++;
        elseif ($pct <= 80)  $buckets['61-80']++;
        else                 $buckets['81-100']++;
    }

    $easyCount      = $questionAnalytics->where('difficulty', 'Easy')->count();
    $moderateCount  = $questionAnalytics->where('difficulty', 'Moderate')->count();
    $difficultCount = $questionAnalytics->where('difficulty', 'Difficult')->count();
@endphp

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="space-y-6">

    {{-- ── BACK ── --}}
    <div>
        <a href="{{ route('admin.classes.details', $class->id) }}"
           class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            ← Back to Class
        </a>
    </div>

    {{-- ── QUIZ HEADER ── --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 p-6 text-white shadow-xl sm:p-8">
        <p class="text-sm font-medium uppercase tracking-[0.2em] text-blue-200">Quiz Overview</p>
        <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $quiz->title }}</h1>
        <p class="mt-2 text-sm text-slate-200 sm:text-base">{{ $quiz->description ?? 'No description' }}</p>
        <div class="mt-6 flex flex-wrap gap-3">
            @foreach([
                ['Questions',  $totalQuestions],
                ['Attempts',   $totalAttempts],
                ['Avg Score',  $averageScore],
                ['Avg %',      $averagePercentage.'%'],
                ['Pass Rate',  $passRate.'%'],
                ['Highest',    $highestScore],
                ['Lowest',     $lowestScore],
            ] as [$lbl, $val])
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="text-xs uppercase tracking-wide text-slate-200">{{ $lbl }}</p>
                <p class="mt-1 text-xl font-bold text-white">{{ $val }}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── TAB BUTTONS ── --}}
    <div class="flex gap-2">
        @foreach(['results' => 'Results', 'analytics' => 'Analytics'] as $tabKey => $tabLabel)
        <a href="?tab={{ $tabKey }}"
           class="rounded-xl px-5 py-2.5 text-sm font-semibold transition
                  {{ $tab === $tabKey ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            {{ $tabLabel }}
        </a>
        @endforeach
    </div>


    {{-- ════════════════════════════════════════════════════════════ --}}
    {{--  RESULTS TAB                                                 --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    @if($tab === 'results')

        @if($totalAttempts === 0)
            <div class="rounded-3xl bg-white px-6 py-12 text-center shadow ring-1 ring-slate-200">
                <p class="text-lg font-semibold text-slate-700">No attempts yet</p>
                <p class="mt-1 text-sm text-slate-400">Charts and results will appear once students complete this quiz.</p>
            </div>
        @else

        {{-- Charts row --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

            {{-- ① Pass / Fail Donut --}}
            <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Pass / Fail Ratio</p>
                <p class="mt-0.5 mb-5 text-sm text-slate-500">Overall outcome breakdown across all attempts</p>
                <div class="flex items-center justify-center gap-10">
                    <div class="relative shrink-0" style="width:170px;height:170px;">
                        <canvas id="passfailChart"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-slate-800">{{ $passRate }}%</span>
                            <span class="text-xs text-slate-500">Pass Rate</span>
                        </div>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-full bg-emerald-500"></span>
                            <span class="text-slate-600">Passed</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $passCount }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-full bg-red-400"></span>
                            <span class="text-slate-600">Failed</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $failCount }}</span>
                        </div>
                        <div class="flex items-center gap-3 border-t border-slate-100 pt-2">
                            <span class="text-slate-400">Total Attempts</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $totalAttempts }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ② Score Distribution Bar --}}
            <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Score Distribution</p>
                <p class="mt-0.5 mb-5 text-sm text-slate-500">Number of students in each score percentage range</p>
                <canvas id="scoreDistChart" height="170"></canvas>
            </div>

        </div>
        @endif

        {{-- Results Table --}}
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-lg font-bold text-slate-800">Student Results</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <select id="resultsSortDropdown"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <option value="newest">Newest to Oldest</option>
                        <option value="ranking">Ranking (High → Low)</option>
                        <option value="surname">Surname (A → Z)</option>
                    </select>
                    <a href="?tab=results&export=excel"
                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-emerald-700">
                        Export Excel
                    </a>
                </div>
            </div>

            @if($totalAttempts === 0)
                <p class="py-10 text-center text-sm text-slate-400">No student results yet.</p>
            @else
            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed text-sm">
                        <thead class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="w-12 px-4 py-3">Rank</th>
                                <th class="w-24 px-4 py-3">Student ID</th>
                                <th class="w-32 px-4 py-3">Surname</th>
                                <th class="w-32 px-4 py-3">First Name</th>
                                <th class="w-12 px-4 py-3">M.I.</th>
                                <th class="w-24 px-4 py-3">Gender</th>
                                <th class="w-20 px-4 py-3">Grade</th>
                                <th class="w-24 px-4 py-3">Section</th>
                                <th class="w-16 px-4 py-3">Score</th>
                                <th class="w-16 px-4 py-3">Total</th>
                                <th class="w-16 px-4 py-3">%</th>
                                <th class="w-24 px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody id="resultsTableBody">
                            @foreach($resultsRows as $index => $row)
                            @php
                                $trClass = 'hover:bg-slate-50 transition';
                                if ($index === 0)      $trClass .= ' bg-yellow-50 ring-1 ring-inset ring-yellow-200';
                                elseif ($index === 1)  $trClass .= ' bg-slate-50 ring-1 ring-inset ring-slate-200';
                                elseif ($index === 2)  $trClass .= ' bg-amber-50 ring-1 ring-inset ring-amber-200';
                            @endphp
                            <tr class="{{ $trClass }}">
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-bold text-slate-700">{{ $index + 1 }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap text-slate-600">{{ $row['student_id'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-medium text-slate-800">{{ $row['surname'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-medium text-slate-800">{{ $row['first_name'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap text-slate-600">{{ $row['middle_initial'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap capitalize text-slate-600">{{ $row['gender'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap text-slate-600">{{ $row['grade_level'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap text-slate-600">{{ $row['section'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-semibold text-slate-800">{{ $row['score'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap text-slate-600">{{ $row['total_points'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-semibold text-slate-800">{{ $row['percentage'] }}%</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                 {{ $row['status'] === 'Passed' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

    @endif {{-- end results tab --}}


    {{-- ════════════════════════════════════════════════════════════ --}}
    {{--  ANALYTICS TAB                                               --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    @if($tab === 'analytics')
    <div class="space-y-5">

        @if($totalAttempts === 0)
            <div class="rounded-3xl bg-white px-6 py-12 text-center shadow ring-1 ring-slate-200">
                <p class="text-lg font-semibold text-slate-700">No data available</p>
                <p class="mt-1 text-sm text-slate-400">Analytics charts will appear once students attempt the quiz.</p>
            </div>
        @else

        {{-- Charts row --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

            {{-- ③ Correct Rate per Question --}}
            <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Correct Rate per Question</p>
                <p class="mt-0.5 mb-5 text-sm text-slate-500">% of students who answered each question correctly</p>
                <canvas id="correctRateChart"></canvas>
            </div>

            {{-- ④ Difficulty Breakdown Donut --}}
            <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Difficulty Breakdown</p>
                <p class="mt-0.5 mb-5 text-sm text-slate-500">Questions classified by how well students performed</p>
                <div class="flex items-center justify-center gap-10">
                    <div class="shrink-0" style="width:170px;height:170px;">
                        <canvas id="difficultyChart"></canvas>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-full bg-emerald-500"></span>
                            <span class="text-slate-600">Easy (&ge;80%)</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $easyCount }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-full bg-amber-400"></span>
                            <span class="text-slate-600">Moderate (50–79%)</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $moderateCount }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 shrink-0 rounded-full bg-red-400"></span>
                            <span class="text-slate-600">Difficult (&lt;50%)</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $difficultCount }}</span>
                        </div>
                        <div class="flex items-center gap-3 border-t border-slate-100 pt-2">
                            <span class="text-slate-400">Total Questions</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $totalQuestions }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        @endif

        {{-- Analytics Table --}}
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Question Analytics</h2>
                    <p class="text-sm text-slate-500">Performance breakdown per question</p>
                </div>
                <a href="?tab=analytics&export=excel"
                   class="inline-flex items-center gap-2 self-start rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-emerald-700">
                    Export Excel
                </a>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed text-sm">
                        <thead class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="w-10 px-4 py-3">#</th>
                                <th class="w-64 px-4 py-3">Question</th>
                                <th class="w-28 px-4 py-3">Type</th>
                                <th class="w-20 px-4 py-3">Points</th>
                                <th class="w-24 px-4 py-3">Correct %</th>
                                <th class="w-20 px-4 py-3">Correct</th>
                                <th class="w-20 px-4 py-3">Wrong</th>
                                <th class="w-20 px-4 py-3">Avg Pts</th>
                                <th class="w-28 px-4 py-3">Difficulty</th>
                                <th class="w-28 px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($questionAnalytics as $q)
                            @php
                                $wrongCount = $q['attempted_count'] - $q['correct_count'];

                                if ($q['correct_rate'] >= 80) {
                                    $statusText  = 'Excellent';
                                    $statusClass = 'bg-emerald-100 text-emerald-700';
                                } elseif ($q['correct_rate'] >= 50) {
                                    $statusText  = 'Average';
                                    $statusClass = 'bg-amber-100 text-amber-700';
                                } else {
                                    $statusText  = 'Needs Review';
                                    $statusClass = 'bg-red-100 text-red-700';
                                }

                                $diffClass = match($q['difficulty']) {
                                    'Easy'     => 'bg-emerald-100 text-emerald-700',
                                    'Moderate' => 'bg-amber-100 text-amber-700',
                                    default    => 'bg-red-100 text-red-700',
                                };
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-semibold text-slate-700">{{ $q['order'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap text-slate-700">{{ $q['question_text'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap capitalize text-slate-600">{{ $q['question_type'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-semibold text-slate-800">{{ $q['points'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-semibold text-slate-800">{{ $q['correct_rate'] }}%</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-semibold text-emerald-600">{{ $q['correct_count'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap font-semibold text-red-500">{{ $wrongCount }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="overflow-x-auto"><p class="whitespace-nowrap text-slate-600">{{ $q['average_points'] }}</p></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $diffClass }}">
                                        {{ $q['difficulty'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-400">
                                    No questions found for this quiz.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    @endif {{-- end analytics tab --}}

    {{-- ── TAB BUTTONS ── --}}
    <div class="flex gap-2">
        @foreach(['results' => 'Results', 'analytics' => 'Analytics'] as $tabKey => $tabLabel)
        <a href="?tab={{ $tabKey }}"
           class="px-4 py-2 rounded-xl text-sm font-semibold transition
                  {{ $tab === $tabKey
                      ? ($isSuperAdmin ? '' : 'bg-indigo-600 text-white shadow')
                      : ($isSuperAdmin ? '' : 'bg-slate-200 text-slate-700 hover:bg-slate-300') }}"
           @if($isSuperAdmin)
               style="{{ $tab === $tabKey
                   ? 'background:rgba(99,102,241,0.22);color:#a5b4fc;border:1px solid rgba(99,102,241,0.4);'
                   : 'background:rgba(255,255,255,0.04);color:#64748b;border:1px solid rgba(255,255,255,0.06);' }}"
           @endif>
            {{ $tabLabel }}
        </a>
        @endforeach
    </div>


<script>
(function () {

    const TAB = @json($tab);

    Chart.defaults.color                  = '#64748b';
    Chart.defaults.font.family            = 'inherit';
    Chart.defaults.plugins.legend.display = false;

    const C = {
        text      : '#64748b',
        grid      : 'rgba(15,23,42,0.07)',
        pass      : '#10b981',
        fail      : '#f87171',
        easy      : '#10b981',
        moderate  : '#f59e0b',
        difficult : '#ef4444',
    };

    if (TAB === 'results') {
        const PASS_COUNT = @json($passCount);
        const FAIL_COUNT = @json($failCount);
        const BUCKETS    = @json(array_values($buckets));

        const pfEl = document.getElementById('passfailChart');
        if (pfEl && (PASS_COUNT + FAIL_COUNT) > 0) {
            new Chart(pfEl, {
                type : 'doughnut',
                data : {
                    labels   : ['Passed', 'Failed'],
                    datasets : [{
                        data            : [PASS_COUNT, FAIL_COUNT],
                        backgroundColor : [C.pass, C.fail],
                        borderColor     : '#ffffff',
                        borderWidth     : 3,
                        hoverOffset     : 6,
                    }]
                },
                options : {
                    cutout    : '72%',
                    animation : { animateRotate: true, duration: 900 },
                    plugins   : {
                        tooltip : {
                            callbacks : {
                                label : ctx => ` ${ctx.label}: ${ctx.parsed} student${ctx.parsed !== 1 ? 's' : ''}`
                            }
                        }
                    }
                }
            });
        }

        const sdEl = document.getElementById('scoreDistChart');
        if (sdEl) {
            const bgColors = [
                'rgba(239,68,68,0.65)',
                'rgba(249,115,22,0.65)',
                'rgba(234,179,8,0.65)',
                'rgba(34,197,94,0.55)',
                'rgba(16,185,129,0.75)',
            ];
            const bdColors = ['#ef4444','#f97316','#eab308','#22c55e','#10b981'];
            new Chart(sdEl, {
                type : 'bar',
                data : {
                    labels   : ['0–20%', '21–40%', '41–60%', '61–80%', '81–100%'],
                    datasets : [{
                        label           : 'Students',
                        data            : BUCKETS,
                        backgroundColor : bgColors,
                        borderColor     : bdColors,
                        borderWidth     : 1.5,
                        borderRadius    : 6,
                    }]
                },
                options : {
                    responsive : true,
                    animation  : { duration: 700 },
                    scales     : {
                        x : { grid: { color: C.grid }, ticks: { color: C.text } },
                        y : { beginAtZero: true, grid: { color: C.grid }, ticks: { color: C.text, stepSize: 1, precision: 0 } }
                    },
                    plugins : {
                        tooltip : {
                            callbacks : {
                                label : ctx => ` ${ctx.parsed.y} student${ctx.parsed.y !== 1 ? 's' : ''}`
                            }
                        }
                    }
                }
            });
        }
    }

    if (TAB === 'analytics') {
        const Q_DATA   = @json($questionAnalytics);
        const EASY_CNT = @json($easyCount);
        const MOD_CNT  = @json($moderateCount);
        const DIFF_CNT = @json($difficultCount);

        if (Q_DATA.length > 0) {
            const crEl = document.getElementById('correctRateChart');
            if (crEl) {
                const labels   = Q_DATA.map(q => 'Q' + q.order);
                const rates    = Q_DATA.map(q => q.correct_rate);
                const bgColors = Q_DATA.map(q => {
                    if (q.correct_rate >= 80) return 'rgba(16,185,129,0.65)';
                    if (q.correct_rate >= 50) return 'rgba(245,158,11,0.65)';
                    return 'rgba(239,68,68,0.65)';
                });
                const bdColors = Q_DATA.map(q => {
                    if (q.correct_rate >= 80) return C.easy;
                    if (q.correct_rate >= 50) return C.moderate;
                    return C.difficult;
                });
                new Chart(crEl, {
                    type : 'bar',
                    data : {
                        labels,
                        datasets : [{
                            label           : 'Correct Rate',
                            data            : rates,
                            backgroundColor : bgColors,
                            borderColor     : bdColors,
                            borderWidth     : 1.5,
                            borderRadius    : 5,
                        }]
                    },
                    options : {
                        indexAxis  : 'y',
                        responsive : true,
                        animation  : { duration: 750 },
                        scales     : {
                            x : { min: 0, max: 100, grid: { color: C.grid }, ticks: { color: C.text, callback: v => v + '%' } },
                            y : { grid: { display: false }, ticks: { color: C.text } }
                        },
                        plugins : {
                            tooltip : {
                                callbacks : {
                                    title : items => {
                                        const idx  = items[0].dataIndex;
                                        const text = Q_DATA[idx].question_text ?? '';
                                        return text.length > 65 ? text.slice(0, 62) + '…' : text;
                                    },
                                    label : ctx => ` Correct rate: ${ctx.parsed.x}%`
                                }
                            }
                        }
                    }
                });
            }

            const diffEl = document.getElementById('difficultyChart');
            if (diffEl && (EASY_CNT + MOD_CNT + DIFF_CNT) > 0) {
                new Chart(diffEl, {
                    type : 'doughnut',
                    data : {
                        labels   : ['Easy', 'Moderate', 'Difficult'],
                        datasets : [{
                            data            : [EASY_CNT, MOD_CNT, DIFF_CNT],
                            backgroundColor : [C.easy, C.moderate, C.difficult],
                            borderColor     : '#ffffff',
                            borderWidth     : 3,
                            hoverOffset     : 6,
                        }]
                    },
                    options : {
                        cutout    : '68%',
                        animation : { animateRotate: true, duration: 800 },
                        plugins   : {
                            tooltip : {
                                callbacks : {
                                    label : ctx => ` ${ctx.label}: ${ctx.parsed} question${ctx.parsed !== 1 ? 's' : ''}`
                                }
                            }
                        }
                    }
                });
            }
        }
    }

    // ── Results table client-side sort ────────────────────────────
    const dropdown  = document.getElementById('resultsSortDropdown');
    const tableBody = document.getElementById('resultsTableBody');

    if (dropdown && tableBody) {
        const originalRows = Array.from(tableBody.querySelectorAll('tr'));

        function updateRankAndHighlights(rows) {
            rows.forEach((row, i) => {
                const rankCell = row.querySelector('td:first-child p');
                if (rankCell) rankCell.textContent = i + 1;

                row.className = 'hover:bg-slate-50 transition';
                if (i === 0)      row.classList.add('bg-yellow-50', 'ring-1', 'ring-inset', 'ring-yellow-200');
                else if (i === 1) row.classList.add('bg-slate-50',  'ring-1', 'ring-inset', 'ring-slate-200');
                else if (i === 2) row.classList.add('bg-amber-50',  'ring-1', 'ring-inset', 'ring-amber-200');
            });
        }

        function sortTable(mode) {
            let rows = [...originalRows];
            if (mode === 'ranking') {
                rows.sort((a, b) => {
                    const sa = parseFloat(a.querySelectorAll('td')[8]?.querySelector('p')?.textContent) || 0;
                    const sb = parseFloat(b.querySelectorAll('td')[8]?.querySelector('p')?.textContent) || 0;
                    return sb - sa;
                });
            } else if (mode === 'surname') {
                rows.sort((a, b) => {
                    const na = a.querySelectorAll('td')[2]?.querySelector('p')?.textContent.trim().toLowerCase() || '';
                    const nb = b.querySelectorAll('td')[2]?.querySelector('p')?.textContent.trim().toLowerCase() || '';
                    return na.localeCompare(nb);
                });
            }
            tableBody.innerHTML = '';
            rows.forEach(r => tableBody.appendChild(r));
            updateRankAndHighlights(rows);
        }

        dropdown.addEventListener('change', () => sortTable(dropdown.value));
        updateRankAndHighlights(originalRows);
    }

})();
</script>

@endsection
