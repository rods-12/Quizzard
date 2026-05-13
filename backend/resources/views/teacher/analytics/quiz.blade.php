@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <a href="{{ route('teacher.reports.quizzes') }}"
           style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); text-decoration:none; margin-bottom:14px;"
           onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color='var(--text-3)'">
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.56l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 111.06 1.06L5.56 9.25h10.69A.75.75 0 0117 10z" clip-rule="evenodd" />
            </svg>
            Back to Quizzes
        </a>
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Quiz Analytics</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">{{ $quiz->title }}</h1>
        @if ($quiz->description)
            <p style="font-size:13px; color:var(--text-2);">{{ $quiz->description }}</p>
        @else
            <p style="font-size:13px; color:var(--text-2);">Analytics and performance data for this quiz.</p>
        @endif

        {{-- Meta chips --}}
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
            <span class="chip num">{{ $questionCount }} {{ Str::plural('Question', $questionCount) }}</span>
            <span class="chip num">{{ $totalPoints }} Total Points</span>
            <span class="chip">Created {{ $quiz->created_at->format('M d, Y') }}</span>
            @if ($quiz->is_published)
                <span class="badge badge-green">Published</span>
            @else
                <span class="badge badge-amber">Unpublished</span>
            @endif
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- A. PERFORMANCE SUMMARY                                       --}}
    {{-- ============================================================ --}}
    <div class="card" style="overflow:hidden; margin-bottom:16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Overview</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Performance Summary</h2>
                <p style="font-size:12px; color:var(--text-2);">Based on <span class="num">{{ $totalAttempts }}</span> reviewed {{ Str::plural('attempt', $totalAttempts) }}. Only reviewed attempts are counted.</p>
            </div>
        </div>

        @if ($totalAttempts === 0)
            <div style="padding:64px 24px; text-align:center;">
                <div style="display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:14px; background:var(--accent-bg); margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 17v-2a4 4 0 014-4h0a4 4 0 014 4v2M7 17v-2a6 6 0 016-6h0a6 6 0 016 6v2M3 21h18"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No reviewed attempts yet</p>
                <p style="font-size:12px; color:var(--text-2);">Summary will appear once at least one attempt has been graded.</p>
            </div>
        @else
            @php
                $cards = [
                    ['label' => 'Total Attempts', 'value' => $totalAttempts,                            'suffix' => '',     'color' => 'var(--text)'],
                    ['label' => 'Avg Score',       'value' => number_format($avgScore, 2),               'suffix' => ' pts', 'color' => 'var(--text)'],
                    ['label' => 'Avg Percentage',  'value' => number_format($avgPct, 2),                 'suffix' => '%',    'color' => 'var(--text)'],
                    ['label' => 'Highest Score',   'value' => number_format($summary->highest_score, 2), 'suffix' => ' pts', 'color' => 'var(--accent)'],
                    ['label' => 'Lowest Score',    'value' => number_format($summary->lowest_score, 2),  'suffix' => ' pts', 'color' => 'var(--danger)'],
                    ['label' => 'Pass Rate',       'value' => number_format($passRate, 2),               'suffix' => '%',    'color' => 'var(--accent)'],
                    ['label' => 'Fail Rate',       'value' => number_format($failRate, 2),               'suffix' => '%',    'color' => 'var(--danger)'],
                ];
            @endphp
            <div class="summary-grid">
                @foreach ($cards as $card)
                    <div style="padding:20px 18px; text-align:center; border-right:1px solid var(--border); border-bottom:1px solid var(--border);">
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:8px;">{{ $card['label'] }}</p>
                        <p style="font-size:22px; font-weight:700; color:{{ $card['color'] }}; line-height:1;" class="num">
                            {{ $card['value'] }}<span style="font-size:13px; font-weight:500; color:var(--text-3);">{{ $card['suffix'] }}</span>
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
        <div class="performers-grid" style="margin-bottom:16px;">

            {{-- Top 10 --}}
            <div class="card" style="overflow:hidden;">
                <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                    <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Rankings</p>
                    <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Top 10 Performers</h2>
                    <p style="font-size:12px; color:var(--text-2);">Highest scoring reviewed attempts.</p>
                </div>
                @if ($topPerformers->isEmpty())
                    <div style="padding:40px 24px; text-align:center;">
                        <p style="font-size:13px; color:var(--text-3);">No performer data available.</p>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table id="topTable" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--border);">
                                    @foreach (['Rank', 'Student', 'Score', 'Percentage', 'Reviewed'] as $i => $col)
                                        <th onclick="sortTable('topTable', {{ $i }})"
                                            style="padding:10px {{ $i === 0 ? '22px' : '14px' }}; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                            onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                            {{ $col }} <span class="sort-icon" style="font-size:9px; margin-left:2px;">↕</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topPerformers as $i => $row)
                                    <tr class="divider"
                                        onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                        onmouseleave="this.style.background='transparent'">
                                        <td style="padding:11px 22px; white-space:nowrap;">
                                            <span class="num" style="font-size:12px; font-weight:700; color:var(--accent);">{{ $i + 1 }}</span>
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap; font-size:13px; font-weight:600; color:var(--text);">
                                            {{ $row->name }}
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap;" data-value="{{ $row->score }}">
                                            <span class="num" style="font-size:13px; color:var(--text);">{{ $row->score }}</span>
                                            <span style="font-size:12px; color:var(--text-3);">/ {{ $row->total_points }}</span>
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap;" data-value="{{ $row->percentage }}">
                                            <span class="badge badge-green num">{{ number_format($row->percentage, 2) }}%</span>
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap; font-size:12px; color:var(--text-3);">
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
            <div class="card" style="overflow:hidden;">
                <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                    <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--danger); margin-bottom:4px;">Needs Support</p>
                    <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Lowest 10 Performers</h2>
                    <p style="font-size:12px; color:var(--text-2);">Students who may need additional support.</p>
                </div>
                @if ($lowestPerformers->isEmpty())
                    <div style="padding:40px 24px; text-align:center;">
                        <p style="font-size:13px; color:var(--text-3);">No performer data available.</p>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table id="bottomTable" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--border);">
                                    @foreach (['Rank', 'Student', 'Score', 'Percentage', 'Reviewed'] as $i => $col)
                                        <th onclick="sortTable('bottomTable', {{ $i }})"
                                            style="padding:10px {{ $i === 0 ? '22px' : '14px' }}; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                            onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                            {{ $col }} <span class="sort-icon" style="font-size:9px; margin-left:2px;">↕</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lowestPerformers as $i => $row)
                                    <tr class="divider"
                                        onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                        onmouseleave="this.style.background='transparent'">
                                        <td style="padding:11px 22px; white-space:nowrap;">
                                            <span class="num" style="font-size:12px; font-weight:700; color:var(--danger);">{{ $i + 1 }}</span>
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap; font-size:13px; font-weight:600; color:var(--text);">
                                            {{ $row->name }}
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap;" data-value="{{ $row->score }}">
                                            <span class="num" style="font-size:13px; color:var(--text);">{{ $row->score }}</span>
                                            <span style="font-size:12px; color:var(--text-3);">/ {{ $row->total_points }}</span>
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap;" data-value="{{ $row->percentage }}">
                                            <span class="badge badge-rose num">{{ number_format($row->percentage, 2) }}%</span>
                                        </td>
                                        <td style="padding:11px 14px; white-space:nowrap; font-size:12px; color:var(--text-3);">
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
        @php $allZero = array_sum(array_values($distribution)) === 0; @endphp
        <div class="performers-grid" style="margin-bottom:16px;">

            {{-- D: Score Distribution --}}
            <div class="card" style="overflow:hidden;">
                <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                    <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Distribution</p>
                    <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Score Distribution</h2>
                    <p style="font-size:12px; color:var(--text-2);">Number of students per score range (%).</p>
                </div>
                <div style="padding:22px;">
                    @if ($allZero)
                        <div style="padding:40px 0; text-align:center;">
                            <p style="font-size:13px; color:var(--text-3);">No distribution data to display.</p>
                        </div>
                    @else
                        <canvas id="distributionChart" height="220"></canvas>
                    @endif
                </div>
            </div>

            {{-- E: Pass vs Fail --}}
            <div class="card" style="overflow:hidden;">
                <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                    <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Outcome</p>
                    <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Pass vs Fail</h2>
                    <p style="font-size:12px; color:var(--text-2);">Based on 60% passing threshold.</p>
                </div>
                <div style="display:flex; align-items:center; justify-content:center; padding:22px;">
                    @if ((int) $summary->passed_count === 0 && (int) $summary->failed_count === 0)
                        <div style="padding:40px 0; text-align:center;">
                            <p style="font-size:13px; color:var(--text-3);">No pass/fail data to display.</p>
                        </div>
                    @else
                        <div style="max-width:260px; width:100%;">
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
    <div class="card" style="overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Per Question</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Question Analytics</h2>
                <p style="font-size:12px; color:var(--text-2);">Correct rate, difficulty, and average earned points per question. Only reviewed attempts are counted.</p>
            </div>
        </div>

        @if ($questionCount === 0)
            <div style="padding:64px 24px; text-align:center;">
                <div style="display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:14px; background:var(--accent-bg); margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No questions yet</p>
                <p style="font-size:12px; color:var(--text-2);">This quiz has no questions added yet.</p>
            </div>
        @elseif ($questions->isEmpty())
            <div style="padding:64px 24px; text-align:center;">
                <p style="font-size:13px; color:var(--text-3);">No questions found for this quiz.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table id="questionsTable" style="width:100%; border-collapse:collapse; min-width:700px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th onclick="sortTable('questionsTable', 0)"
                                style="padding:11px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'"># <span class="sort-icon" style="font-size:9px;">↕</span></th>
                            <th style="padding:11px 14px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); white-space:nowrap;">Question</th>
                            <th onclick="sortTable('questionsTable', 2)"
                                style="padding:11px 14px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">Type <span class="sort-icon" style="font-size:9px;">↕</span></th>
                            <th onclick="sortTable('questionsTable', 3)"
                                style="padding:11px 14px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">Correct Rate <span class="sort-icon" style="font-size:9px;">↕</span></th>
                            <th onclick="sortTable('questionsTable', 4)"
                                style="padding:11px 14px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">Incorrect Rate <span class="sort-icon" style="font-size:9px;">↕</span></th>
                            <th onclick="sortTable('questionsTable', 5)"
                                style="padding:11px 14px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">Avg Points <span class="sort-icon" style="font-size:9px;">↕</span></th>
                            <th onclick="sortTable('questionsTable', 6)"
                                style="padding:11px 14px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">Difficulty <span class="sort-icon" style="font-size:9px;">↕</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($questions as $i => $q)
                            <tr class="divider"
                                onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                onmouseleave="this.style.background='transparent'">
                                <td style="padding:12px 22px; white-space:nowrap;">
                                    <span class="num" style="font-size:12px; color:var(--text-3);">{{ $i + 1 }}</span>
                                </td>
                                <td style="padding:12px 14px; font-size:13px; color:var(--text-2); max-width:300px;">
                                    {{ Str::limit($q->question_text, 100) }}
                                </td>
                                <td style="padding:12px 14px; white-space:nowrap;">
                                    <span class="chip">{{ ucfirst(str_replace('_', ' ', $q->question_type)) }}</span>
                                </td>
                                <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->correct_rate ?? -1 }}">
                                    @if ($q->correct_rate !== null)
                                        <span class="num" style="font-size:13px; font-weight:600; color:var(--accent);">{{ number_format($q->correct_rate, 2) }}%</span>
                                    @else
                                        <span style="font-size:12px; color:var(--text-3);">N/A</span>
                                    @endif
                                </td>
                                <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->incorrect_rate ?? -1 }}">
                                    @if ($q->incorrect_rate !== null)
                                        <span class="num" style="font-size:13px; font-weight:600; color:var(--danger);">{{ number_format($q->incorrect_rate, 2) }}%</span>
                                    @else
                                        <span style="font-size:12px; color:var(--text-3);">N/A</span>
                                    @endif
                                </td>
                                <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->avg_points ?? -1 }}">
                                    @if ($q->avg_points !== null)
                                        <span class="num" style="font-size:13px; color:var(--text);">{{ number_format($q->avg_points, 2) }}</span>
                                        <span style="font-size:12px; color:var(--text-3);">/ {{ $q->points }}</span>
                                    @else
                                        <span style="font-size:12px; color:var(--text-3);">N/A</span>
                                    @endif
                                </td>
                                <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->difficulty }}">
                                    @if ($q->difficulty === 'Easy')
                                        <span class="badge badge-green">Easy</span>
                                    @elseif ($q->difficulty === 'Moderate')
                                        <span class="badge badge-amber">Moderate</span>
                                    @elseif ($q->difficulty === 'Hard')
                                        <span class="badge badge-rose">Hard</span>
                                    @else
                                        <span class="badge badge-slate">{{ $q->difficulty }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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
                        'rgba(248,113,113,0.75)',
                        'rgba(251,191,36,0.75)',
                        'rgba(251,191,36,0.75)',
                        'rgba(74,222,128,0.75)',
                        'rgba(74,222,128,0.85)',
                    ],
                    borderColor: [
                        'rgba(248,113,113,1)',
                        'rgba(251,191,36,1)',
                        'rgba(251,191,36,1)',
                        'rgba(74,222,128,1)',
                        'rgba(74,222,128,1)',
                    ],
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0, color: 'rgba(146,152,176,0.8)' },
                        grid: { color: 'rgba(255,255,255,0.05)' },
                    },
                    x: {
                        ticks: { color: 'rgba(146,152,176,0.8)' },
                        grid: { display: false },
                    }
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
                    backgroundColor: ['rgba(74,222,128,0.8)', 'rgba(248,113,113,0.8)'],
                    borderColor: ['#4ade80', '#f87171'],
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: 'rgba(146,152,176,0.9)', padding: 16, font: { size: 12 } }
                    },
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

<style>
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }
    .performers-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    @media (max-width: 900px) {
        .summary-grid { grid-template-columns: repeat(2, 1fr); }
        .performers-grid { grid-template-columns: 1fr; }
    }
</style>

@endsection
