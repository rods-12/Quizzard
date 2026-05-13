@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Global Analytics</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">Performance Intelligence</h1>
        <p style="font-size:13px; color:var(--text-2);">Teacher-wide analytics across all your quizzes, classes, and students. Only reviewed attempts are counted.</p>
    </div>

    {{-- ============================================================ --}}
    {{-- A & B: TOP / BOTTOM 10 QUIZZES BY PASS RATE                 --}}
    {{-- ============================================================ --}}
    <div class="analytics-grid" style="display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:16px;">

        {{-- A: Highest Pass Rate --}}
        <div class="card" style="overflow:hidden;">
            <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Quiz Rankings</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Top 10 — Highest Pass Rate</h2>
                <p style="font-size:12px; color:var(--text-2);">Assigned quizzes with at least 5 reviewed attempts.</p>
            </div>
            @if($topQuizzes->isEmpty())
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center;">
                    <div style="width:44px; height:44px; border-radius:50%; background:var(--accent-bg); display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No quizzes meet the threshold yet.</p>
                    <p style="font-size:12px; color:var(--text-3); max-width:240px;">Quizzes need at least 5 reviewed attempts to appear here.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table id="topQuizzesTable" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border);">
                                @foreach(['#', 'Quiz', 'Pass Rate', 'Avg %', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('topQuizzesTable', {{ $i }})"
                                        style="padding:10px {{ $loop->first || $loop->last ? '22px' : '14px' }}; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                        onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                        {{ $col }} <span class="sort-icon" style="opacity:0.5;">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topQuizzes as $i => $q)
                                <tr class="divider"
                                    onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                    onmouseleave="this.style.background='transparent'">
                                    <td style="padding:12px 22px; font-size:12px; color:var(--text-3); white-space:nowrap;">
                                        <span class="num">{{ $i + 1 }}</span>
                                    </td>
                                    <td style="padding:12px 14px; font-size:13px; font-weight:600; color:var(--text);">
                                        <a href="{{ route('teacher.analytics.quiz', $q->id) }}"
                                           style="color:var(--text); text-decoration:none;"
                                           onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color='var(--text)'">
                                            {{ Str::limit($q->title, 40) }}
                                        </a>
                                    </td>
                                    <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->pass_rate }}">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div style="width:72px; height:5px; border-radius:99px; background:var(--surface-3); overflow:hidden; flex-shrink:0;">
                                                <div style="height:5px; border-radius:99px; background:var(--accent); width:{{ $q->pass_rate }}%;"></div>
                                            </div>
                                            <span class="num score-high" style="font-size:11px;">{{ number_format($q->pass_rate, 1) }}%</span>
                                        </div>
                                    </td>
                                    <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->avg_pct }}">
                                        @php $avg = $q->avg_pct; @endphp
                                        <span class="num {{ $avg >= 75 ? 'score-high' : ($avg >= 50 ? 'score-mid' : 'score-low') }}" style="font-size:12px;">
                                            {{ number_format($avg, 1) }}%
                                        </span>
                                    </td>
                                    <td style="padding:12px 22px; white-space:nowrap;">
                                        <span class="num" style="font-size:13px; color:var(--text-2);">{{ $q->total_attempts }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- B: Lowest Pass Rate --}}
        <div class="card" style="overflow:hidden;">
            <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--danger); margin-bottom:4px;">Quiz Rankings</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Bottom 10 — Lowest Pass Rate</h2>
                <p style="font-size:12px; color:var(--text-2);">Quizzes that may need review or additional support.</p>
            </div>
            @if($bottomQuizzes->isEmpty())
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center;">
                    <div style="width:44px; height:44px; border-radius:50%; background:rgba(248,113,113,0.08); display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No quizzes meet the threshold yet.</p>
                    <p style="font-size:12px; color:var(--text-3); max-width:240px;">Quizzes need at least 5 reviewed attempts to appear here.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table id="bottomQuizzesTable" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border);">
                                @foreach(['#', 'Quiz', 'Pass Rate', 'Avg %', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('bottomQuizzesTable', {{ $i }})"
                                        style="padding:10px {{ $loop->first || $loop->last ? '22px' : '14px' }}; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                        onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                        {{ $col }} <span class="sort-icon" style="opacity:0.5;">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bottomQuizzes as $i => $q)
                                <tr class="divider"
                                    onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                    onmouseleave="this.style.background='transparent'">
                                    <td style="padding:12px 22px; font-size:12px; color:var(--text-3); white-space:nowrap;">
                                        <span class="num">{{ $i + 1 }}</span>
                                    </td>
                                    <td style="padding:12px 14px; font-size:13px; font-weight:600; color:var(--text);">
                                        <a href="{{ route('teacher.analytics.quiz', $q->id) }}"
                                           style="color:var(--text); text-decoration:none;"
                                           onmouseenter="this.style.color='var(--danger)'" onmouseleave="this.style.color='var(--text)'">
                                            {{ Str::limit($q->title, 40) }}
                                        </a>
                                    </td>
                                    <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->pass_rate }}">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div style="width:72px; height:5px; border-radius:99px; background:var(--surface-3); overflow:hidden; flex-shrink:0;">
                                                <div style="height:5px; border-radius:99px; background:var(--danger); width:{{ $q->pass_rate }}%;"></div>
                                            </div>
                                            <span class="num score-low" style="font-size:11px;">{{ number_format($q->pass_rate, 1) }}%</span>
                                        </div>
                                    </td>
                                    <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $q->avg_pct }}">
                                        @php $avg = $q->avg_pct; @endphp
                                        <span class="num {{ $avg >= 75 ? 'score-high' : ($avg >= 50 ? 'score-mid' : 'score-low') }}" style="font-size:12px;">
                                            {{ number_format($avg, 1) }}%
                                        </span>
                                    </td>
                                    <td style="padding:12px 22px; white-space:nowrap;">
                                        <span class="num" style="font-size:13px; color:var(--text-2);">{{ $q->total_attempts }}</span>
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
    <div class="card" style="overflow:hidden; margin-bottom:16px;">

        {{-- Card Header with Date Filter --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border); flex-wrap:wrap;">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Aggregated Results</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Overall Student Performance</h2>
                <p style="font-size:12px; color:var(--text-2);">Across all your quizzes. Filter by review date.</p>
            </div>
            <form method="GET" action="{{ route('teacher.analytics.global') }}"
                  style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px;">
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <label style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">From</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                           style="background:var(--surface-2); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 10px; font-size:12px; color:var(--text); font-family:var(--font); outline:none;">
                </div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <label style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">To</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                           style="background:var(--surface-2); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 10px; font-size:12px; color:var(--text); font-family:var(--font); outline:none;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                @if($startDate || $endDate)
                    <a href="{{ route('teacher.analytics.global') }}" class="btn btn-ghost btn-sm">Clear</a>
                @endif
            </form>
        </div>

        {{-- Validation errors --}}
        @if($errors->has('start_date') || $errors->has('end_date'))
            <div class="attention-rose" style="margin:16px 22px; border-radius:var(--radius-sm);">
                {{ $errors->first('start_date') ?: $errors->first('end_date') }}
            </div>
        @endif

        @if($overallTotal === 0)
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center;">
                <div style="width:44px; height:44px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No reviewed attempts found for the selected period.</p>
                @if($startDate || $endDate)
                    <p style="font-size:12px; color:var(--text-3);">Try widening your date range or clearing the filter.</p>
                @endif
            </div>
        @else
            @php
                $overallFailRate = $overallPassRate !== null ? round(100 - $overallPassRate, 2) : null;
                $overallCards = [
                    ['label' => 'Total Attempts', 'value' => $overallTotal,                                                          'suffix' => '',  'type' => 'neutral'],
                    ['label' => 'Avg Percentage', 'value' => number_format($overallAvgPct, 2),                                       'suffix' => '%', 'type' => 'neutral'],
                    ['label' => 'Pass Rate',       'value' => number_format($overallPassRate, 2),                                    'suffix' => '%', 'type' => 'pass'],
                    ['label' => 'Fail Rate',       'value' => $overallFailRate !== null ? number_format($overallFailRate, 2) : '—',  'suffix' => $overallFailRate !== null ? '%' : '', 'type' => 'fail'],
                ];
            @endphp
            <div style="display:grid; grid-template-columns:repeat(4,1fr);">
                @foreach($overallCards as $i => $card)
                    <div style="padding:20px 24px; text-align:center; {{ $i < 3 ? 'border-right:1px solid var(--border);' : '' }}">
                        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:8px;">{{ $card['label'] }}</p>
                        <p class="num" style="font-size:22px; font-weight:700; color:{{ $card['type'] === 'pass' ? 'var(--accent)' : ($card['type'] === 'fail' ? 'var(--danger)' : 'var(--text)') }};">
                            {{ $card['value'] }}<span style="font-size:14px; font-weight:500; color:var(--text-3);">{{ $card['suffix'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- ============================================================ --}}
    {{-- D. MONTHLY PERFORMANCE TREND                                 --}}
    {{-- ============================================================ --}}
    @if($monthlyTrend->isNotEmpty())
        <div class="card" style="overflow:hidden; margin-bottom:16px;">
            <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Trend</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Monthly Performance Trend</h2>
                <p style="font-size:12px; color:var(--text-2);">Average score percentage per month across all reviewed attempts.</p>
            </div>
            <div style="padding:24px 22px;">
                <canvas id="monthlyTrendChart" height="100"></canvas>
            </div>
        </div>
    @elseif($startDate || $endDate)
        <div class="card" style="overflow:hidden; margin-bottom:16px;">
            <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Trend</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text);">Monthly Performance Trend</h2>
            </div>
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center;">
                <p style="font-size:13px; color:var(--text-3);">No monthly data for the selected date range.</p>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- E. STUDENT PERFORMANCE TIMELINE                              --}}
    {{-- ============================================================ --}}
    <div class="card" style="overflow:hidden; margin-bottom:16px;">
        <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Individual Tracking</p>
            <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Student Performance Timeline</h2>
            <p style="font-size:12px; color:var(--text-2);">Track an individual student's quiz scores over time.</p>
        </div>
        <div style="padding:20px 22px; border-bottom:1px solid var(--border);">
            <form method="GET" action="{{ route('teacher.analytics.global') }}"
                  style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px;">
                @if($startDate) <input type="hidden" name="start_date" value="{{ $startDate }}"> @endif
                @if($endDate)   <input type="hidden" name="end_date"   value="{{ $endDate }}">   @endif

                <div style="display:flex; flex-direction:column; gap:4px;">
                    <label style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">Student</label>
                    <select name="student_id"
                            style="background:var(--surface-2); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 10px; font-size:12px; color:var(--text); font-family:var(--font); outline:none; min-width:200px;">
                        <option value="">— Select student —</option>
                        @foreach($studentList as $s)
                            <option value="{{ $s->id }}" @selected($selectedStudent == $s->id)>{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <label style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">Class (optional)</label>
                    <select name="class_id"
                            style="background:var(--surface-2); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 10px; font-size:12px; color:var(--text); font-family:var(--font); outline:none; min-width:160px;">
                        <option value="">All classes</option>
                        @foreach($classList as $c)
                            <option value="{{ $c->id }}" @selected($selectedClass == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <label style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">From</label>
                    <input type="date" name="tl_start" value="{{ $tlStart }}"
                           style="background:var(--surface-2); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 10px; font-size:12px; color:var(--text); font-family:var(--font); outline:none;">
                </div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <label style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">To</label>
                    <input type="date" name="tl_end" value="{{ $tlEnd }}"
                           style="background:var(--surface-2); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 10px; font-size:12px; color:var(--text); font-family:var(--font); outline:none;">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">View</button>
                @if($selectedStudent)
                    <a href="{{ route('teacher.analytics.global') }}" class="btn btn-ghost btn-sm">Clear</a>
                @endif
            </form>

            @if($errors->has('tl_start') || $errors->has('tl_end'))
                <div class="attention-rose" style="margin-top:12px; border-radius:var(--radius-sm);">
                    {{ $errors->first('tl_start') ?: $errors->first('tl_end') }}
                </div>
            @endif
        </div>

        <div style="padding:20px 22px;">
            @if(!$selectedStudent)
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px 24px; text-align:center; border:1px dashed var(--border-md); border-radius:var(--radius);">
                    <div style="width:44px; height:44px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No student selected.</p>
                    <p style="font-size:12px; color:var(--text-3); max-width:280px;">Choose a student from the dropdown above to view their performance timeline.</p>
                </div>
            @elseif($studentList->isEmpty())
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px 24px; text-align:center; border:1px dashed var(--border-md); border-radius:var(--radius);">
                    <p style="font-size:13px; color:var(--text-3);">No students have completed any reviewed attempts on your quizzes yet.</p>
                </div>
            @elseif($timelineData->isEmpty())
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px 24px; text-align:center; border:1px dashed var(--border-md); border-radius:var(--radius);">
                    <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No reviewed attempts found for this student.</p>
                    @if($selectedClass || $tlStart || $tlEnd)
                        <p style="font-size:12px; color:var(--text-3);">Try removing the class or date filters.</p>
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
    <div class="analytics-grid" style="display:grid; grid-template-columns:repeat(2,1fr); gap:16px;">

        {{-- F: At-Risk --}}
        <div class="card" style="overflow:hidden;">
            <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--danger); margin-bottom:4px;">Needs Attention</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">At-Risk Students</h2>
                <p style="font-size:12px; color:var(--text-2);">Avg below 50% or 2+ failed attempts.</p>
            </div>
            @if($atRisk->isEmpty())
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center;">
                    <div style="width:44px; height:44px; border-radius:50%; background:var(--accent-bg); display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No at-risk students found.</p>
                    <p style="font-size:12px; color:var(--text-3); max-width:240px;">All students are meeting the performance threshold.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table id="atRiskTable" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border);">
                                @foreach(['Student', 'Avg %', 'Fails', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('atRiskTable', {{ $i }})"
                                        style="padding:10px {{ $loop->first || $loop->last ? '22px' : '14px' }}; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                        onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                        {{ $col }} <span class="sort-icon" style="opacity:0.5;">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($atRisk as $s)
                                <tr class="divider"
                                    onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                    onmouseleave="this.style.background='transparent'">
                                    <td style="padding:12px 22px; font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">{{ $s->full_name }}</td>
                                    <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $s->avg_pct }}">
                                        <span class="badge badge-rose num">{{ number_format($s->avg_pct, 1) }}%</span>
                                    </td>
                                    <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $s->fail_count }}">
                                        <span class="num" style="font-size:13px; color:var(--text-2);">{{ $s->fail_count }}</span>
                                    </td>
                                    <td style="padding:12px 22px; white-space:nowrap;">
                                        <span class="num" style="font-size:13px; color:var(--text-2);">{{ $s->total_attempts }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- G: Strongest --}}
        <div class="card" style="overflow:hidden;">
            <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Top Performers</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Strongest Students</h2>
                <p style="font-size:12px; color:var(--text-2);">Avg 80%+ with at least 2 reviewed attempts.</p>
            </div>
            @if($strongest->isEmpty())
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center;">
                    <div style="width:44px; height:44px; border-radius:50%; background:var(--accent-bg); display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No students meet the criteria yet.</p>
                    <p style="font-size:12px; color:var(--text-3); max-width:240px;">Students need an 80%+ average across at least 2 reviewed attempts.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table id="strongestTable" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border);">
                                @foreach(['Student', 'Avg %', 'Attempts'] as $i => $col)
                                    <th onclick="sortTable('strongestTable', {{ $i }})"
                                        style="padding:10px {{ $loop->first || $loop->last ? '22px' : '14px' }}; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                        onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                        {{ $col }} <span class="sort-icon" style="opacity:0.5;">↕</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($strongest as $s)
                                <tr class="divider"
                                    onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                    onmouseleave="this.style.background='transparent'">
                                    <td style="padding:12px 22px; font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">{{ $s->full_name }}</td>
                                    <td style="padding:12px 14px; white-space:nowrap;" data-value="{{ $s->avg_pct }}">
                                        <span class="badge badge-green num">{{ number_format($s->avg_pct, 1) }}%</span>
                                    </td>
                                    <td style="padding:12px 22px; white-space:nowrap;">
                                        <span class="num" style="font-size:13px; color:var(--text-2);">{{ $s->total_attempts }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

    <style>
        @media (max-width:900px) {
            .analytics-grid { grid-template-columns:1fr !important; }
        }
        @media (max-width:640px) {
            .overall-stat-grid { grid-template-columns:repeat(2,1fr) !important; }
        }
        input[type="date"]::-webkit-calendar-picker-indicator { filter:invert(0.6); cursor:pointer; }
        select option { background:var(--surface-2); color:var(--text); }
    </style>

@push('charts')
<script>
    // Shared Chart.js defaults for dark theme
    Chart.defaults.color = '#9298b0';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.07)';

    // D: Monthly trend
    @if($monthlyTrend->isNotEmpty())
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
                    borderColor: '#4ade80',
                    backgroundColor: 'rgba(74,222,128,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#4ade80',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
    @endif

    // E: Student timeline
    @if($selectedStudent && $timelineData->isNotEmpty())
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
                    borderColor: '#60a5fa',
                    backgroundColor: 'rgba(96,165,250,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#60a5fa',
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
                    y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: 'rgba(255,255,255,0.05)' } },
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
