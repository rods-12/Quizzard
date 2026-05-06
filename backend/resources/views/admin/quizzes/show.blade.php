@extends('admin.layouts.app')

@section('title', 'Quiz Details')

@section('content')

@php
    $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin';

    // --- Score Distribution buckets (built from real $resultsRows) ---
    $buckets = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];
    foreach ($resultsRows as $row) {
        $pct = $row['percentage'];
        if ($pct <= 20)      $buckets['0-20']++;
        elseif ($pct <= 40)  $buckets['21-40']++;
        elseif ($pct <= 60)  $buckets['41-60']++;
        elseif ($pct <= 80)  $buckets['61-80']++;
        else                 $buckets['81-100']++;
    }

    // --- Difficulty counts (built from real $questionAnalytics) ---
    $easyCount     = $questionAnalytics->where('difficulty', 'Easy')->count();
    $moderateCount = $questionAnalytics->where('difficulty', 'Moderate')->count();
    $difficultCount= $questionAnalytics->where('difficulty', 'Difficult')->count();
@endphp

{{-- Chart.js CDN --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="space-y-6">

    {{-- ── BACK ── --}}
    <div>
        <a href="{{ route('admin.classes.details', $class->id) }}"
           class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition
                  {{ $isSuperAdmin ? '' : 'bg-white text-slate-700 shadow ring-1 ring-slate-200 hover:bg-slate-50' }}"
           @if($isSuperAdmin)
               style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);color:#cbd5e1;"
               onmouseover="this.style.background='rgba(255,255,255,0.11)';"
               onmouseout="this.style.background='rgba(255,255,255,0.06)';"
           @endif>
            ← Back to Class
        </a>
    </div>

    {{-- ── QUIZ HEADER ── --}}
    @if($isSuperAdmin)
    <div class="rounded-2xl p-6 text-white shadow-xl"
         style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 60%,#1e1b4b 100%);border:1px solid rgba(99,102,241,0.3);">
        <p class="text-xs font-semibold uppercase tracking-widest" style="color:#a5b4fc;">Quiz Overview</p>
        <h1 class="mt-1.5 text-3xl font-bold">{{ $quiz->title }}</h1>
        <p class="mt-2 text-sm" style="color:#c7d2fe;">{{ $quiz->description ?? 'No description' }}</p>
        <div class="mt-6 flex flex-wrap gap-3">
            @foreach([
                ['Questions',   $totalQuestions],
                ['Attempts',    $totalAttempts],
                ['Avg Score',   $averageScore],
                ['Avg %',       $averagePercentage.'%'],
                ['Pass Rate',   $passRate.'%'],
                ['Highest',     $highestScore],
                ['Lowest',      $lowestScore],
            ] as [$lbl, $val])
            <div class="rounded-xl px-4 py-3" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs" style="color:#a5b4fc;">{{ $lbl }}</p>
                <p class="mt-1 text-xl font-bold text-white">{{ $val }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="rounded-3xl p-6 text-white shadow-xl"
         style="background:linear-gradient(135deg,#020617,#0f172a,#1e1b4b);">
        <h1 class="text-3xl font-bold">{{ $quiz->title }}</h1>
        <p class="mt-2 text-slate-300">{{ $quiz->description ?? 'No description' }}</p>
        <div class="mt-6 flex flex-wrap gap-4">
            @foreach([
                ['Questions',   $totalQuestions],
                ['Attempts',    $totalAttempts],
                ['Avg Score',   $averageScore],
                ['Avg %',       $averagePercentage.'%'],
                ['Pass Rate',   $passRate.'%'],
                ['Highest',     $highestScore],
                ['Lowest',      $lowestScore],
            ] as [$lbl, $val])
            <div class="rounded-xl bg-slate-800/60 px-4 py-2">
                <p class="text-xs text-slate-400">{{ $lbl }}</p>
                <p class="text-xl font-bold">{{ $val }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

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


    {{-- ════════════════════════════════════════════════════════════ --}}
    {{--  RESULTS TAB                                                 --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    @if($tab === 'results')

    @if($totalAttempts === 0)
        {{-- Empty state --}}
        @if($isSuperAdmin)
        <div class="rounded-2xl px-6 py-12 text-center" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-lg font-semibold text-white">No attempts yet</p>
            <p class="mt-1 text-sm" style="color:#475569;">Charts and results will appear once students complete this quiz.</p>
        </div>
        @else
        <div class="rounded-3xl bg-white px-6 py-12 text-center shadow ring-1 ring-slate-200">
            <p class="text-lg font-semibold text-slate-700">No attempts yet</p>
            <p class="mt-1 text-sm text-slate-400">Charts and results will appear once students complete this quiz.</p>
        </div>
        @endif
    @else

    {{-- Charts row --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

        {{-- ① Pass / Fail Donut --}}
        @if($isSuperAdmin)
        <div class="rounded-2xl p-5 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-xs font-semibold uppercase tracking-widest" style="color:#475569;">Pass / Fail Ratio</p>
            <p class="mt-0.5 text-sm mb-5" style="color:#64748b;">Overall outcome breakdown across all attempts</p>
            <div class="flex items-center justify-center gap-10">
                <div class="relative shrink-0" style="width:170px;height:170px;">
                    <canvas id="passfailChart"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold text-white">{{ $passRate }}%</span>
                        <span class="text-xs" style="color:#a5b4fc;">Pass Rate</span>
                    </div>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full shrink-0" style="background:#34d399;"></span>
                        <span style="color:#94a3b8;">Passed</span>
                        <span class="ml-auto font-bold text-white">{{ $passCount }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full shrink-0" style="background:#f87171;"></span>
                        <span style="color:#94a3b8;">Failed</span>
                        <span class="ml-auto font-bold text-white">{{ $failCount }}</span>
                    </div>
                    <div class="flex items-center gap-3 pt-2" style="border-top:1px solid rgba(255,255,255,0.06);">
                        <span style="color:#64748b;">Total Attempts</span>
                        <span class="ml-auto font-bold text-white">{{ $totalAttempts }}</span>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Pass / Fail Ratio</p>
            <p class="mt-0.5 text-sm text-slate-500 mb-5">Overall outcome breakdown across all attempts</p>
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
                        <span class="h-3 w-3 rounded-full bg-emerald-500 shrink-0"></span>
                        <span class="text-slate-600">Passed</span>
                        <span class="ml-auto font-bold text-slate-800">{{ $passCount }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full bg-red-400 shrink-0"></span>
                        <span class="text-slate-600">Failed</span>
                        <span class="ml-auto font-bold text-slate-800">{{ $failCount }}</span>
                    </div>
                    <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                        <span class="text-slate-400">Total Attempts</span>
                        <span class="ml-auto font-bold text-slate-800">{{ $totalAttempts }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ② Score Distribution Bar --}}
        @if($isSuperAdmin)
        <div class="rounded-2xl p-5 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-xs font-semibold uppercase tracking-widest" style="color:#475569;">Score Distribution</p>
            <p class="mt-0.5 text-sm mb-5" style="color:#64748b;">Number of students in each score percentage range</p>
            <canvas id="scoreDistChart" height="170"></canvas>
        </div>
        @else
        <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Score Distribution</p>
            <p class="mt-0.5 text-sm text-slate-500 mb-5">Number of students in each score percentage range</p>
            <canvas id="scoreDistChart" height="170"></canvas>
        </div>
        @endif

    </div>{{-- end charts row --}}
    @endif {{-- end totalAttempts > 0 --}}

    {{-- Results Table --}}
    @if($isSuperAdmin)
    <div class="rounded-2xl p-6 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
    @else
    <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
    @endif
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <h2 class="font-bold text-lg {{ $isSuperAdmin ? 'text-white' : 'text-slate-800' }}">Student Results</h2>
            <div class="flex items-center gap-3 flex-wrap">
                @if($isSuperAdmin)
                <select id="resultsSortDropdown" class="sa-select rounded-lg border px-4 py-2 text-sm outline-none transition">
                @else
                <select id="resultsSortDropdown" class="rounded-xl border border-slate-300 px-4 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @endif
                    <option value="newest">Newest to Oldest</option>
                    <option value="ranking">Ranking (High → Low)</option>
                    <option value="surname">Surname (A → Z)</option>
                </select>
                <a href="?tab=results&export=excel"
                   class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700 transition">
                    Export Excel
                </a>
            </div>
        </div>

        @if($totalAttempts === 0)
        <p class="py-10 text-center text-sm {{ $isSuperAdmin ? '' : 'text-slate-400' }}"
           @if($isSuperAdmin) style="color:#475569;" @endif>
           No student results yet.
        </p>
        @else
        <div class="overflow-x-auto rounded-xl"
             @if($isSuperAdmin) style="border:1px solid rgba(255,255,255,0.06);" @endif>
            <table class="w-full text-sm">
                <thead class="{{ $isSuperAdmin ? '' : 'bg-slate-50 text-slate-600' }} uppercase text-xs tracking-wider"
                       @if($isSuperAdmin) style="background:rgba(255,255,255,0.03);color:#475569;" @endif>
                    <tr>
                        <th class="p-3 text-left">Rank</th>
                        <th class="p-3 text-left">Student ID</th>
                        <th class="p-3 text-left">Surname</th>
                        <th class="p-3 text-left">First Name</th>
                        <th class="p-3 text-left">M.I.</th>
                        <th class="p-3 text-left">Gender</th>
                        <th class="p-3 text-left">Grade</th>
                        <th class="p-3 text-left">Section</th>
                        <th class="p-3 text-left">Score</th>
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-left">%</th>
                        <th class="p-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody id="resultsTableBody">
                    @foreach($resultsRows as $index => $row)
                    @php
                        if ($isSuperAdmin) {
                            $trStyle = 'border-bottom:1px solid rgba(255,255,255,0.04);border-left:3px solid transparent;';
                            if ($index === 0)    $trStyle = 'border-bottom:1px solid rgba(255,255,255,0.04);background:rgba(250,204,21,0.06);border-left:3px solid rgba(250,204,21,0.45);';
                            elseif($index === 1) $trStyle = 'border-bottom:1px solid rgba(255,255,255,0.04);background:rgba(148,163,184,0.05);border-left:3px solid rgba(148,163,184,0.3);';
                            elseif($index === 2) $trStyle = 'border-bottom:1px solid rgba(255,255,255,0.04);background:rgba(251,146,60,0.05);border-left:3px solid rgba(251,146,60,0.3);';
                        } else {
                            $trClass = 'hover:bg-slate-50 transition';
                            if ($index === 0)    $trClass .= ' bg-yellow-50 ring-1 ring-inset ring-yellow-200';
                            elseif($index === 1) $trClass .= ' bg-slate-50 ring-1 ring-inset ring-slate-200';
                            elseif($index === 2) $trClass .= ' bg-amber-50 ring-1 ring-inset ring-amber-200';
                        }
                    @endphp

                    @if($isSuperAdmin)
                    <tr style="{{ $trStyle }}" class="transition hover:bg-white/5">
                        <td class="p-3 font-bold" style="color:#a5b4fc;">{{ $index + 1 }}</td>
                        <td class="p-3" style="color:#94a3b8;">{{ $row['student_id'] }}</td>
                        <td class="p-3 font-medium" style="color:#e2e8f0;">{{ $row['surname'] }}</td>
                        <td class="p-3 font-medium" style="color:#e2e8f0;">{{ $row['first_name'] }}</td>
                        <td class="p-3" style="color:#94a3b8;">{{ $row['middle_initial'] }}</td>
                        <td class="p-3 capitalize" style="color:#94a3b8;">{{ $row['gender'] }}</td>
                        <td class="p-3" style="color:#94a3b8;">{{ $row['grade_level'] }}</td>
                        <td class="p-3" style="color:#94a3b8;">{{ $row['section'] }}</td>
                        <td class="p-3 font-semibold" style="color:#e2e8f0;">{{ $row['score'] }}</td>
                        <td class="p-3" style="color:#94a3b8;">{{ $row['total_points'] }}</td>
                        <td class="p-3 font-semibold" style="color:#e2e8f0;">{{ $row['percentage'] }}%</td>
                        <td class="p-3">
                            @if($row['status'] === 'Passed')
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                      style="background:rgba(16,185,129,0.12);color:#34d399;">Passed</span>
                            @else
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                      style="background:rgba(239,68,68,0.12);color:#f87171;">Failed</span>
                            @endif
                        </td>
                    </tr>
                    @else
                    <tr class="{{ $trClass ?? '' }}">
                        <td class="p-3 font-bold text-slate-700">{{ $index + 1 }}</td>
                        <td class="p-3 text-slate-600">{{ $row['student_id'] }}</td>
                        <td class="p-3 font-medium text-slate-800">{{ $row['surname'] }}</td>
                        <td class="p-3 font-medium text-slate-800">{{ $row['first_name'] }}</td>
                        <td class="p-3 text-slate-600">{{ $row['middle_initial'] }}</td>
                        <td class="p-3 capitalize text-slate-600">{{ $row['gender'] }}</td>
                        <td class="p-3 text-slate-600">{{ $row['grade_level'] }}</td>
                        <td class="p-3 text-slate-600">{{ $row['section'] }}</td>
                        <td class="p-3 font-semibold text-slate-800">{{ $row['score'] }}</td>
                        <td class="p-3 text-slate-600">{{ $row['total_points'] }}</td>
                        <td class="p-3 text-slate-800">{{ $row['percentage'] }}%</td>
                        <td class="p-3">
                            <span class="font-semibold {{ $row['status'] === 'Passed' ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $row['status'] }}
                            </span>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>{{-- end results table card --}}
    @endif {{-- end results tab --}}


    {{-- ════════════════════════════════════════════════════════════ --}}
    {{--  ANALYTICS TAB                                               --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    @if($tab === 'analytics')
    <div class="space-y-5">

        @if($totalAttempts === 0)
        {{-- Empty state --}}
        @if($isSuperAdmin)
        <div class="rounded-2xl px-6 py-12 text-center" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-lg font-semibold text-white">No data available</p>
            <p class="mt-1 text-sm" style="color:#475569;">Analytics charts will appear once students attempt the quiz.</p>
        </div>
        @else
        <div class="rounded-3xl bg-white px-6 py-12 text-center shadow ring-1 ring-slate-200">
            <p class="text-lg font-semibold text-slate-700">No data available</p>
            <p class="mt-1 text-sm text-slate-400">Analytics charts will appear once students attempt the quiz.</p>
        </div>
        @endif
        @else

        {{-- Charts row --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

            {{-- ③ Correct Rate per Question – Horizontal Bar --}}
            @if($isSuperAdmin)
            <div class="rounded-2xl p-5 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
                <p class="text-xs font-semibold uppercase tracking-widest" style="color:#475569;">Correct Rate per Question</p>
                <p class="mt-0.5 text-sm mb-5" style="color:#64748b;">% of students who answered each question correctly</p>
                <canvas id="correctRateChart"></canvas>
            </div>
            @else
            <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Correct Rate per Question</p>
                <p class="mt-0.5 text-sm text-slate-500 mb-5">% of students who answered each question correctly</p>
                <canvas id="correctRateChart"></canvas>
            </div>
            @endif

            {{-- ④ Difficulty Breakdown Donut --}}
            @if($isSuperAdmin)
            <div class="rounded-2xl p-5 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
                <p class="text-xs font-semibold uppercase tracking-widest" style="color:#475569;">Difficulty Breakdown</p>
                <p class="mt-0.5 text-sm mb-5" style="color:#64748b;">Questions classified by how well students performed</p>
                <div class="flex items-center justify-center gap-10">
                    <div class="shrink-0" style="width:170px;height:170px;">
                        <canvas id="difficultyChart"></canvas>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full shrink-0" style="background:#34d399;"></span>
                            <span style="color:#94a3b8;">Easy (&ge;80%)</span>
                            <span class="ml-auto font-bold text-white">{{ $easyCount }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full shrink-0" style="background:#fbbf24;"></span>
                            <span style="color:#94a3b8;">Moderate (50–79%)</span>
                            <span class="ml-auto font-bold text-white">{{ $moderateCount }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full shrink-0" style="background:#f87171;"></span>
                            <span style="color:#94a3b8;">Difficult (&lt;50%)</span>
                            <span class="ml-auto font-bold text-white">{{ $difficultCount }}</span>
                        </div>
                        <div class="flex items-center gap-3 pt-2" style="border-top:1px solid rgba(255,255,255,0.06);">
                            <span style="color:#64748b;">Total Questions</span>
                            <span class="ml-auto font-bold text-white">{{ $totalQuestions }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Difficulty Breakdown</p>
                <p class="mt-0.5 text-sm text-slate-500 mb-5">Questions classified by how well students performed</p>
                <div class="flex items-center justify-center gap-10">
                    <div class="shrink-0" style="width:170px;height:170px;">
                        <canvas id="difficultyChart"></canvas>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="text-slate-600">Easy (&ge;80%)</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $easyCount }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-amber-400 shrink-0"></span>
                            <span class="text-slate-600">Moderate (50–79%)</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $moderateCount }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-red-400 shrink-0"></span>
                            <span class="text-slate-600">Difficult (&lt;50%)</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $difficultCount }}</span>
                        </div>
                        <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                            <span class="text-slate-400">Total Questions</span>
                            <span class="ml-auto font-bold text-slate-800">{{ $totalQuestions }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- end charts row --}}
        @endif {{-- end totalAttempts > 0 --}}

        {{-- Analytics Table --}}
        @if($isSuperAdmin)
        <div class="rounded-2xl p-6 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
        @else
        <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
        @endif
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-bold text-lg {{ $isSuperAdmin ? 'text-white' : 'text-slate-800' }}">Question Analytics</h2>
                    <p class="text-sm {{ $isSuperAdmin ? '' : 'text-slate-500' }}"
                       @if($isSuperAdmin) style="color:#64748b;" @endif>
                        Performance breakdown per question
                    </p>
                </div>
                <a href="?tab=analytics&export=excel"
                   class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700 transition self-start">
                    Export Excel
                </a>
            </div>

            <div class="overflow-x-auto rounded-xl"
                 @if($isSuperAdmin) style="border:1px solid rgba(255,255,255,0.06);" @endif>
                <table class="w-full text-sm">
                    <thead class="{{ $isSuperAdmin ? '' : 'bg-slate-50 text-slate-600' }} uppercase text-xs tracking-wider"
                           @if($isSuperAdmin) style="background:rgba(255,255,255,0.03);color:#475569;" @endif>
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Question</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Points</th>
                            <th class="px-4 py-3 text-left">Correct %</th>
                            <th class="px-4 py-3 text-left">Correct</th>
                            <th class="px-4 py-3 text-left">Wrong</th>
                            <th class="px-4 py-3 text-left">Avg Pts</th>
                            <th class="px-4 py-3 text-left">Difficulty</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($questionAnalytics as $q)
                        @php
                            $wrongCount = $q['attempted_count'] - $q['correct_count'];

                            // Status label + styles
                            if ($q['correct_rate'] >= 80) {
                                $statusText    = 'Excellent';
                                $statusSaStyle = 'background:rgba(16,185,129,0.12);color:#34d399;';
                                $statusClass   = 'bg-emerald-100 text-emerald-700';
                            } elseif ($q['correct_rate'] >= 50) {
                                $statusText    = 'Average';
                                $statusSaStyle = 'background:rgba(245,158,11,0.12);color:#fbbf24;';
                                $statusClass   = 'bg-yellow-100 text-yellow-700';
                            } else {
                                $statusText    = 'Needs Review';
                                $statusSaStyle = 'background:rgba(239,68,68,0.12);color:#f87171;';
                                $statusClass   = 'bg-red-100 text-red-700';
                            }

                            // Difficulty badge styles
                            $diffSaStyle = match($q['difficulty']) {
                                'Easy'     => 'background:rgba(16,185,129,0.10);color:#34d399;',
                                'Moderate' => 'background:rgba(245,158,11,0.10);color:#fbbf24;',
                                default    => 'background:rgba(239,68,68,0.10);color:#f87171;',
                            };
                            $diffClass = match($q['difficulty']) {
                                'Easy'     => 'bg-emerald-100 text-emerald-700',
                                'Moderate' => 'bg-amber-100 text-amber-700',
                                default    => 'bg-red-100 text-red-700',
                            };
                        @endphp

                        @if($isSuperAdmin)
                        <tr class="transition hover:bg-white/5"
                            style="border-bottom:1px solid rgba(255,255,255,0.04);">
                            <td class="px-4 py-3 font-semibold" style="color:#a5b4fc;">{{ $q['order'] }}</td>
                            <td class="px-4 py-3 max-w-xs" style="color:#e2e8f0;">{{ $q['question_text'] }}</td>
                            <td class="px-4 py-3 capitalize" style="color:#94a3b8;">{{ $q['question_type'] }}</td>
                            <td class="px-4 py-3 font-semibold" style="color:#e2e8f0;">{{ $q['points'] }}</td>
                            <td class="px-4 py-3 font-semibold" style="color:#e2e8f0;">{{ $q['correct_rate'] }}%</td>
                            <td class="px-4 py-3 font-semibold" style="color:#34d399;">{{ $q['correct_count'] }}</td>
                            <td class="px-4 py-3 font-semibold" style="color:#f87171;">{{ $wrongCount }}</td>
                            <td class="px-4 py-3" style="color:#94a3b8;">{{ $q['average_points'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                      style="{{ $diffSaStyle }}">{{ $q['difficulty'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                      style="{{ $statusSaStyle }}">{{ $statusText }}</span>
                            </td>
                        </tr>
                        @else
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-700">{{ $q['order'] }}</td>
                            <td class="px-4 py-3 text-slate-700 max-w-xs">{{ $q['question_text'] }}</td>
                            <td class="px-4 py-3 capitalize text-slate-600">{{ $q['question_type'] }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $q['points'] }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $q['correct_rate'] }}%</td>
                            <td class="px-4 py-3 font-semibold text-emerald-600">{{ $q['correct_count'] }}</td>
                            <td class="px-4 py-3 font-semibold text-red-500">{{ $wrongCount }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $q['average_points'] }}</td>
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
                        @endif

                        @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-sm"
                                @if($isSuperAdmin) style="color:#475569;" @else class="text-slate-400" @endif>
                                No questions found for this quiz.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>{{-- end analytics table card --}}

    </div>{{-- end analytics space-y-5 --}}
    @endif {{-- end analytics tab --}}

</div>{{-- end page space-y-6 --}}


{{-- ════════════════════════════════════════════════════════════════ --}}
{{--  CHART.JS — all values come directly from PHP variables         --}}
{{--  No hardcoded dummy data. Every number is real.                 --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<script>
(function () {

    const IS_SA = @json($isSuperAdmin);
    const TAB   = @json($tab);

    // ── colour tokens per theme ───────────────────────────────────
    const C = IS_SA ? {
        text      : '#94a3b8',
        grid      : 'rgba(255,255,255,0.05)',
        pass      : '#34d399',
        fail      : '#f87171',
        easy      : '#34d399',
        moderate  : '#fbbf24',
        difficult : '#f87171',
        bg        : '#161b27',
    } : {
        text      : '#64748b',
        grid      : 'rgba(15,23,42,0.07)',
        pass      : '#10b981',
        fail      : '#f87171',
        easy      : '#10b981',
        moderate  : '#f59e0b',
        difficult  : '#ef4444',
        bg        : '#ffffff',
    };

    // ── shared Chart.js defaults ──────────────────────────────────
    Chart.defaults.color          = C.text;
    Chart.defaults.font.family    = 'inherit';
    Chart.defaults.plugins.legend.display = false;

    // ─────────────────────────────────────────────────────────────
    //  RESULTS TAB
    // ─────────────────────────────────────────────────────────────
    if (TAB === 'results') {

        // Real data injected from PHP
        const PASS_COUNT = @json($passCount);
        const FAIL_COUNT = @json($failCount);
        // Bucket values in order: 0-20, 21-40, 41-60, 61-80, 81-100
        const BUCKETS    = @json(array_values($buckets));

        // ① Pass / Fail Donut ─────────────────────────────────────
        const pfEl = document.getElementById('passfailChart');
        if (pfEl && (PASS_COUNT + FAIL_COUNT) > 0) {
            new Chart(pfEl, {
                type : 'doughnut',
                data : {
                    labels   : ['Passed', 'Failed'],
                    datasets : [{
                        data            : [PASS_COUNT, FAIL_COUNT],
                        backgroundColor : [C.pass, C.fail],
                        borderColor     : IS_SA ? '#161b27' : '#ffffff',
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
                                label : ctx =>
                                    ` ${ctx.label}: ${ctx.parsed} student${ctx.parsed !== 1 ? 's' : ''}`
                            }
                        }
                    }
                }
            });
        }

        // ② Score Distribution Bar ────────────────────────────────
        const sdEl = document.getElementById('scoreDistChart');
        if (sdEl) {
            // Gradient colours from red→green across the buckets
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
                        x : {
                            grid  : { color: C.grid },
                            ticks : { color: C.text },
                        },
                        y : {
                            beginAtZero : true,
                            grid        : { color: C.grid },
                            ticks       : { color: C.text, stepSize: 1, precision: 0 },
                        }
                    },
                    plugins : {
                        tooltip : {
                            callbacks : {
                                label : ctx =>
                                    ` ${ctx.parsed.y} student${ctx.parsed.y !== 1 ? 's' : ''}`
                            }
                        }
                    }
                }
            });
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  ANALYTICS TAB
    // ─────────────────────────────────────────────────────────────
    if (TAB === 'analytics') {

        // Real data injected from PHP
        const Q_DATA    = @json($questionAnalytics);   // full array from controller
        const EASY_CNT  = @json($easyCount);
        const MOD_CNT   = @json($moderateCount);
        const DIFF_CNT  = @json($difficultCount);

        if (Q_DATA.length > 0) {

            // ③ Correct Rate per Question – Horizontal Bar ────────
            const crEl = document.getElementById('correctRateChart');
            if (crEl) {
                // Label each bar as Q1, Q2 … and show full text in tooltip
                const labels = Q_DATA.map(q => 'Q' + q.order);
                const rates  = Q_DATA.map(q => q.correct_rate);

                // Colour each bar by its own difficulty level (live from data)
                const bgColors = Q_DATA.map(q => {
                    if (q.correct_rate >= 80) return IS_SA ? 'rgba(52,211,153,0.70)'  : 'rgba(16,185,129,0.65)';
                    if (q.correct_rate >= 50) return IS_SA ? 'rgba(251,191,36,0.70)'  : 'rgba(245,158,11,0.65)';
                    return IS_SA ? 'rgba(248,113,113,0.70)' : 'rgba(239,68,68,0.65)';
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
                        indexAxis  : 'y',   // <-- horizontal bars
                        responsive : true,
                        animation  : { duration: 750 },
                        scales     : {
                            x : {
                                min   : 0,
                                max   : 100,
                                grid  : { color: C.grid },
                                ticks : { color: C.text, callback: v => v + '%' },
                            },
                            y : {
                                grid  : { display: false },
                                ticks : { color: C.text },
                            }
                        },
                        plugins : {
                            tooltip : {
                                callbacks : {
                                    // Show truncated question text as tooltip title
                                    title : items => {
                                        const idx  = items[0].dataIndex;
                                        const text = Q_DATA[idx].question_text ?? '';
                                        return text.length > 65
                                            ? text.slice(0, 62) + '…'
                                            : text;
                                    },
                                    label : ctx =>
                                        ` Correct rate: ${ctx.parsed.x}%`
                                }
                            }
                        }
                    }
                });
            }

            // ④ Difficulty Breakdown Donut ────────────────────────
            const diffEl = document.getElementById('difficultyChart');
            if (diffEl && (EASY_CNT + MOD_CNT + DIFF_CNT) > 0) {
                new Chart(diffEl, {
                    type : 'doughnut',
                    data : {
                        labels   : ['Easy', 'Moderate', 'Difficult'],
                        datasets : [{
                            data            : [EASY_CNT, MOD_CNT, DIFF_CNT],
                            backgroundColor : [C.easy, C.moderate, C.difficult],
                            borderColor     : IS_SA ? '#161b27' : '#ffffff',
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
                                    label : ctx =>
                                        ` ${ctx.label}: ${ctx.parsed} question${ctx.parsed !== 1 ? 's' : ''}`
                                }
                            }
                        }
                    }
                });
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  RESULTS TABLE CLIENT-SIDE SORT
    // ─────────────────────────────────────────────────────────────
    const dropdown  = document.getElementById('resultsSortDropdown');
    const tableBody = document.getElementById('resultsTableBody');

    if (dropdown && tableBody) {
        const originalRows = Array.from(tableBody.querySelectorAll('tr'));

        function updateRankAndHighlights(rows) {
            rows.forEach((row, i) => {
                // Rank is the first <td>
                const rankCell = row.querySelector('td:first-child');
                if (rankCell) rankCell.textContent = i + 1;

                if (IS_SA) {
                    row.style.background  = '';
                    row.style.borderLeft  = '3px solid transparent';
                    if (i === 0) {
                        row.style.background = 'rgba(250,204,21,0.06)';
                        row.style.borderLeft = '3px solid rgba(250,204,21,0.45)';
                    } else if (i === 1) {
                        row.style.background = 'rgba(148,163,184,0.05)';
                        row.style.borderLeft = '3px solid rgba(148,163,184,0.3)';
                    } else if (i === 2) {
                        row.style.background = 'rgba(251,146,60,0.05)';
                        row.style.borderLeft = '3px solid rgba(251,146,60,0.3)';
                    }
                } else {
                    row.className = 'hover:bg-slate-50 transition';
                    if (i === 0) row.classList.add('bg-yellow-50', 'ring-1', 'ring-inset', 'ring-yellow-200');
                    else if (i === 1) row.classList.add('bg-slate-50', 'ring-1', 'ring-inset', 'ring-slate-200');
                    else if (i === 2) row.classList.add('bg-amber-50', 'ring-1', 'ring-inset', 'ring-amber-200');
                }
            });
        }

        function sortTable(mode) {
            let rows = [...originalRows];

            if (mode === 'ranking') {
                // Score is the 9th td → index 8
                rows.sort((a, b) => {
                    const sa = parseFloat(a.querySelectorAll('td')[8]?.textContent) || 0;
                    const sb = parseFloat(b.querySelectorAll('td')[8]?.textContent) || 0;
                    return sb - sa;
                });
            } else if (mode === 'surname') {
                // Surname is the 3rd td → index 2
                rows.sort((a, b) => {
                    const na = a.querySelectorAll('td')[2]?.textContent.trim().toLowerCase() || '';
                    const nb = b.querySelectorAll('td')[2]?.textContent.trim().toLowerCase() || '';
                    return na.localeCompare(nb);
                });
            }
            // 'newest' → keep original server-side order (no re-sort needed)

            tableBody.innerHTML = '';
            rows.forEach(r => tableBody.appendChild(r));
            updateRankAndHighlights(rows);
        }

        dropdown.addEventListener('change', () => sortTable(dropdown.value));
        // Apply top-3 highlights on initial load
        updateRankAndHighlights(originalRows);
    }

})();
</script>

@endsection
