@extends('teacher.layouts.app')

@section('content')
<div class="space-y-8">

    {{-- ============================================================ --}}
    {{-- HERO HEADER                                                  --}}
    {{-- ============================================================ --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-green-700 via-green-600 to-emerald-600 px-6 py-8 text-white shadow-lg sm:px-10 sm:py-10">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-10 h-40 w-40 rounded-full bg-emerald-400/20 blur-2xl"></div>
        </div>
        <div class="relative">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-900/40 px-3 py-1.5 text-xs font-medium uppercase tracking-widest text-emerald-100 backdrop-blur-md">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Global Analytics
            </div>
            <h2 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                Performance Intelligence
            </h2>
            <p class="mt-3 max-w-2xl text-base leading-relaxed text-emerald-100">
                Teacher-wide analytics across all your quizzes, classes, and students.
                Only reviewed attempts are counted.
            </p>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- A & B: TOP / BOTTOM 10 QUIZZES BY PASS RATE                 --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- A: Highest Pass Rate --}}
        <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
            <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
                <h3 class="text-lg font-bold text-gray-900">Top 10 — Highest Pass Rate</h3>
                <p class="mt-1 text-sm text-gray-500">Assigned quizzes with at least 5 reviewed attempts.</p>
            </div>
            @if ($topQuizzes->isEmpty())
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">No quizzes meet the minimum threshold yet.</p>
                    <p class="mt-1 text-xs text-gray-300">Quizzes need at least 5 reviewed attempts to appear here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table id="topQuizzesTable" class="min-w-full divide-y divide-emerald-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                @foreach (['#', 'Quiz', 'Pass Rate', 'Avg %', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('topQuizzesTable', {{ $i }})"
                                        class="cursor-pointer whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                        {{ $col }} <span class="sort-icon">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-emerald-50 bg-white">
                            @foreach ($topQuizzes as $i => $q)
                                <tr class="transition-colors duration-150 hover:bg-emerald-50/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        <a href="{{ route('teacher.analytics.quiz', $q->id) }}"
                                           class="hover:text-emerald-600 hover:underline">
                                            {{ Str::limit($q->title, 40) }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3" data-value="{{ $q->pass_rate }}">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full bg-emerald-500"
                                                     style="width: {{ $q->pass_rate }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold text-emerald-600">{{ number_format($q->pass_rate, 1) }}%</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600"
                                        data-value="{{ $q->avg_pct }}">
                                        {{ number_format($q->avg_pct, 1) }}%
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                        {{ $q->total_attempts }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- B: Lowest Pass Rate --}}
        <div class="overflow-hidden rounded-[2rem] border border-red-100 bg-white shadow-sm ring-1 ring-red-900/5">
            <div class="border-b border-red-100 bg-red-50/50 px-6 py-5 sm:px-8">
                <h3 class="text-lg font-bold text-gray-900">Bottom 10 — Lowest Pass Rate</h3>
                <p class="mt-1 text-sm text-gray-500">Quizzes that may need review or additional support.</p>
            </div>
            @if ($bottomQuizzes->isEmpty())
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">No quizzes meet the minimum threshold yet.</p>
                    <p class="mt-1 text-xs text-gray-300">Quizzes need at least 5 reviewed attempts to appear here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table id="bottomQuizzesTable" class="min-w-full divide-y divide-red-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                @foreach (['#', 'Quiz', 'Pass Rate', 'Avg %', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('bottomQuizzesTable', {{ $i }})"
                                        class="cursor-pointer whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                        {{ $col }} <span class="sort-icon">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-50 bg-white">
                            @foreach ($bottomQuizzes as $i => $q)
                                <tr class="transition-colors duration-150 hover:bg-red-50/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        <a href="{{ route('teacher.analytics.quiz', $q->id) }}"
                                           class="hover:text-red-500 hover:underline">
                                            {{ Str::limit($q->title, 40) }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3" data-value="{{ $q->pass_rate }}">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full bg-red-400"
                                                     style="width: {{ $q->pass_rate }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold text-red-500">{{ number_format($q->pass_rate, 1) }}%</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600"
                                        data-value="{{ $q->avg_pct }}">
                                        {{ number_format($q->avg_pct, 1) }}%
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                        {{ $q->total_attempts }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

    {{-- ============================================================ --}}
    {{-- C. OVERALL STUDENT PERFORMANCE                               --}}
    {{-- ============================================================ --}}
    <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
        <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Overall Student Performance</h3>
                    <p class="mt-1 text-sm text-gray-500">Across all your quizzes. Filter by review date.</p>
                </div>
                <form method="GET" action="{{ route('teacher.analytics.global') }}"
                      class="flex flex-wrap items-end gap-2">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500">From</label>
                        <input type="date" name="start_date" value="{{ $startDate }}"
                               class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500">To</label>
                        <input type="date" name="end_date" value="{{ $endDate }}"
                               class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                        Filter
                    </button>
                    @if ($startDate || $endDate)
                        <a href="{{ route('teacher.analytics.global') }}"
                           class="rounded-lg border border-gray-200 px-4 py-1.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            {{-- Phase 3: validation error display for date filters --}}
            @if ($errors->has('start_date') || $errors->has('end_date'))
                <div class="mt-3 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-600 border border-red-100">
                    {{ $errors->first('start_date') ?: $errors->first('end_date') }}
                </div>
            @endif
        </div>

        @if ($overallTotal === 0)
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-400">No reviewed attempts found for the selected period.</p>
                @if ($startDate || $endDate)
                    <p class="mt-1 text-xs text-gray-300">Try widening your date range or clearing the filter.</p>
                @endif
            </div>
        @else
            @php
                // {{-- Phase 3: null-safe fail rate — $overallPassRate is guaranteed non-null here
                //      since $overallTotal > 0, but we guard anyway. --}}
                $overallFailRate = $overallPassRate !== null ? round(100 - $overallPassRate, 2) : null;
            @endphp
            <div class="grid grid-cols-2 gap-px bg-emerald-100 sm:grid-cols-4">
                @php
                    $overallCards = [
                        ['label' => 'Total Attempts', 'value' => $overallTotal,                                                           'suffix' => '',  'color' => 'text-gray-900'],
                        ['label' => 'Avg Percentage', 'value' => number_format($overallAvgPct, 2),                                        'suffix' => '%', 'color' => 'text-gray-900'],
                        ['label' => 'Pass Rate',       'value' => number_format($overallPassRate, 2),                                     'suffix' => '%', 'color' => 'text-emerald-600'],
                        ['label' => 'Fail Rate',       'value' => $overallFailRate !== null ? number_format($overallFailRate, 2) : '—',   'suffix' => $overallFailRate !== null ? '%' : '', 'color' => 'text-red-500'],
                    ];
                @endphp
                @foreach ($overallCards as $card)
                    <div class="bg-white px-5 py-6 text-center">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">
                            {{ $card['value'] }}<span class="text-base font-medium text-gray-400">{{ $card['suffix'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- D. MONTHLY PERFORMANCE TREND                                 --}}
    {{-- ============================================================ --}}
    @if ($monthlyTrend->isNotEmpty())
        <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
            <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
                <h3 class="text-lg font-bold text-gray-900">Monthly Performance Trend</h3>
                <p class="mt-1 text-sm text-gray-500">Average score percentage per month across all reviewed attempts.</p>
            </div>
            <div class="px-6 py-6 sm:px-8">
                <canvas id="monthlyTrendChart" height="100"></canvas>
            </div>
        </div>
    @else
        {{-- Only show this empty state when an active filter is set;
             if no filter and no data at all, the overall stats empty state above is sufficient. --}}
        @if ($startDate || $endDate)
            <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
                <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
                    <h3 class="text-lg font-bold text-gray-900">Monthly Performance Trend</h3>
                </div>
                <div class="px-6 py-12 text-center">
                    <p class="text-sm text-gray-400">No monthly data for the selected date range.</p>
                </div>
            </div>
        @endif
    @endif

    {{-- ============================================================ --}}
    {{-- E. STUDENT PERFORMANCE TIMELINE                              --}}
    {{-- ============================================================ --}}
    <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
        <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
            <h3 class="text-lg font-bold text-gray-900">Student Performance Timeline</h3>
            <p class="mt-1 text-sm text-gray-500">Track an individual student's quiz scores over time.</p>
        </div>
        <div class="px-6 py-6 sm:px-8 space-y-6">
            <form method="GET" action="{{ route('teacher.analytics.global') }}"
                  class="flex flex-wrap items-end gap-3">
                {{-- Preserve C filters --}}
                @if ($startDate) <input type="hidden" name="start_date" value="{{ $startDate }}"> @endif
                @if ($endDate)   <input type="hidden" name="end_date"   value="{{ $endDate }}">   @endif

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Student</label>
                    <select name="student_id"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 min-w-[200px]">
                        <option value="">— Select student —</option>
                        @foreach ($studentList as $s)
                            <option value="{{ $s->id }}" @selected($selectedStudent == $s->id)>
                                {{ $s->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">Class (optional)</label>
                    <select name="class_id"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 min-w-[160px]">
                        <option value="">All classes</option>
                        @foreach ($classList as $c)
                            <option value="{{ $c->id }}" @selected($selectedClass == $c->id)>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">From</label>
                    <input type="date" name="tl_start" value="{{ $tlStart }}"
                           class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500">To</label>
                    <input type="date" name="tl_end" value="{{ $tlEnd }}"
                           class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                    View
                </button>
                @if ($selectedStudent)
                    <a href="{{ route('teacher.analytics.global') }}"
                       class="rounded-lg border border-gray-200 px-4 py-1.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                        Clear
                    </a>
                @endif
            </form>

            {{-- Phase 3: validation error display for timeline date filters --}}
            @if ($errors->has('tl_start') || $errors->has('tl_end'))
                <div class="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-600 border border-red-100">
                    {{ $errors->first('tl_start') ?: $errors->first('tl_end') }}
                </div>
            @endif

            @if (!$selectedStudent)
                <div class="rounded-2xl bg-gray-50 px-6 py-12 text-center border border-dashed border-gray-200">
                    <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">No student selected.</p>
                    <p class="mt-1 text-xs text-gray-300">Choose a student from the dropdown above to view their performance timeline.</p>
                </div>
            @elseif ($studentList->isEmpty())
                <div class="rounded-2xl bg-gray-50 px-6 py-12 text-center border border-dashed border-gray-200">
                    <p class="text-sm text-gray-400">No students have completed any reviewed attempts on your quizzes yet.</p>
                </div>
            @elseif ($timelineData->isEmpty())
                <div class="rounded-2xl bg-gray-50 px-6 py-12 text-center border border-dashed border-gray-200">
                    <p class="text-sm font-medium text-gray-400">No reviewed attempts found for this student.</p>
                    @if ($selectedClass || $tlStart || $tlEnd)
                        <p class="mt-1 text-xs text-gray-300">Try removing the class or date filters.</p>
                    @endif
                </div>
            @else
                <canvas id="timelineChart" height="100"></canvas>
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- F & G: AT-RISK + STRONGEST STUDENTS                         --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- F: At-Risk --}}
        <div class="overflow-hidden rounded-[2rem] border border-red-100 bg-white shadow-sm ring-1 ring-red-900/5">
            <div class="border-b border-red-100 bg-red-50/50 px-6 py-5 sm:px-8">
                <h3 class="text-lg font-bold text-gray-900">At-Risk Students</h3>
                <p class="mt-1 text-sm text-gray-500">Avg below 50% or 2+ failed attempts.</p>
            </div>
            @if ($atRisk->isEmpty())
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">No at-risk students found.</p>
                    <p class="mt-1 text-xs text-gray-300">All students are meeting the performance threshold.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table id="atRiskTable" class="min-w-full divide-y divide-red-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                @foreach (['Student', 'Avg %', 'Fails', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('atRiskTable', {{ $i }})"
                                        class="cursor-pointer whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                        {{ $col }} <span class="sort-icon">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-50 bg-white">
                            @foreach ($atRisk as $s)
                                <tr class="transition-colors duration-150 hover:bg-red-50/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $s->full_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3" data-value="{{ $s->avg_pct }}">
                                        <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">
                                            {{ number_format($s->avg_pct, 1) }}%
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600"
                                        data-value="{{ $s->fail_count }}">
                                        {{ $s->fail_count }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                        {{ $s->total_attempts }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- G: Strongest --}}
        <div class="overflow-hidden rounded-[2rem] border border-emerald-100 bg-white shadow-sm ring-1 ring-emerald-900/5">
            <div class="border-b border-emerald-100 bg-emerald-50/50 px-6 py-5 sm:px-8">
                <h3 class="text-lg font-bold text-gray-900">Strongest Students</h3>
                <p class="mt-1 text-sm text-gray-500">Avg 80%+ with at least 2 reviewed attempts.</p>
            </div>
            @if ($strongest->isEmpty())
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto mb-4 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">No students meet the criteria yet.</p>
                    <p class="mt-1 text-xs text-gray-300">Students need an 80%+ average across at least 2 reviewed attempts.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table id="strongestTable" class="min-w-full divide-y divide-emerald-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                @foreach (['Student', 'Avg %', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('strongestTable', {{ $i }})"
                                        class="cursor-pointer whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 hover:bg-slate-100">
                                        {{ $col }} <span class="sort-icon">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-emerald-50 bg-white">
                            @foreach ($strongest as $s)
                                <tr class="transition-colors duration-150 hover:bg-emerald-50/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $s->full_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3" data-value="{{ $s->avg_pct }}">
                                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            {{ number_format($s->avg_pct, 1) }}%
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                        {{ $s->total_attempts }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

</div>

@push('charts')
<script>
    // D: Monthly trend line chart
    @if ($monthlyTrend->isNotEmpty())
    (function () {
        const ctx = document.getElementById('monthlyTrendChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlyTrend->pluck('month')) !!},
                datasets: [{
                    label: 'Avg %',
                    data: {!! json_encode($monthlyTrend->pluck('avg_pct')->map(fn($v) => round($v, 2))) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
    @endif

    // E: Student timeline line chart
    @if ($selectedStudent && $timelineData->isNotEmpty())
    (function () {
        const ctx = document.getElementById('timelineChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($timelineData->pluck('reviewed_date')) !!},
                datasets: [{
                    label: 'Score %',
                    data: {!! json_encode($timelineData->pluck('pct')) !!},
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 5,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: items => {!! json_encode($timelineData->pluck('title')) !!}[items[0].dataIndex],
                            label: item => ` ${item.parsed.y}%`
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
                    x: { grid: { display: false } }
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
