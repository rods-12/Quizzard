@extends('admin.layouts.app')
@section('title', 'Class Analytics')

@section('content')
@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

<div class="space-y-6">

    {{-- ===== HERO ===== --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-8 py-10 shadow-2xl">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('admin.analytics.overview') }}"
                       class="text-slate-400 hover:text-white text-sm transition">← Analytics</a>
                    <span class="text-slate-600">/</span>
                    <span class="text-white text-sm font-medium">Classes</span>
                </div>
                <h1 class="text-3xl font-bold text-white">Class Analytics</h1>
                <p class="mt-1 text-slate-400 text-sm">Compare class performance, pass rates, and quiz engagement across all sections.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- Date Range Filter --}}
                <form method="GET" action="{{ route('admin.analytics.classes') }}" class="hidden flex-wrap items-center gap-2">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="text-slate-400 text-sm">to</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition">
                        Filter
                    </button>
                    @if(!empty($filters['date_from']) || !empty($filters['date_to']))
                        <a href="{{ route('admin.analytics.classes') }}"
                           class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600 transition">
                            Clear
                        </a>
                    @endif
                </form>
                {{-- Export --}}
                <a href="{{ route('admin.analytics.classes.export', request()->query()) }}"
                   class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>
    </div>

    @include('admin.analytics.partials.nav')
    @php
        $teacherOptions = $teachers->map(fn($teacher) => '<option value="' . e($teacher->id) . '"' . (($filters['teacher_id'] ?? null) == $teacher->id ? ' selected' : '') . '>' . e($teacher->name ?: $teacher->email) . '</option>')->implode('');
        $teacherFilter = '<label class="flex min-w-[190px] flex-col gap-1"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</span><select name="teacher_id" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"><option value="">All Teachers</option>' . $teacherOptions . '</select></label>';
    @endphp
    @include('admin.analytics.partials.filter-bar', ['routeName' => 'admin.analytics.classes', 'filters' => $filters, 'extraFields' => $teacherFilter])

    {{-- ===== KPI STRIP ===== --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @php
            $kpiItems = [
                ['label' => 'Total Classes',    'value' => number_format($kpis['total_classes'] ?? 0),    'icon' => '🏫', 'color' => 'blue'],
                ['label' => 'Total Students',   'value' => number_format($kpis['total_students'] ?? 0),   'icon' => '👩‍🎓', 'color' => 'violet'],
                ['label' => 'Avg Pass Rate',    'value' => number_format($kpis['avg_pass_rate'] ?? 0, 1).'%', 'icon' => '✅', 'color' => 'emerald'],
                ['label' => 'Avg Score',        'value' => number_format($kpis['avg_score'] ?? 0, 1).'%', 'icon' => '📊', 'color' => 'amber'],
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
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">{{ $kpi['icon'] }}</span>
            </div>
            <p class="text-2xl font-bold text-slate-900">{{ $kpi['value'] }}</p>
            <p class="text-xs font-medium text-slate-500 mt-1">{{ $kpi['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ===== CHARTS ROW ===== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Grouped Bar: Avg Score by Class --}}
        <div class="rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-800 mb-4">Average Score by Class</h2>
            <canvas id="classScoreChart" height="260"></canvas>
        </div>

        {{-- Stacked Bar: Pass vs Fail per Class --}}
        <div class="rounded-2xl border bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-base font-semibold text-slate-800 mb-4">Pass vs Fail Count per Class</h2>
            <canvas id="classPassFailChart" height="260"></canvas>
        </div>

    </div>

    {{-- ===== TOP 5 / BOTTOM 5 TABLES ===== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Top 5 --}}
        <div class="rounded-2xl border bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center gap-3">
                <span class="text-lg">🏆</span>
                <h2 class="text-base font-semibold text-slate-800">Top 5 Classes by Pass Rate</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Rank</th>
                            <th class="px-4 py-3 text-left">Class</th>
                            <th class="px-4 py-3 text-left">Teacher</th>
                            <th class="px-4 py-3 text-right">Pass Rate</th>
                            <th class="px-4 py-3 text-right">Avg Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topClasses as $i => $class)
                        @php
                            $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '#'.($i+1) };
                            $rowBg = match($i) { 0 => 'bg-yellow-50', 1 => 'bg-slate-50', 2 => 'bg-amber-50', default => '' };
                        @endphp
                        <tr class="hover:bg-slate-50 transition {{ $rowBg }}">
                            <td class="px-4 py-3 text-center font-bold text-lg">{{ $medal }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.analytics.classes.show', $class->id) }}"
                                   class="font-semibold text-blue-600 hover:underline">{{ $class->name }}</a>
                                <p class="text-xs text-slate-400">{{ $class->students_count ?? 0 }} students</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $class->teacher_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-emerald-600">{{ number_format($class->pass_rate ?? 0, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($class->avg_score ?? 0, 1) }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bottom 5 --}}
        <div class="rounded-2xl border bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center gap-3">
                <span class="text-lg">⚠️</span>
                <h2 class="text-base font-semibold text-slate-800">Bottom 5 — Needs Attention</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Rank</th>
                            <th class="px-4 py-3 text-left">Class</th>
                            <th class="px-4 py-3 text-left">Teacher</th>
                            <th class="px-4 py-3 text-right">Pass Rate</th>
                            <th class="px-4 py-3 text-right">Avg Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bottomClasses as $i => $class)
                        <tr class="hover:bg-red-50 transition">
                            <td class="px-4 py-3 text-center font-bold text-slate-500">#{{ $i+1 }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.analytics.classes.show', $class->id) }}"
                                   class="font-semibold text-blue-600 hover:underline">{{ $class->name }}</a>
                                <p class="text-xs text-slate-400">{{ $class->students_count ?? 0 }} students</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $class->teacher_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-red-500">{{ number_format($class->pass_rate ?? 0, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format($class->avg_score ?? 0, 1) }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- ===== FULL CLASS TABLE ===== --}}
    <div class="rounded-2xl border bg-white shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-semibold text-slate-800">All Classes</h2>
            <form method="GET" action="{{ route('admin.analytics.classes') }}" class="flex flex-wrap items-center gap-2">
                @foreach($filters as $k => $v)
                    @if($k !== 'search' && $k !== 'sort' && $k !== 'direction')
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <input type="hidden" name="teacher_id" value="{{ $filters['teacher_id'] ?? '' }}">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="Search class or teacher…"
                       class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 w-52">
                <select name="sort" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <option value="pass_rate"    {{ ($filters['sort'] ?? '') === 'pass_rate'    ? 'selected' : '' }}>Sort: Pass Rate</option>
                    <option value="avg_score"    {{ ($filters['sort'] ?? '') === 'avg_score'    ? 'selected' : '' }}>Sort: Avg Score</option>
                    <option value="total_attempts"{{ ($filters['sort'] ?? '') === 'total_attempts'? 'selected' : '' }}>Sort: Attempts</option>
                    <option value="students_count"{{ ($filters['sort'] ?? '') === 'students_count'? 'selected' : '' }}>Sort: Students</option>
                    <option value="name"         {{ ($filters['sort'] ?? '') === 'name'         ? 'selected' : '' }}>Sort: Name</option>
                </select>
                <select name="direction" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <option value="desc" {{ ($filters['direction'] ?? 'desc') === 'desc' ? 'selected' : '' }}>↓ Desc</option>
                    <option value="asc"  {{ ($filters['direction'] ?? 'desc') === 'asc'  ? 'selected' : '' }}>↑ Asc</option>
                </select>
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
                    Apply
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Class</th>
                        <th class="px-4 py-3 text-left">Teacher</th>
                        <th class="px-4 py-3 text-left">Latest Attempt</th>
                        <th class="px-4 py-3 text-right">Students</th>
                        <th class="px-4 py-3 text-right">Quizzes</th>
                        <th class="px-4 py-3 text-right">Attempts</th>
                        <th class="px-4 py-3 text-right">Avg Score</th>
                        <th class="px-4 py-3 text-right">Pass Rate</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($classes as $class)
                    @php
                        $pr = $class->pass_rate ?? 0;
                        $prColor = $pr > 75 ? 'text-emerald-600 bg-emerald-50' : ($pr > 40 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50');
                    @endphp
                    <tr class="cursor-pointer hover:bg-slate-50 transition"
                        onclick="window.showPageLoadingOverlay && window.showPageLoadingOverlay('Loading class analytics...'); window.location='{{ route('admin.analytics.classes.show', $class->id) }}'">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-slate-800">{{ $class->name }}</span>
                            <p class="text-xs text-slate-400">{{ $class->class_code ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $class->teacher_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $class->latest_attempt_at ? \Carbon\Carbon::parse($class->latest_attempt_at)->format('M d, Y') : 'No attempts' }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($class->students_count ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($class->quizzes_count ?? 0) }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($class->total_attempts ?? 0) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format($class->avg_score ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-bold {{ $prColor }}">
                                {{ number_format($pr, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                            <a href="{{ route('admin.analytics.classes.show', $class->id) }}"
                               class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 transition">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                            No classes found for the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($classes->hasPages())
        <div class="border-t border-slate-100 px-6 py-4">
            {{ $classes->appends($filters)->links() }}
        </div>
        @endif
    </div>

</div>

{{-- ===== CHART.JS ===== --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    const labels   = @json($chartData['labels']);
    const scores   = @json($chartData['avg_scores']);
    const passArr  = @json($chartData['pass_counts']);
    const failArr  = @json($chartData['fail_counts']);

    // ── Avg Score Bar ──────────────────────────────────────────
    new Chart(document.getElementById('classScoreChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Avg Score %',
                data: scores,
                backgroundColor: scores.map(s =>
                    s > 75 ? 'rgba(16,185,129,0.75)' :
                    s > 40 ? 'rgba(245,158,11,0.75)' :
                              'rgba(239,68,68,0.75)'
                ),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100,
                     ticks: { callback: v => v + '%' },
                     grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false },
                     ticks: { maxRotation: 30, minRotation: 0, font: { size: 11 } } }
            }
        }
    });

    // ── Pass vs Fail Stacked Bar ────────────────────────────────
    new Chart(document.getElementById('classPassFailChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Pass', data: passArr, backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 4, borderSkipped: false },
                { label: 'Fail', data: failArr, backgroundColor: 'rgba(239,68,68,0.75)', borderRadius: 4, borderSkipped: false },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { stacked: true, grid: { display: false },
                     ticks: { maxRotation: 30, minRotation: 0, font: { size: 11 } } },
                y: { stacked: true, beginAtZero: true,
                     grid: { color: 'rgba(0,0,0,0.05)' } }
            }
        }
    });
})();
</script>

@endsection
