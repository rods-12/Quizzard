{{-- resources/views/admin/analytics/students/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Student Profile — ' . $student->full_name)

@section('content')
@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

<div class="space-y-6">

    {{-- ===== HERO ===== --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-8 py-8 shadow-xl">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-5">
                {{-- Avatar --}}
                <div class="w-16 h-16 rounded-2xl bg-blue-500 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0 shadow-lg">
                    {{ strtoupper(substr($student->full_name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <a href="{{ route('admin.analytics.students') }}"
                           class="text-slate-400 hover:text-white text-sm transition">← Students</a>
                    </div>
                    <h1 class="text-2xl font-bold text-white">{{ $student->full_name }}</h1>
                    <p class="text-slate-400 text-sm mt-0.5">{{ $student->email }}
                        @if($profile)
                            · Grade {{ $profile->grade_level }} — {{ $profile->section }}
                            · {{ ucfirst($profile->gender ?? '') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.analytics.students.show.export', $student->id) }}"
                   class="rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold px-4 py-2 transition flex items-center gap-2">
                    ⬇ Export Report
                </a>
            </div>
        </div>
    </div>

    {{-- ===== KPI STRIP ===== --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        @php
            $kpis = [
                ['label' => 'Total Attempts',  'value' => $stats['total_attempts'],              'icon' => '📝', 'sub' => 'quizzes taken'],
                ['label' => 'Average Score',   'value' => number_format($stats['avg_score'],1).'%', 'icon' => '📊', 'sub' => 'overall avg'],
                ['label' => 'Pass Rate',       'value' => number_format($stats['pass_rate'],1).'%',  'icon' => '✅', 'sub' => 'of attempts'],
                ['label' => 'Highest Score',   'value' => number_format($stats['best_score'],1).'%', 'icon' => '🏆', 'sub' => 'best attempt'],
                ['label' => 'Quizzes Passed',  'value' => $stats['passed'],                      'icon' => '🎯', 'sub' => 'out of ' . $stats['total_attempts']],
            ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm">
            <div class="text-2xl mb-2">{{ $kpi['icon'] }}</div>
            <p class="text-2xl font-bold text-slate-800">{{ $kpi['value'] }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $kpi['label'] }}</p>
            <p class="text-xs text-slate-400">{{ $kpi['sub'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ===== RANK BADGES ===== --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm flex items-center gap-4">
            <div class="text-4xl">🏫</div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wide">Class Rank</p>
                <p class="text-xl font-bold text-slate-800">
                    @if($classRank) #{{ $classRank }} of {{ $classTotalStudents }} @else N/A @endif
                </p>
                <p class="text-xs text-slate-400">in {{ $student->studentProfile->section ?? 'their section' }}</p>
            </div>
        </div>
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow-sm flex items-center gap-4">
            <div class="text-4xl">🌐</div>
            <div>
                <p class="text-xs text-slate-500 uppercase tracking-wide">System Rank</p>
                <p class="text-xl font-bold text-slate-800">
                    @if($systemRank) #{{ $systemRank }} of {{ $systemTotalStudents }} @else N/A @endif
                </p>
                <p class="text-xs text-slate-400">among all students</p>
            </div>
        </div>
    </div>

    {{-- ===== CHARTS ROW ===== --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Score Trend --}}
        <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-800 text-lg mb-1">📈 Score Trend Over Time</h2>
            <p class="text-xs text-slate-500 mb-5">Each quiz attempt plotted chronologically</p>
            <div class="h-64">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        {{-- Radar by category --}}
        <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-800 text-lg mb-1">🎯 Performance by Subject / Class</h2>
            <p class="text-xs text-slate-500 mb-5">Average score per class enrolled in</p>
            @if($classPerformance->isEmpty())
                @include('admin.analytics.partials.empty-state', ['title' => 'No class performance yet', 'message' => 'This chart appears after the student completes quizzes assigned to their classes.'])
            @else
                <div class="h-64">
                    <canvas id="radarChart"></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== ALL ATTEMPTS TABLE ===== --}}
    <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-lg">📋 All Quiz Attempts</h2>
            <p class="text-xs text-slate-500 mt-0.5">Complete attempt history for this student</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Quiz</th>
                        <th class="px-4 py-3 text-left">Class</th>
                        <th class="px-4 py-3 text-center">Score</th>
                        <th class="px-4 py-3 text-center">Percentage</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Date</th>
                        <th class="px-4 py-3 text-center">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attempts as $attempt)
                    @php $pct = $attempt->total_points > 0 ? ($attempt->score / $attempt->total_points) * 100 : 0; @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ $attempt->quiz->title ?? 'Deleted Quiz' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            {{ $attempt->quiz?->classes->first()?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-slate-700">
                            {{ $attempt->score }} / {{ $attempt->total_points }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold {{ $pct > 75 ? 'text-emerald-600' : ($pct > 40 ? 'text-amber-600' : 'text-red-600') }}">
                                {{ number_format($pct, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($pct >= 60)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Passed</span>
                            @elseif($attempt->completed_at)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Failed</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">{{ ucfirst($attempt->status) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-slate-500 text-xs">
                            {{ $attempt->completed_at ? \Carbon\Carbon::parse($attempt->completed_at)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-slate-500 text-xs">
                            @if($attempt->started_at && $attempt->completed_at)
                                @php
                                    $seconds = \Carbon\Carbon::parse($attempt->started_at)->diffInSeconds($attempt->completed_at);
                                    $hours = intdiv($seconds, 3600);
                                    $minutes = intdiv($seconds % 3600, 60);
                                    $remainingSeconds = $seconds % 60;
                                @endphp
                                @if($hours > 0)
                                    {{ $hours }}h {{ $minutes }}m
                                @elseif($minutes > 0)
                                    {{ $minutes }}m {{ $remainingSeconds }}s
                                @else
                                    {{ $remainingSeconds }}s
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No attempts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($attempts, 'hasPages') && $attempts->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $attempts->links() }}
        </div>
        @endif
    </div>

    {{-- ===== WEAK AREAS ===== --}}
    @if(count($weakAreas) > 0)
    <div class="rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-lg">🔍 Frequently Missed Questions</h2>
            <p class="text-xs text-slate-500 mt-0.5">Questions this student has answered incorrectly most often</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Question</th>
                        <th class="px-4 py-3 text-center">Quiz</th>
                        <th class="px-4 py-3 text-center">Times Wrong</th>
                        <th class="px-4 py-3 text-center">Times Seen</th>
                        <th class="px-4 py-3 text-center">Error Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($weakAreas as $item)
                    <tr class="hover:bg-red-50 transition">
                        <td class="px-4 py-3 text-slate-700 max-w-xs">
                            <p class="truncate">{{ $item->question_text }}</p>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-500 text-xs">{{ $item->quiz_title }}</td>
                        <td class="px-4 py-3 text-center font-bold text-red-600">{{ $item->wrong_count }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $item->total_seen }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $rate = $item->total_seen > 0 ? ($item->wrong_count / $item->total_seen) * 100 : 0; @endphp
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                    <div class="bg-red-500 h-1.5 rounded-full" style="width:{{ min($rate,100) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-red-600">{{ number_format($rate,0) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ── Score Trend ───────────────────────────────────────────────
const trendLabels = @json($trendLabels);
const trendData   = @json($trendData);

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: 'Score %',
            data: trendData,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            pointBackgroundColor: trendData.map(v => v > 75 ? '#10b981' : v > 40 ? '#f59e0b' : '#ef4444'),
            pointRadius: 5,
            fill: true,
            tension: 0.3,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { callback: v => v + '%' } },
            x: { grid: { display: false }, ticks: { maxRotation: 35, font: { size: 10 } } }
        }
    }
});

// ── Radar by Class ────────────────────────────────────────────
const radarLabels = @json($classPerformance->pluck('class_name'));
const radarData   = @json($classPerformance->pluck('avg_score')->map(fn($v) => round($v, 1)));
const radarEl = document.getElementById('radarChart');
if (radarEl) {
new Chart(radarEl, {
    type: 'radar',
    data: {
        labels: radarLabels,
        datasets: [{
            label: 'Avg Score %',
            data: radarData,
            backgroundColor: 'rgba(99,102,241,0.2)',
            borderColor: 'rgba(99,102,241,0.9)',
            pointBackgroundColor: '#6366f1',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            r: {
                beginAtZero: true, max: 100,
                grid: { color: '#e2e8f0' },
                ticks: { stepSize: 25, callback: v => v + '%', font: { size: 10 } }
            }
        }
    }
});
}
</script>
@endsection
