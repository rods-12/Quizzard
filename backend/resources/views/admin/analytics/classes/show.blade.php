@extends('admin.layouts.app')
@section('title', 'Class: ' . $classroom->name)

@section('content')
@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

<div class="space-y-6">

    {{-- ===== HERO ===== --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-8 py-10 shadow-2xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('admin.analytics.overview') }}" class="text-slate-400 hover:text-white text-sm transition">← Analytics</a>
                    <span class="text-slate-600">/</span>
                    <a href="{{ route('admin.analytics.classes') }}" class="text-slate-400 hover:text-white text-sm transition">Classes</a>
                    <span class="text-slate-600">/</span>
                    <span class="text-white text-sm font-medium">{{ $classroom->name }}</span>
                </div>
                <h1 class="text-3xl font-bold text-white">{{ $classroom->name }}</h1>
                <p class="mt-1 text-slate-400 text-sm">
                    Teacher: <span class="text-white font-medium">{{ $classroom->teacher->first_name ?? '' }} {{ $classroom->teacher->surname ?? '—' }}</span>
                    &nbsp;·&nbsp; Code: <span class="font-mono text-blue-300">{{ $classroom->class_code }}</span>
                </p>
            </div>
            <a href="{{ route('admin.analytics.classes.show.export', $classroom->id) }}"
               data-no-loading="true"
               class="self-start flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export Excel
            </a>
        </div>
    </div>

    {{-- ===== KPI STRIP ===== --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @php
            $kpiItems = [
                ['label' => 'Enrolled Students', 'value' => number_format($kpis['total_students'] ?? 0),    'icon' => '👩‍🎓', 'color' => 'blue'],
                ['label' => 'Total Attempts',    'value' => number_format($kpis['total_attempts'] ?? 0),    'icon' => '📝', 'color' => 'violet'],
                ['label' => 'Pass Rate',         'value' => number_format($kpis['pass_rate'] ?? 0, 1).'%',  'icon' => '✅', 'color' => 'emerald'],
                ['label' => 'Avg Score',         'value' => number_format($kpis['avg_score'] ?? 0, 1).'%',  'icon' => '📊', 'color' => 'amber'],
            ];
            $colorMap = [
                'blue'   => 'bg-blue-50 border-blue-200 text-blue-700',
                'violet' => 'bg-violet-50 border-violet-200 text-violet-700',
                'emerald'=> 'bg-emerald-50 border-emerald-200 text-emerald-700',
                'amber'  => 'bg-amber-50 border-amber-200 text-amber-700',
            ];
        @endphp
        @foreach($kpiItems as $kpi)
        <div class="rounded-2xl border bg-white p-5 shadow-sm ring-1 ring-slate-200 {{ $colorMap[$kpi['color']] }}">
            <span class="text-2xl">{{ $kpi['icon'] }}</span>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $kpi['value'] }}</p>
            <p class="text-xs font-medium text-slate-500 mt-1">{{ $kpi['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ===== CHARTS ROW ===== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Score Trend Over Time --}}
        <div class="rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-800 mb-4">Score Trend Over Time</h2>
            <canvas id="trendChart" height="220"></canvas>
        </div>

        {{-- Score Distribution --}}
        <div class="rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-800 mb-4">Score Distribution</h2>
            <canvas id="distributionChart" height="220"></canvas>
        </div>

    </div>

    {{-- ===== TOP & BOTTOM STUDENTS ===== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Top Students --}}
        <div class="rounded-2xl border bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center gap-2">
                <span class="text-lg">🏆</span>
                <h2 class="text-base font-semibold text-slate-800">Top Students in This Class</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Rank</th>
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-right">Avg Score</th>
                            <th class="px-4 py-3 text-right">Attempts</th>
                            <th class="px-4 py-3 text-right">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topStudents as $i => $student)
                        @php
                            $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '#'.($i+1) };
                            $rowBg = match($i) { 0 => 'bg-yellow-50', 1 => 'bg-slate-50', 2 => 'bg-amber-50', default => '' };
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $rowBg }}">
                            <td class="px-4 py-3 text-center font-bold text-lg">{{ $medal }}</td>
                            <td class="max-w-[240px] px-4 py-3">
                                <a href="{{ route('admin.analytics.students.show', $student->id) }}"
                                   class="block truncate font-semibold text-blue-600 hover:underline" title="{{ trim(($student->first_name ?? '') . ' ' . ($student->surname ?? '')) }}">
                                    {{ $student->first_name }} {{ $student->surname }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{ number_format($student->avg_score ?? 0, 1) }}%</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $student->total_attempts }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($student->pass_rate ?? 0, 1) }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">No attempts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bottom Students --}}
        <div class="rounded-2xl border bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center gap-2">
                <span class="text-lg">⚠️</span>
                <h2 class="text-base font-semibold text-slate-800">At-Risk Students in This Class</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Student</th>
                            <th class="px-4 py-3 text-right">Avg Score</th>
                            <th class="px-4 py-3 text-right">Attempts</th>
                            <th class="px-4 py-3 text-right">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bottomStudents as $student)
                        <tr class="hover:bg-red-50 transition">
                            <td class="max-w-[240px] px-4 py-3">
                                <a href="{{ route('admin.analytics.students.show', $student->id) }}"
                                   class="block truncate font-semibold text-blue-600 hover:underline" title="{{ trim(($student->first_name ?? '') . ' ' . ($student->surname ?? '')) }}">
                                    {{ $student->first_name }} {{ $student->surname }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-red-500">{{ number_format($student->avg_score ?? 0, 1) }}%</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $student->total_attempts }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($student->pass_rate ?? 0, 1) }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">No at-risk students found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ===== QUIZ PERFORMANCE TABLE ===== --}}
    <div class="rounded-2xl border bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-base font-semibold text-slate-800">Quiz Performance in This Class</h2>
            <p class="text-xs text-slate-400 mt-1">All quizzes assigned to this class with attempt statistics.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Quiz</th>
                        <th class="px-4 py-3 text-right">Total Attempts</th>
                        <th class="px-4 py-3 text-right">Avg Score</th>
                        <th class="px-4 py-3 text-right">Pass Rate</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($quizPerformance as $quiz)
                    @php
                        $pr = $quiz->pass_rate ?? 0;
                        $prColor = $pr > 75 ? 'text-emerald-600 bg-emerald-50' : ($pr > 40 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50');
                    @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="max-w-[320px] truncate px-4 py-3 font-semibold text-slate-800" title="{{ $quiz->title }}">{{ $quiz->title }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ number_format($quiz->total_attempts ?? 0) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format($quiz->avg_score ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-bold {{ $prColor }}">
                                {{ number_format($pr, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="#"
                               class="inline-flex items-center gap-1 rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-600 transition">
                                View Quiz
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No quizzes assigned to this class yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    const trendLabels = @json($chartData['trend_labels']);
    const trendScores = @json($chartData['trend_scores']);
    const distLabels  = @json($chartData['dist_labels']);
    const distCounts  = @json($chartData['dist_counts']);

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Avg Score %',
                data: trendScores,
                borderColor: 'rgba(59,130,246,1)',
                backgroundColor: 'rgba(59,130,246,0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(59,130,246,1)',
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('distributionChart'), {
        type: 'bar',
        data: {
            labels: distLabels,
            datasets: [{
                label: 'Students',
                data: distCounts,
                backgroundColor: [
                    'rgba(239,68,68,0.75)',
                    'rgba(245,158,11,0.75)',
                    'rgba(234,179,8,0.75)',
                    'rgba(16,185,129,0.75)',
                    'rgba(5,150,105,0.75)',
                ],
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>

@endsection
