@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <a href="{{ route('teacher.reports.students') }}"
           style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); text-decoration:none; margin-bottom:14px;"
           onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color='var(--text-3)'">
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.56l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 111.06 1.06L5.56 9.25h10.69A.75.75 0 0117 10z" clip-rule="evenodd" />
            </svg>
            Back to Students
        </a>
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Teacher Reports</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">{{ $student->first_name }} {{ $student->surname }}</h1>
        <p style="font-size:13px; color:var(--text-2);">Quiz performance for class: <strong style="color:var(--text);">{{ $class->name }}</strong></p>
    </div>

    {{-- Quiz Info Card --}}
    <div class="card" style="overflow:hidden;">

        {{-- Card Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Quiz Report</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Quiz Info</h2>
                <p style="font-size:12px; color:var(--text-2);">Showing all quizzes assigned in this class for this student.</p>
            </div>
            <a href="{{ route('teacher.reports.student.quiz.info.export', [$student->id, $class->id]) }}"
               class="btn btn-primary btn-sm" style="flex-shrink:0; display:inline-flex; align-items:center; gap:6px;">
                <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 2a.75.75 0 01.75.75v7.19l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V2.75A.75.75 0 0110 2zm-5.25 11a.75.75 0 01.75.75v.5c0 .69.56 1.25 1.25 1.25h6.5c.69 0 1.25-.56 1.25-1.25v-.5a.75.75 0 011.5 0v.5A2.75 2.75 0 0113.25 17h-6.5A2.75 2.75 0 014 14.25v-.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                </svg>
                Export to Excel
            </a>
        </div>

        {{-- Empty State --}}
        @if ($quizzes->isEmpty())
            <div style="padding:64px 24px; text-align:center;">
                <div style="display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:14px; background:var(--accent-bg); margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.25 4A2.25 2.25 0 001 6.25v7.5A2.25 2.25 0 003.25 16h13.5A2.25 2.25 0 0019 13.75v-7.5A2.25 2.25 0 0016.75 4H3.25zm0 1.5h13.5c.414 0 .75.336.75.75v.19l-6.56 3.936a1.75 1.75 0 01-1.88 0L2.5 6.44v-.19c0-.414.336-.75.75-.75zm-.75 2.69l5.79 3.475a3.25 3.25 0 003.42 0L17.5 8.19v5.56a.75.75 0 01-.75.75H3.25a.75.75 0 01-.75-.75V8.19z" fill="var(--accent)"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No quizzes found</p>
                <p style="font-size:12px; color:var(--text-2);">There are no quizzes available for this class yet.</p>
            </div>

        @else
            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table id="studentQuizInfoTable" style="width:100%; border-collapse:collapse; min-width:640px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th onclick="sortTable('studentQuizInfoTable', 0)"
                                style="padding:11px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Quiz Name <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('studentQuizInfoTable', 1)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Score <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('studentQuizInfoTable', 2)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Total <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('studentQuizInfoTable', 3)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Status <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('studentQuizInfoTable', 4)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Date Published <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('studentQuizInfoTable', 5)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Date Completed <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quizzes as $quiz)
                            <tr class="divider"
                                onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                onmouseleave="this.style.background='transparent'">

                                {{-- Quiz Name --}}
                                <td style="padding:13px 22px; white-space:nowrap;">
                                    <span style="font-size:13px; font-weight:600; color:var(--text);">{{ $quiz->name }}</span>
                                </td>

                                {{-- Score --}}
                                <td style="padding:13px 16px; white-space:nowrap;" data-value="{{ $quiz->score ?? -1 }}">
                                    <span class="num" style="font-size:13px; color:var(--text);">
                                        {{ !is_null($quiz->score) ? number_format($quiz->score, 2) : '—' }}
                                    </span>
                                </td>

                                {{-- Total --}}
                                <td style="padding:13px 16px; white-space:nowrap;" data-value="{{ $quiz->total ?? -1 }}">
                                    <span class="num" style="font-size:13px; color:var(--text-2);">
                                        {{ !is_null($quiz->total) ? number_format($quiz->total, 2) : '—' }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td style="padding:13px 16px; white-space:nowrap;" data-value="{{ $quiz->status }}">
                                    @if ($quiz->status === 'Taken')
                                        <span class="badge badge-green">Taken</span>
                                    @else
                                        <span class="badge badge-slate">Not Yet</span>
                                    @endif
                                </td>

                                {{-- Date Published --}}
                                <td style="padding:13px 16px; white-space:nowrap; font-size:12px; color:var(--text-2);"
                                    data-value="{{ $quiz->date_published ? $quiz->date_published->timestamp : '' }}">
                                    {{ $quiz->date_published?->format('M d, Y h:i A') ?? '—' }}
                                </td>

                                {{-- Date Completed --}}
                                <td style="padding:13px 16px; white-space:nowrap; font-size:12px; color:var(--text-2);"
                                    data-value="{{ $quiz->date_completed ? $quiz->date_completed->timestamp : '' }}">
                                    {{ $quiz->date_completed?->format('M d, Y h:i A') ?? '—' }}
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

                        const aRaw = aCell.dataset.value !== undefined ? aCell.dataset.value.trim() : '';
                        const bRaw = bCell.dataset.value !== undefined ? bCell.dataset.value.trim() : '';

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
