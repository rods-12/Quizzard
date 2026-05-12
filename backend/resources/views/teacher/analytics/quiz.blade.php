@extends('teacher.layouts.app')

@section('content')
<div class="space-y-8">

    {{-- ============================================================ --}}
    {{-- QUIZ METADATA HEADER                                         --}}
    {{-- ============================================================ --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-green-700 via-green-600 to-emerald-600 px-6 py-8 text-white shadow-lg sm:px-10 sm:py-10">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-10 h-40 w-40 rounded-full bg-emerald-400/20 blur-2xl"></div>
        </div>

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-900/40 px-3 py-1.5 text-xs font-medium uppercase tracking-widest text-emerald-100 backdrop-blur-md">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Quiz Analytics
                </div>
                <h2 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ $quiz->title }}
                </h2>
                @if ($quiz->description)
                    <p class="mt-3 max-w-2xl text-base leading-relaxed text-emerald-100">
                        {{ $quiz->description }}
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap gap-3 text-sm text-emerald-100">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-900/40 px-3 py-1 text-xs font-medium backdrop-blur-md border border-emerald-400/20">
                        {{ $questionCount }} {{ Str::plural('Question', $questionCount) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-900/40 px-3 py-1 text-xs font-medium backdrop-blur-md border border-emerald-400/20">
                        {{ $totalPoints }} Total Points
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-900/40 px-3 py-1 text-xs font-medium backdrop-blur-md border border-emerald-400/20">
                        Created {{ $quiz->created_at->format('M d, Y') }}
                    </span>
                    @if ($quiz->is_published)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-300/20 px-3 py-1 text-xs font-semibold text-green-100 border border-green-300/30">
                            ● Published
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-300/20 px-3 py-1 text-xs font-semibold text-amber-100 border border-amber-300/30">
                            ● Unpublished
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('teacher.reports.quizzes') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-white/20 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-white/30 border border-white/20">
                    ← Back to Quizzes
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- A. SUMMARY CARDS                                             --}}
    {{-- ============================================================ --}}
    <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
        <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
            <h3 class="text-lg font-bold text-gray-900">Performance Summary</h3>
            <p class="mt-1 text-sm text-gray-500">Based on {{ $totalAttempts }} reviewed {{ Str::plural('attempt', $totalAttempts) }}. Only reviewed attempts are counted.</p>
        </div>

        @if ($totalAttempts === 0)
            <div class="px-6 py-16 sm:px-8 text-center">
                <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2a4 4 0 014-4h0a4 4 0 014 4v2M7 17v-2a6 6 0 016-6h0a6 6 0 016 6v2M3 21h18"/>
                </svg>
                <p class="text-sm font-medium text-gray-400">No reviewed attempts yet.</p>
                <p class="mt-1 text-xs text-gray-300">Summary will appear once at least one attempt has been graded.</p>
            </div>
        @else
            <div class="grid grid-cols-2 gap-px bg-emerald-100 sm:grid-cols-3 lg:grid-cols-7">
                @php
                    $cards = [
                        ['label' => 'Total Attempts',  'value' => $totalAttempts,                            'suffix' => ''],
                        ['label' => 'Avg Score',        'value' => number_format($avgScore, 2),               'suffix' => ' pts'],
                        ['label' => 'Avg Percentage',   'value' => number_format($avgPct, 2),                 'suffix' => '%'],
                        ['label' => 'Highest Score',    'value' => number_format($summary->highest_score, 2), 'suffix' => ' pts'],
                        ['label' => 'Lowest Score',     'value' => number_format($summary->lowest_score, 2),  'suffix' => ' pts'],
                        ['label' => 'Pass Rate',        'value' => number_format($passRate, 2),               'suffix' => '%', 'color' => 'text-emerald-600'],
                        ['label' => 'Fail Rate',        'value' => number_format($failRate, 2),               'suffix' => '%', 'color' => 'text-red-500'],
                    ];
                @endphp

                @foreach ($cards as $card)
                    <div class="bg-white px-5 py-6 text-center">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold {{ $card['color'] ?? 'text-gray-900' }}">
                            {{ $card['value'] }}<span class="text-base font-medium text-gray-400">{{ $card['suffix'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- B & C. TOP / LOWEST PERFORMING STUDENTS                      --}}
    {{-- ============================================================ --}}
    @if ($totalAttempts > 0)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Top 10 --}}
            <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
                <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
                    <h3 class="text-lg font-bold text-gray-900">Top 10 Performers</h3>
                    <p class="mt-1 text-sm text-gray-500">Highest scoring reviewed attempts.</p>
                </div>
                @if ($topPerformers->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-400">No performer data available.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table id="topTable" class="min-w-full divide-y divide-emerald-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    @foreach (['Rank', 'Student', 'Score', 'Percentage', 'Reviewed'] as $i => $col)
                                        <th onclick="sortTable('topTable', {{ $i }})"
                                            class="cursor-pointer whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                            {{ $col }} <span class="sort-icon">↕</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-50 bg-white">
                                @foreach ($topPerformers as $i => $row)
                                    <tr class="transition-colors duration-150 hover:bg-emerald-50/50">
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-500">
                                            {{ $i + 1 }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                            {{ $row->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                            {{ $row->score }} / {{ $row->total_points }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-emerald-600"
                                            data-value="{{ $row->percentage }}">
                                            {{ number_format($row->percentage, 2) }}%
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                            {{ $row->reviewed_at ? \Carbon\Carbon::parse($row->reviewed_at)->format('M d, Y') : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Bottom 10 --}}
            <div class="overflow-hidden rounded-[2rem] border border-red-100 bg-white shadow-sm ring-1 ring-red-900/5">
                <div class="border-b border-red-100 bg-red-50/50 px-6 py-5 sm:px-8">
                    <h3 class="text-lg font-bold text-gray-900">Lowest 10 Performers</h3>
                    <p class="mt-1 text-sm text-gray-500">Students who may need additional support.</p>
                </div>
                @if ($lowestPerformers->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-400">No performer data available.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table id="bottomTable" class="min-w-full divide-y divide-red-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    @foreach (['Rank', 'Student', 'Score', 'Percentage', 'Reviewed'] as $i => $col)
                                        <th onclick="sortTable('bottomTable', {{ $i }})"
                                            class="cursor-pointer whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                            {{ $col }} <span class="sort-icon">↕</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-50 bg-white">
                                @foreach ($lowestPerformers as $i => $row)
                                    <tr class="transition-colors duration-150 hover:bg-red-50/50">
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-500">
                                            {{ $i + 1 }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                            {{ $row->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                            {{ $row->score }} / {{ $row->total_points }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-red-500"
                                            data-value="{{ $row->percentage }}">
                                            {{ number_format($row->percentage, 2) }}%
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                            {{ $row->reviewed_at ? \Carbon\Carbon::parse($row->reviewed_at)->format('M d, Y') : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- D & E. CHARTS: SCORE DISTRIBUTION + PASS VS FAIL             --}}
    {{-- ============================================================ --}}
    @if ($totalAttempts > 0)
        @php
            $allZero = array_sum(array_values($distribution)) === 0;
        @endphp
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- D: Score Distribution Bar --}}
            <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
                <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
                    <h3 class="text-lg font-bold text-gray-900">Score Distribution</h3>
                    <p class="mt-1 text-sm text-gray-500">Number of students per score range (%).</p>
                </div>
                <div class="px-6 py-6 sm:px-8">
                    @if ($allZero)
                        <p class="py-8 text-center text-sm text-gray-400">No distribution data to display.</p>
                    @else
                        <canvas id="distributionChart" height="220"></canvas>
                    @endif
                </div>
            </div>

            {{-- E: Pass vs Fail Doughnut --}}
            <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
                <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
                    <h3 class="text-lg font-bold text-gray-900">Pass vs Fail</h3>
                    <p class="mt-1 text-sm text-gray-500">Based on 60% passing threshold.</p>
                </div>
                <div class="flex items-center justify-center px-6 py-6 sm:px-8">
                    @if ((int) $summary->passed_count === 0 && (int) $summary->failed_count === 0)
                        <p class="py-8 text-center text-sm text-gray-400">No pass/fail data to display.</p>
                    @else
                        <div style="max-width: 280px; width: 100%;">
                            <canvas id="passFailChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- F. QUESTION ANALYTICS                                        --}}
    {{-- ============================================================ --}}
    <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
        <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
            <h3 class="text-lg font-bold text-gray-900">Question Analytics</h3>
            <p class="mt-1 text-sm text-gray-500">
                Correct rate, difficulty, and average earned points per question.
                Only reviewed attempts are counted.
            </p>
        </div>

        @if ($questionCount === 0)
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-400">This quiz has no questions yet.</p>
            </div>
        @elseif ($questions->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="text-sm text-gray-400">No questions found for this quiz.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table id="questionsTable" class="min-w-full divide-y divide-emerald-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th onclick="sortTable('questionsTable', 0)"
                                class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100 sm:px-8">
                                # <span class="sort-icon">↕</span>
                            </th>
                            <th class="whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Question
                            </th>
                            <th onclick="sortTable('questionsTable', 2)"
                                class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                Type <span class="sort-icon">↕</span>
                            </th>
                            <th onclick="sortTable('questionsTable', 3)"
                                class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                Correct Rate <span class="sort-icon">↕</span>
                            </th>
                            <th onclick="sortTable('questionsTable', 4)"
                                class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                Incorrect Rate <span class="sort-icon">↕</span>
                            </th>
                            <th onclick="sortTable('questionsTable', 5)"
                                class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                Avg Points <span class="sort-icon">↕</span>
                            </th>
                            <th onclick="sortTable('questionsTable', 6)"
                                class="cursor-pointer whitespace-nowrap px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                Difficulty <span class="sort-icon">↕</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-50 bg-white">
                        @foreach ($questions as $i => $q)
                            <tr class="transition-colors duration-150 hover:bg-emerald-50/50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 sm:px-8">
                                    {{ $i + 1 }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ Str::limit($q->question_text, 100) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ ucfirst(str_replace('_', ' ', $q->question_type)) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-emerald-600"
                                    data-value="{{ $q->correct_rate ?? -1 }}">
                                    {{ $q->correct_rate !== null ? number_format($q->correct_rate, 2).'%' : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-red-500"
                                    data-value="{{ $q->incorrect_rate ?? -1 }}">
                                    {{ $q->incorrect_rate !== null ? number_format($q->incorrect_rate, 2).'%' : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600"
                                    data-value="{{ $q->avg_points ?? -1 }}">
                                    {{ $q->avg_points !== null ? number_format($q->avg_points, 2).' / '.$q->points : 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @php
                                        $badge = match($q->difficulty) {
                                            'Easy'     => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
                                            'Moderate' => 'bg-amber-100 text-amber-700 ring-amber-500/20',
                                            'Hard'     => 'bg-red-100 text-red-700 ring-red-600/20',
                                            default    => 'bg-gray-100 text-gray-500 ring-gray-400/20',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $badge }}">
                                        {{ $q->difficulty }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@push('charts')
<script>
    // D: Score Distribution Bar Chart
    @if ($totalAttempts > 0 && array_sum(array_values($distribution)) > 0)
    (function () {
        const ctx = document.getElementById('distributionChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($distribution)) !!},
                datasets: [{
                    label: 'Students',
                    data: {!! json_encode(array_values($distribution)) !!},
                    backgroundColor: [
                        'rgba(239,68,68,0.7)',
                        'rgba(249,115,22,0.7)',
                        'rgba(234,179,8,0.7)',
                        'rgba(34,197,94,0.7)',
                        'rgba(16,185,129,0.7)',
                    ],
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
    @endif

    // E: Pass vs Fail Doughnut
    @if ($totalAttempts > 0 && ((int) $summary->passed_count > 0 || (int) $summary->failed_count > 0))
    (function () {
        const ctx = document.getElementById('passFailChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{
                    data: [{{ (int) $summary->passed_count }}, {{ (int) $summary->failed_count }}],
                    backgroundColor: ['rgba(16,185,129,0.8)', 'rgba(239,68,68,0.8)'],
                    borderWidth: 2,
                    borderColor: ['#10b981', '#ef4444'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} (${((ctx.parsed / {{ $totalAttempts }}) * 100).toFixed(1)}%)`
                        }
                    }
                }
            }
        });
    })();
    @endif

    // Sort utility
    const sortState = {};
    function sortTable(tableId, colIndex) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const tbody = table.querySelector('tbody');
        const rows  = Array.from(tbody.querySelectorAll('tr'));
        const icons = table.querySelectorAll('.sort-icon');

        if (!sortState[tableId]) sortState[tableId] = {};
        const asc = !sortState[tableId][colIndex];
        sortState[tableId][colIndex] = asc;

        icons.forEach(ic => ic.textContent = '↕');
        icons[colIndex].textContent = asc ? '↑' : '↓';

        rows.sort((a, b) => {
            const aCell = a.querySelectorAll('td')[colIndex];
            const bCell = b.querySelectorAll('td')[colIndex];
            const aRaw  = (aCell.dataset.value ?? aCell.innerText).trim();
            const bRaw  = (bCell.dataset.value ?? bCell.innerText).trim();

            if (!aRaw && !bRaw) return 0;
            if (!aRaw) return 1;
            if (!bRaw) return -1;

            const aNum = parseFloat(aRaw), bNum = parseFloat(bRaw);
            if (!isNaN(aNum) && !isNaN(bNum)) return asc ? aNum - bNum : bNum - aNum;
            return asc ? aRaw.localeCompare(bRaw) : bRaw.localeCompare(aRaw);
        });

        rows.forEach(r => tbody.appendChild(r));
    }
</script>
@endpush

@endsection
