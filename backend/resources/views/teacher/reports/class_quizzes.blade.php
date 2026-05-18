@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <a href="{{ route('teacher.reports.classes') }}"
           style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); text-decoration:none; margin-bottom:14px;"
           onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color='var(--text-3)'">
            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.56l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 111.06 1.06L5.56 9.25h10.69A.75.75 0 0117 10z" clip-rule="evenodd"/>
            </svg>
            Back to Classes
        </a>
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Teacher Reports</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">{{ $class->name }}</h1>
        <p style="font-size:13px; color:var(--text-2);">All quizzes assigned to this class, including question count, student participation, and publish status.</p>
    </div>

    {{-- Quizzes Table Card --}}
    <div class="card" style="overflow:hidden;">

        {{-- Card Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Quiz Report</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Assigned Quiz Overview</h2>
                <p style="font-size:12px; color:var(--text-2);">Showing {{ $quizzes->count() }} {{ Str::plural('quiz', $quizzes->count()) }} assigned to this class.</p>
            </div>
        </div>

        @if($quizzes->isEmpty())
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 24px; text-align:center;">
                <div style="width:52px; height:52px; border-radius:50%; background:var(--accent-bg); display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="3" width="14" height="14" rx="2" stroke="var(--accent)" stroke-width="1.5" fill="none"/>
                        <path d="M6.5 7.5h7M6.5 10h7M6.5 12.5h4" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No quizzes assigned</p>
                <p style="font-size:13px; color:var(--text-2); max-width:280px;">No quizzes have been assigned to this class yet.</p>
            </div>

        @else
            <div style="overflow-x:auto;">
                <table id="classQuizzesTable" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th onclick="sortTable('classQuizzesTable', 0)"
                                style="padding:12px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Quiz Name <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizzesTable', 1)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Questions <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizzesTable', 2)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Students Taken <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizzesTable', 3)"
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
                                <td style="padding:14px 22px; font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">
                                    {{ $quiz->title }}
                                </td>
                                <td style="padding:14px 16px; white-space:nowrap;">
                                    <span class="num" style="font-size:13px; color:var(--text-2);">{{ $quiz->questions_count }}</span>
                                </td>
                                <td style="padding:14px 16px; white-space:nowrap;" data-value="{{ $quiz->students_taken_count }}">
                                    <span class="num" style="font-size:13px; color:var(--text-2);">{{ $quiz->students_taken_count }}</span>
                                    <span style="font-size:12px; color:var(--text-3);"> / {{ $class->students->count() }}</span>
                                </td>
                                <td style="padding:14px 16px; white-space:nowrap;" data-value="{{ $quiz->is_published ? 'Published' : 'Unpublished' }}">
                                    @if($quiz->is_published)
                                        <span class="badge badge-green">Published</span>
                                    @else
                                        <span class="badge badge-slate">Unpublished</span>
                                    @endif
                                </td>
                                <td style="padding:14px 22px; white-space:nowrap;">
                                    <a href="{{ route('teacher.reports.class.quiz.detail', [$class->id, $quiz->id]) }}"
                                       class="btn btn-secondary btn-sm">
                                        View Students
                                    </a>
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

                        const aRaw = aCell.dataset.value !== undefined && aCell.dataset.value !== '' ? aCell.dataset.value.trim() : aCell.innerText.trim();
                        const bRaw = bCell.dataset.value !== undefined && bCell.dataset.value !== '' ? bCell.dataset.value.trim() : bCell.innerText.trim();

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
