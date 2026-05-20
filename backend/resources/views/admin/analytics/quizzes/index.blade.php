{{-- resources/views/admin/analytics/quizzes/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Quiz Analytics')
@section('content')
@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

<div class="space-y-6">

    {{-- ===== HERO ===== --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-8 py-8 text-white shadow-xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.analytics.overview') }}"
                       class="inline-flex items-center rounded-xl border border-white/25 bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-lg transition hover:bg-blue-50">
                        ← Analytics
                    </a>
                    <span class="text-white/40">›</span>
                    <span class="text-sm text-white/70">Quiz Analytics</span>
                </div>
                <h1 class="mt-3 text-2xl font-bold tracking-tight">📋 Quiz Analytics</h1>
                <p class="mt-1 text-sm text-slate-300">Pass rates, difficulty analysis, and performance rankings across all quizzes.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                {{-- Date Range Filter --}}
                <form method="GET" action="{{ route('admin.analytics.quizzes') }}" class="hidden flex-wrap gap-2">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    <span class="self-center text-white/50 text-sm">to</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="rounded-xl border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    @if($isSuperAdmin)
                    <select name="teacher_id" class="rounded-xl border border-white/20 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">All Teachers</option>
                        @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ ($filters['teacher_id'] ?? '') == $t->id ? 'selected' : '' }}>
                            {{ $t->first_name }} {{ $t->surname }}
                        </option>
                        @endforeach
                    </select>
                    @endif
                    <button type="submit" class="rounded-xl bg-blue-500 px-4 py-2 text-sm font-medium hover:bg-blue-400 transition">Filter</button>
                    <a href="{{ route('admin.analytics.quizzes') }}" class="rounded-xl bg-white/10 px-4 py-2 text-sm font-medium hover:bg-white/20 transition">Reset</a>
                </form>
                {{-- Export --}}
                <a href="{{ route('admin.analytics.quizzes.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                   data-no-loading="true"
                   class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-500">
                    ⬇ Export Excel
                </a>
            </div>
        </div>
    </div>

    @include('admin.analytics.partials.nav')
    @php
        $teacherOptions = $teachers->map(fn($teacher) => '<option value="' . e($teacher->id) . '"' . (($filters['teacher_id'] ?? null) == $teacher->id ? ' selected' : '') . '>' . e($teacher->name ?: $teacher->email) . '</option>')->implode('');
        $teacherFilter = '<label class="flex min-w-[190px] flex-col gap-1"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</span><select name="teacher_id" data-compact-select class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"><option value="">All Teachers</option>' . $teacherOptions . '</select></label>';
    @endphp
    @include('admin.analytics.partials.filter-bar', ['routeName' => 'admin.analytics.quizzes', 'filters' => $filters, 'extraFields' => $teacherFilter, 'showSearch' => false])

    {{-- ===== KPI STRIP ===== --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @php
        $kpiCards = [
            ['label' => 'Total Quizzes', 'value' => number_format($kpis['total_quizzes'] ?? 0), 'icon' => '📋', 'color' => 'blue'],
            ['label' => 'Total Attempts', 'value' => number_format($kpis['total_attempts'] ?? 0), 'icon' => '✍️', 'color' => 'purple'],
            ['label' => 'Avg Pass Rate', 'value' => number_format($kpis['avg_pass_rate'] ?? 0, 1) . '%', 'icon' => '✅', 'color' => 'emerald'],
            ['label' => 'Avg Score', 'value' => number_format($kpis['avg_score'] ?? 0, 1) . '%', 'icon' => '📊', 'color' => 'amber'],
        ];
        $colorMap = ['blue' => 'bg-blue-50 text-blue-700', 'purple' => 'bg-purple-50 text-purple-700', 'emerald' => 'bg-emerald-50 text-emerald-700', 'amber' => 'bg-amber-50 text-amber-700'];
        @endphp
        @foreach($kpiCards as $kpi)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $colorMap[$kpi['color']] }} text-lg">{{ $kpi['icon'] }}</div>
                <div>
                    <p class="text-xs text-slate-500">{{ $kpi['label'] }}</p>
                    <p class="text-xl font-bold text-slate-800">{{ $kpi['value'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ===== CHARTS ROW ===== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Pass/Fail Donut --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
            <h2 class="mb-1 text-sm font-semibold text-slate-700">System-Wide Pass/Fail Ratio</h2>
            <p class="mb-4 text-xs text-slate-400">All attempts in selected period</p>
            <div class="relative mx-auto" style="max-width:220px;">
                <canvas id="passfailDonut" height="220"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-bold text-emerald-600">{{ number_format($kpis['avg_pass_rate'] ?? 0, 1) }}%</span>
                    <span class="text-xs text-slate-400">Pass Rate</span>
                </div>
            </div>
            <div class="mt-4 flex justify-center gap-6 text-xs">
                <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-emerald-500 inline-block"></span>Pass ({{ number_format($chartData['pass_count'] ?? 0) }})</div>
                <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-red-400 inline-block"></span>Fail ({{ number_format($chartData['fail_count'] ?? 0) }})</div>
            </div>
        </div>

        {{-- Scatter: Avg Score vs Attempts --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100 lg:col-span-2">
            <h2 class="mb-1 text-sm font-semibold text-slate-700">Difficulty vs Popularity</h2>
            <p class="mb-4 text-xs text-slate-400">Avg Score % (Y) vs Total Attempts (X) — bubbles sized by pass rate</p>
            <canvas id="scatterChart" height="160"></canvas>
        </div>
    </div>

    {{-- ===== TOP 10 / BOTTOM 10 TABLES ===== --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- Top 10 Highest Pass Rate --}}
        <div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h2 class="font-semibold text-slate-800">🏆 Top 10 Highest Pass Rate</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Quizzes students performed best in</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Best Performing</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Quiz</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Teacher</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Attempts</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Avg Score</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($topPassRate as $i => $quiz)
                        @php
                        $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '' };
                        $rowBg = match($i) { 0 => 'bg-yellow-50', 1 => 'bg-slate-50', 2 => 'bg-amber-50', default => 'bg-white hover:bg-slate-50' };
                        @endphp
                        <tr class="{{ $rowBg }} cursor-pointer transition"
                            onclick="window.showPageLoadingOverlay && window.showPageLoadingOverlay('Loading quiz analytics...'); window.location='{{ route('admin.analytics.quizzes.show', $quiz->id) }}'">
                            <td class="px-4 py-3 font-bold text-slate-500">{{ $medal ?: ($i + 1) }}</td>
                            <td class="px-4 py-3">
                                @if(Route::has('admin.analytics.quizzes.show'))
                                    <a href="{{ route('admin.analytics.quizzes.show', $quiz->id) }}" class="font-medium text-blue-600 hover:underline">{{ Str::limit($quiz->title, 30) }}</a>
                                @else
                                    <span class="font-medium text-slate-800">{{ Str::limit($quiz->title, 30) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $quiz->teacher_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ number_format($quiz->total_attempts ?? 0) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-semibold text-slate-700">{{ number_format($quiz->avg_score ?? 0, 1) }}%</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    {{ number_format($quiz->pass_rate ?? 0, 1) }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No completed quiz attempts match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bottom 10 Lowest Pass Rate --}}
        <div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h2 class="font-semibold text-slate-800">⚠️ Bottom 10 Lowest Pass Rate</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Quizzes that may need review or adjustment</p>
                </div>
                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Needs Attention</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Quiz</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Teacher</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Attempts</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Avg Score</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($bottomPassRate as $i => $quiz)
                        @php
                        $passRate = $quiz->pass_rate ?? 0;
                        $badgeColor = $passRate <= 40 ? 'bg-red-100 text-red-700' : ($passRate <= 75 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
                        @endphp
                        <tr class="cursor-pointer bg-white transition hover:bg-red-50/30"
                            onclick="window.showPageLoadingOverlay && window.showPageLoadingOverlay('Loading quiz analytics...'); window.location='{{ route('admin.analytics.quizzes.show', $quiz->id) }}'">
                            <td class="px-4 py-3 font-bold text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-3">
                                @if(Route::has('admin.analytics.quizzes.show'))
                                    <a href="{{ route('admin.analytics.quizzes.show', $quiz->id) }}" class="font-medium text-blue-600 hover:underline">{{ Str::limit($quiz->title, 30) }}</a>
                                @else
                                    <span class="font-medium text-slate-800">{{ Str::limit($quiz->title, 30) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $quiz->teacher_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ number_format($quiz->total_attempts ?? 0) }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ number_format($quiz->avg_score ?? 0, 1) }}%</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full {{ $badgeColor }} px-2.5 py-0.5 text-xs font-semibold">
                                    {{ number_format($quiz->pass_rate ?? 0, 1) }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No separate low-pass quizzes outside the top list for this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== PASS RATE DISTRIBUTION BAR CHART ===== --}}
    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
        <h2 class="mb-1 text-sm font-semibold text-slate-700">Pass Rate Distribution</h2>
        <p class="mb-4 text-xs text-slate-400">How many quizzes fall in each pass rate bracket</p>
        <canvas id="distributionBar" height="80"></canvas>
    </div>

    {{-- ===== FULL QUIZ TABLE ===== --}}
    <div class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
        <div class="flex flex-col gap-3 px-6 py-4 border-b border-slate-100 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="font-semibold text-slate-800">All Quizzes</h2>
                <p class="text-xs text-slate-400">Showing {{ $allQuizzes->firstItem() }}–{{ $allQuizzes->lastItem() }} of {{ $allQuizzes->total() }} quizzes</p>
            </div>
            <form method="GET" action="{{ route('admin.analytics.quizzes') }}" class="flex flex-wrap gap-2">
                @foreach(array_filter($filters ?? []) as $key => $val)
                    @if($key !== 'sort' && $key !== 'direction' && $key !== 'search')
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search quiz name…"
                       class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300" />
                <select name="sort" data-compact-select class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <option value="pass_rate" {{ request('sort','pass_rate') === 'pass_rate' ? 'selected' : '' }}>Sort: Pass Rate</option>
                    <option value="avg_score" {{ request('sort') === 'avg_score' ? 'selected' : '' }}>Sort: Avg Score</option>
                    <option value="total_attempts" {{ request('sort') === 'total_attempts' ? 'selected' : '' }}>Sort: Attempts</option>
                    <option value="title" {{ request('sort') === 'title' ? 'selected' : '' }}>Sort: Title</option>
                </select>
                <select name="direction" data-compact-select class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <option value="desc" {{ request('direction','desc') === 'desc' ? 'selected' : '' }}>↓ Desc</option>
                    <option value="asc" {{ request('direction') === 'asc' ? 'selected' : '' }}>↑ Asc</option>
                </select>
                <button type="submit" class="rounded-xl bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-400 transition">Apply</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Quiz Title</th>
                        @if($isSuperAdmin)<th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Teacher</th>@endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500">Latest Attempt</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Questions</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Attempts</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Avg Score</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Pass Rate</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Highest</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Lowest</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($allQuizzes as $quiz)
                    @php
                    $pr = $quiz->pass_rate ?? 0;
                    $prColor = $pr > 75 ? 'bg-emerald-100 text-emerald-700' : ($pr > 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700');
                    @endphp
                    <tr class="cursor-pointer hover:bg-slate-50 transition"
                        onclick="window.showPageLoadingOverlay && window.showPageLoadingOverlay('Loading quiz analytics...'); window.location='{{ route('admin.analytics.quizzes.show', array_merge(['quiz' => $quiz->id], request()->query())) }}'">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-800">{{ Str::limit($quiz->title, 35) }}</p>
                            <p class="text-[11px] text-slate-500">Latest: {{ $quiz->latest_attempt_at ? \Carbon\Carbon::parse($quiz->latest_attempt_at)->format('M d, Y') : 'No attempts' }}</p>
                        </td>
                        @if($isSuperAdmin)
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $quiz->teacher_name ?? '—' }}</td>
                        @endif
                        <td class="px-4 py-3 text-slate-600">{{ $quiz->latest_attempt_at ? \Carbon\Carbon::parse($quiz->latest_attempt_at)->format('M d, Y') : 'No attempts' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $quiz->questions_count ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ number_format($quiz->total_attempts ?? 0) }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ number_format($quiz->avg_score ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full {{ $prColor }} px-2.5 py-0.5 text-xs font-semibold">{{ number_format($pr, 1) }}%</span>
                        </td>
                        <td class="px-4 py-3 text-center text-emerald-600 font-medium">{{ number_format($quiz->highest_score ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-center text-red-500 font-medium">{{ number_format($quiz->lowest_score ?? 0, 1) }}%</td>
                        <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                            @if($quiz->is_published)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700 font-medium">Published</span>
                            @else
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 font-medium">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.analytics.quizzes.show', array_merge(['quiz' => $quiz->id], request()->query())) }}"
                               class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-100 transition">
                                View Quiz
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 11 : 10 }}" class="px-4 py-12 text-center text-slate-400">No quizzes found for the selected filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($allQuizzes->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $allQuizzes->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>

{{-- ===== CHARTS JS ===== --}}
@php
    $distributionLabels = $chartData['distribution_labels'] ?? ['0-40%', '41-75%', '76-100%'];
    $distributionCounts = $chartData['distribution_counts'] ?? [0, 0, 0];
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Pass/Fail Donut
    new Chart(document.getElementById('passfailDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Pass', 'Fail'],
            datasets: [{
                data: [{{ $chartData['pass_count'] }}, {{ $chartData['fail_count'] }}],
                backgroundColor: ['#10b981', '#f87171'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            cutout: '72%',
            plugins: { legend: { display: false } },
            responsive: true
        }
    });

    // Scatter chart: attempts (X) vs avg_score (Y)
    const scatterData = @json($chartData['scatter'] ?? []);
    new Chart(document.getElementById('scatterChart'), {
        type: 'bubble',
        data: {
            datasets: [{
                label: 'Quizzes',
                data: scatterData.map(d => ({
                    x: d.attempts,
                    y: d.avg_score,
                    r: Math.max(4, Math.min(20, d.pass_rate / 8))
                })),
                backgroundColor: scatterData.map(d =>
                    d.pass_rate > 75 ? 'rgba(16,185,129,0.5)' :
                    d.pass_rate > 40 ? 'rgba(245,158,11,0.5)' :
                    'rgba(239,68,68,0.5)'
                ),
                borderColor: 'transparent'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const d = scatterData[ctx.dataIndex];
                            return [`${d.title}`, `Attempts: ${d.attempts}`, `Avg: ${d.avg_score.toFixed(1)}%`, `Pass Rate: ${d.pass_rate.toFixed(1)}%`];
                        }
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: 'Total Attempts' }, beginAtZero: true, grid: { color: '#f1f5f9' } },
                y: { title: { display: true, text: 'Avg Score %' }, min: 0, max: 100, grid: { color: '#f1f5f9' } }
            }
        }
    });

    // Distribution bar
    const distLabels = @json($distributionLabels);
    const distCounts = @json($distributionCounts);
    new Chart(document.getElementById('distributionBar'), {
        type: 'bar',
        data: {
            labels: distLabels,
            datasets: [{
                label: 'Number of Quizzes',
                data: distCounts,
                backgroundColor: ['#ef4444','#f59e0b','#10b981'],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } }
            }
        }
    });
});
</script>
@endsection
