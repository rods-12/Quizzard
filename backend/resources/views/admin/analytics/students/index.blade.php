{{-- resources/views/admin/analytics/students/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Student Analytics')

@section('content')
@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

<div class="space-y-6">

    {{-- ===== HERO ===== --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-8 py-8 shadow-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <a href="{{ route('admin.analytics.overview') }}"
                       class="text-slate-400 hover:text-white text-sm transition flex items-center gap-1">
                        ← Analytics
                    </a>
                </div>
                <h1 class="text-3xl font-bold text-white">Student Analytics</h1>
                <p class="mt-1 text-slate-400 text-sm">Performance breakdown for all students across the system.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.analytics.students.export', array_merge(request()->query(), [])) }}"
                   class="rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 transition flex items-center gap-2">
                    ⬇ Export Excel
                </a>
            </div>
        </div>
    </div>

    @include('admin.analytics.partials.nav')
    @php
        $filterControlClass = 'rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100';
        $gradeOptions = $gradeLevels->map(fn($grade) => '<option value="' . e($grade) . '"' . (($filters['grade_level'] ?? null) == $grade ? ' selected' : '') . '>Grade ' . e($grade) . '</option>')->implode('');
        $quizOptions = $quizzesForFilter->map(fn($quiz) => '<option value="' . e($quiz->id) . '"' . (($filters['quiz_id'] ?? null) == $quiz->id ? ' selected' : '') . '>' . e($quiz->title) . '</option>')->implode('');
        $classOptions = $classesForFilter->map(fn($class) => '<option value="' . e($class->id) . '"' . (($filters['class_id'] ?? null) == $class->id ? ' selected' : '') . '>' . e($class->name) . '</option>')->implode('');
        $extraFields = '<label class="flex min-w-[150px] flex-col gap-1"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grade</span><select name="grade_level" class="' . $filterControlClass . '"><option value="">All Grades</option>' . $gradeOptions . '</select></label>'
            . '<label class="flex min-w-[220px] flex-col gap-1"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quiz Basis</span><select name="quiz_id" class="' . $filterControlClass . '"><option value="">All Quizzes</option>' . $quizOptions . '</select></label>'
            . '<label class="flex min-w-[220px] flex-col gap-1"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Class Basis</span><select name="class_id" class="' . $filterControlClass . '"><option value="">All Classes</option>' . $classOptions . '</select></label>';
    @endphp
    @include('admin.analytics.partials.filter-bar', [
        'routeName' => 'admin.analytics.students',
        'filters' => $filters,
        'extraFields' => $extraFields,
    ])

    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
        <p class="font-semibold">Current basis: {{ $basisLabel }}</p>
        <p class="mt-1">Top, bottom, chart, pass-rate, and table values below are calculated from this selected basis and date range.</p>
    </div>

    {{-- ===== KPI STRIP ===== --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @php
            $kpis = [
                ['label' => 'Total Students', 'value' => number_format($totalStudents), 'icon' => '👥', 'color' => 'blue'],
                ['label' => 'Avg Score', 'value' => number_format($avgScore, 1) . '%', 'icon' => '📊', 'color' => 'indigo'],
                ['label' => 'System Pass Rate', 'value' => number_format($passRate, 1) . '%', 'icon' => '✅', 'color' => 'emerald'],
                ['label' => 'Active Students', 'value' => number_format($activeStudents), 'icon' => '🏃', 'color' => 'amber'],
            ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">{{ $kpi['icon'] }}</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ $kpi['value'] }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $kpi['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ===== TOP 10 / BOTTOM 10 TABLES ===== --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- Top 10 --}}
        <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">🏆 Top 10 Performing Students</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Highest average scores in selected period</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <th class="px-4 py-3 text-left w-10">Rank</th>
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-center">Grade</th>
                            <th class="px-4 py-3 text-center">Avg Score</th>
                            <th class="px-4 py-3 text-center">Pass Rate</th>
                            <th class="px-4 py-3 text-center">Attempts</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topStudents as $i => $student)
                        @php
                            $medal = match($i) {
                                0 => ['bg' => 'bg-yellow-50', 'badge' => 'bg-yellow-400 text-yellow-900', 'emoji' => '🥇'],
                                1 => ['bg' => 'bg-slate-50', 'badge' => 'bg-slate-400 text-white', 'emoji' => '🥈'],
                                2 => ['bg' => 'bg-amber-50', 'badge' => 'bg-amber-500 text-white', 'emoji' => '🥉'],
                                default => ['bg' => '', 'badge' => 'bg-slate-200 text-slate-700', 'emoji' => ''],
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $medal['bg'] }}">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold {{ $medal['badge'] }}">
                                    {{ $medal['emoji'] ?: $i + 1 }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $student->full_name }}</p>
                                <p class="text-xs text-slate-400">{{ $student->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $student->grade_level ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-emerald-600">{{ number_format($student->avg_score, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-slate-700">{{ number_format($student->pass_rate, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $student->attempt_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.analytics.students.show', $student->id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-semibold">View →</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No data for selected period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bottom 10 --}}
        <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">⚠️ Bottom 10 At-Risk Students</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Lowest average scores — may need intervention</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <th class="px-4 py-3 text-left w-10">Rank</th>
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-center">Grade</th>
                            <th class="px-4 py-3 text-center">Avg Score</th>
                            <th class="px-4 py-3 text-center">Pass Rate</th>
                            <th class="px-4 py-3 text-center">Attempts</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bottomStudents as $i => $student)
                        <tr class="hover:bg-red-50 transition">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                    {{ $i + 1 }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $student->full_name }}</p>
                                <p class="text-xs text-slate-400">{{ $student->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $student->grade_level ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold text-red-600">{{ number_format($student->avg_score, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-slate-700">{{ number_format($student->pass_rate, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $student->attempt_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.analytics.students.show', $student->id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-semibold">View →</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No data for selected period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== COMPARISON CHART ===== --}}
    <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-800 text-lg mb-1">📈 Top 10 vs Bottom 10 — Score Comparison</h2>
        <p class="text-xs text-slate-500 mb-5">Average scores side by side for quick visual comparison</p>
        <div class="h-72">
            <canvas id="comparisonChart"></canvas>
        </div>
    </div>

    {{-- ===== SCORE DISTRIBUTION CHART ===== --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-800 text-lg mb-1">📊 Score Distribution</h2>
            <p class="text-xs text-slate-500 mb-5">How student scores spread across ranges</p>
            <div class="h-64">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
        <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-800 text-lg mb-1">🎓 Performance by Grade Level</h2>
            <p class="text-xs text-slate-500 mb-5">Average score per grade level</p>
            <div class="h-64">
                <canvas id="gradeChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ===== FULL STUDENT TABLE ===== --}}
    <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-bold text-slate-800 text-lg">👥 All Students</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ $allStudents->total() }} students total — click any row for full profile</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('admin.analytics.students') }}" class="flex items-center gap-2">
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    <input type="hidden" name="grade_level" value="{{ request('grade_level') }}">
                    <input type="hidden" name="quiz_id" value="{{ request('quiz_id') }}">
                    <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search student…"
                           class="rounded-xl border border-slate-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
                    <select name="sort" class="rounded-xl border border-slate-200 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="avg_score_desc" {{ request('sort','avg_score_desc') == 'avg_score_desc' ? 'selected' : '' }}>Avg Score ↓</option>
                        <option value="avg_score_asc" {{ request('sort') == 'avg_score_asc' ? 'selected' : '' }}>Avg Score ↑</option>
                        <option value="pass_rate_desc" {{ request('sort') == 'pass_rate_desc' ? 'selected' : '' }}>Pass Rate ↓</option>
                        <option value="attempts_desc" {{ request('sort') == 'attempts_desc' ? 'selected' : '' }}>Most Attempts</option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name A–Z</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 transition">Search</button>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                        <th class="px-3 py-2 text-left">Student</th>
                        <th class="px-3 py-2 text-center">Grade</th>
                        <th class="px-3 py-2 text-center">Section</th>
                        <th class="px-3 py-2 text-center">Avg Score</th>
                        <th class="px-3 py-2 text-center">Pass Rate</th>
                        <th class="px-3 py-2 text-center">Attempts</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($allStudents as $student)
                    <tr class="hover:bg-slate-50 transition cursor-pointer"
                        onclick="window.showPageLoadingOverlay && window.showPageLoadingOverlay('Loading student analytics...'); window.location='{{ route('admin.analytics.students.show', $student->id) }}'">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs flex-shrink-0">
                                    {{ strtoupper(substr($student->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $student->full_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $student->email }}</p>
                                    <p class="text-[11px] text-slate-500">Latest: {{ $student->latest_attempt_at ? \Carbon\Carbon::parse($student->latest_attempt_at)->format('M d, Y') : 'No attempts' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $student->grade_level ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $student->section ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($student->attempt_count > 0)
                                @php $score = $student->avg_score; @endphp
                                <span class="font-bold {{ $score > 75 ? 'text-emerald-600' : ($score > 40 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ number_format($score, 1) }}%
                                </span>
                            @else
                                <span class="text-slate-400 text-xs">No attempts</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($student->attempt_count > 0)
                                <span class="{{ $student->pass_rate > 75 ? 'text-emerald-600' : ($student->pass_rate > 40 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ number_format($student->pass_rate, 1) }}%
                                </span>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $student->attempt_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($student->attempt_count == 0)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">Inactive</span>
                            @elseif($student->avg_score <= 40)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">At Risk</span>
                            @elseif($student->avg_score <= 75)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Developing</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Passing</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                            <a href="{{ route('admin.analytics.students.show', $student->id) }}"
                               class="text-blue-600 hover:text-blue-800 text-xs font-semibold">View →</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($allStudents->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $allStudents->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const chartDefaults = {
    font: { family: 'inherit' },
    color: '#64748b',
};
Chart.defaults.font.family = chartDefaults.font.family;

// ── Comparison Chart ──────────────────────────────────────────
const topLabels  = @json($topStudents->pluck('full_name')->map(fn($n) => strlen($n) > 15 ? substr($n,0,13).'…' : $n)->values());
const topScores  = @json($topStudents->pluck('avg_score')->map(fn($v) => round($v,1))->values());
const botLabels  = @json($bottomStudents->pluck('full_name')->map(fn($n) => strlen($n) > 15 ? substr($n,0,13).'…' : $n)->values());
const botScores  = @json($bottomStudents->pluck('avg_score')->map(fn($v) => round($v,1))->values());

new Chart(document.getElementById('comparisonChart'), {
    type: 'bar',
    data: {
        labels: [...topLabels, ...botLabels],
        datasets: [{
            label: 'Avg Score %',
            data: [...topScores, ...botScores],
            backgroundColor: [
                ...Array(topScores.length).fill('rgba(16,185,129,0.75)'),
                ...Array(botScores.length).fill('rgba(239,68,68,0.75)'),
            ],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { callback: v => v + '%' } },
            x: { grid: { display: false }, ticks: { maxRotation: 35, font: { size: 11 } } }
        }
    }
});

// ── Distribution Chart ────────────────────────────────────────
const distLabels = ['0–20%', '21–40%', '41–60%', '61–80%', '81–100%'];
const distData   = @json($scoreDistribution);
new Chart(document.getElementById('distributionChart'), {
    type: 'bar',
    data: {
        labels: distLabels,
        datasets: [{
            label: 'Students',
            data: distData,
            backgroundColor: ['#ef4444','#f97316','#eab308','#22c55e','#10b981'],
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});

// ── Grade Chart ───────────────────────────────────────────────
const gradeLabels = @json(array_keys($gradePerformance));
const gradeData   = @json(array_values($gradePerformance));
new Chart(document.getElementById('gradeChart'), {
    type: 'bar',
    data: {
        labels: gradeLabels.map(g => 'Grade ' + g),
        datasets: [{
            label: 'Avg Score %',
            data: gradeData,
            backgroundColor: 'rgba(99,102,241,0.75)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { callback: v => v + '%' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endsection
