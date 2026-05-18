@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Teacher Reports</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">Quizzes</h1>
        <p style="font-size:13px; color:var(--text-2);">Review quiz performance, participation, and publishing status across all of your quizzes.</p>
    </div>

    {{-- Quizzes Table Card --}}
    <div class="card" style="overflow:hidden;">

        {{-- Card Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Publishing and Results Overview</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Quiz Performance Report</h2>
                <p style="font-size:12px; color:var(--text-2);">This report only includes quizzes created by your account.</p>
            </div>
            <a href="{{ route('teacher.quizzes.index') }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                Manage Quizzes
            </a>
        </div>

        @if($quizzes->isEmpty())
            {{-- Empty State --}}
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 24px; text-align:center;">
                <div style="width:52px; height:52px; border-radius:50%; background:var(--accent-bg); display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="3" width="14" height="14" rx="2" stroke="var(--accent)" stroke-width="1.5" fill="none"/>
                        <path d="M6.5 7.5h7M6.5 10h7M6.5 12.5h4" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No quizzes found</p>
                <p style="font-size:13px; color:var(--text-2); max-width:280px;">There are currently no quizzes available in this report.</p>
            </div>

        @else
            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table id="quizzesTable" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th onclick="sortTable('quizzesTable', 0)"
                                style="padding:12px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Quiz Title <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('quizzesTable', 1)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Classes Assigned <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('quizzesTable', 2)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Students Attempted <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('quizzesTable', 3)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Avg Score <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('quizzesTable', 4)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Status <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th style="padding:12px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); white-space:nowrap;">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quizzes as $quiz)
                            <tr class="divider"
                                onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                onmouseleave="this.style.background='transparent'">

                                {{-- Quiz Title + Description --}}
                                <td style="padding:14px 22px;">
                                    <div style="font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">
                                        {{ $quiz->title }}
                                    </div>
                                    @if($quiz->description)
                                        <div style="margin-top:3px; font-size:11.5px; color:var(--text-3); max-width:320px; line-height:1.5; white-space:normal;">
                                            {{ $quiz->description }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Classes Assigned --}}
                                <td style="padding:14px 16px; white-space:nowrap;">
                                    <span class="num" style="font-size:13px; color:var(--text-2);">{{ $quiz->classes_count }}</span>
                                </td>

                                {{-- Students Attempted --}}
                                <td style="padding:14px 16px; white-space:nowrap;">
                                    <span class="num" style="font-size:13px; color:var(--text-2);">{{ $quiz->students_attempted_count }}</span>
                                </td>

                                {{-- Avg Score --}}
                                <td style="padding:14px 16px; white-space:nowrap;" data-value="{{ $quiz->average_score ?? -1 }}">
                                    @if(!is_null($quiz->average_score))
                                        @php $score = $quiz->average_score; @endphp
                                        <span class="num {{ $score >= 75 ? 'score-high' : ($score >= 50 ? 'score-mid' : 'score-low') }}">
                                            {{ number_format($score, 2) }}%
                                        </span>
                                    @else
                                        <span class="score-none num">N/A</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td style="padding:14px 16px; white-space:nowrap;" data-value="{{ $quiz->is_published ? 'Published' : 'Unpublished' }}">
                                    @if($quiz->is_published)
                                        <span class="badge badge-green">Published</span>
                                    @else
                                        <span class="badge badge-amber">Unpublished</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td style="padding:14px 22px; white-space:nowrap;">
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <a href="{{ route('teacher.reports.quiz.questions', $quiz->id) }}"
                                           class="btn btn-secondary btn-sm">
                                            View Questions
                                        </a>
                                        <a href="{{ route('teacher.reports.quiz.answers', $quiz->id) }}"
                                           class="btn btn-primary btn-sm">
                                            View Answers
                                        </a>
                                        <a href="{{ route('teacher.analytics.quiz', $quiz->id) }}"
                                           class="btn btn-ghost btn-sm">
                                            Analytics
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <script>
                const sortState = {};

                function sortTable(tableId, colIndex) {
                    const table = document.getElementById(tableId);
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const icons = table.querySelectorAll('.sort-icon');

                    if (!sortState[tableId]) sortState[tableId] = {};
                    const asc = !sortState[tableId][colIndex];
                    sortState[tableId][colIndex] = asc;

                    icons.forEach((icon) => { icon.textContent = '↕'; });
                    icons[colIndex].textContent = asc ? '↑' : '↓';

                    rows.sort((a, b) => {
                        const aCell = a.querySelectorAll('td')[colIndex];
                        const bCell = b.querySelectorAll('td')[colIndex];

                        const aRaw = aCell.dataset.value !== undefined && aCell.dataset.value !== ''
                            ? aCell.dataset.value.trim() : aCell.innerText.trim();
                        const bRaw = bCell.dataset.value !== undefined && bCell.dataset.value !== ''
                            ? bCell.dataset.value.trim() : bCell.innerText.trim();

                        if (aRaw === '' && bRaw === '') return 0;
                        if (aRaw === '') return 1;
                        if (bRaw === '') return -1;

                        const aNum = parseFloat(aRaw);
                        const bNum = parseFloat(bRaw);

                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return asc ? aNum - bNum : bNum - aNum;
                        }

                        return asc ? aRaw.localeCompare(bRaw) : bRaw.localeCompare(aRaw);
                    });

                    rows.forEach(row => tbody.appendChild(row));
                }
            </script>
        @endif

    </div>

@endsection
