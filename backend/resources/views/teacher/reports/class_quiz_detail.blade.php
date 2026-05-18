@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <a href="{{ route('teacher.reports.class.quizzes', $class->id) }}"
           style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); text-decoration:none; margin-bottom:14px;"
           onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color='var(--text-3)'">
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.56l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 111.06 1.06L5.56 9.25h10.69A.75.75 0 0117 10z" clip-rule="evenodd" />
            </svg>
            Back to Class Quizzes
        </a>
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Teacher Reports</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">{{ $quiz->title }}</h1>
        <p style="font-size:13px; color:var(--text-2);">Student results for this quiz in <strong style="color:var(--text);">{{ $class->name }}</strong>. Only students enrolled in this class are shown.</p>
    </div>

    {{-- Student Results Card --}}
    <div class="card" style="overflow:hidden;">

        {{-- Card Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Quiz Performance</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Student Results</h2>
                <p style="font-size:12px; color:var(--text-2);">
                    Total points for this quiz:
                    <span class="num" style="color:var(--text); font-weight:600;">{{ $totalPoints }}</span>
                </p>
            </div>
            <a href="{{ route('teacher.reports.class.quiz.detail.export', [$class->id, $quiz->id]) }}"
               class="btn btn-primary btn-sm" style="flex-shrink:0;">
                ⬇ Export to Excel
            </a>
        </div>

        {{-- Empty State --}}
        @if ($students->isEmpty())
            <div style="padding:64px 24px; text-align:center;">
                <div style="display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:14px; background:var(--accent-bg); margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 5.75A2.75 2.75 0 014.75 3h10.5A2.75 2.75 0 0118 5.75v8.5A2.75 2.75 0 0115.25 17H4.75A2.75 2.75 0 012 14.25v-8.5zm2.75-1.25A1.25 1.25 0 003.5 5.75v8.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-8.5c0-.69-.56-1.25-1.25-1.25H4.75z" fill="var(--accent)"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No students enrolled</p>
                <p style="font-size:12px; color:var(--text-2);">No students are currently enrolled in this class.</p>
            </div>

        @else
            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table id="classQuizDetailTable" style="width:100%; border-collapse:collapse; min-width:640px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th onclick="sortTable('classQuizDetailTable', 0)"
                                style="padding:11px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Student ID <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizDetailTable', 1)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                First Name <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizDetailTable', 2)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Last Name <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizDetailTable', 3)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Score <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizDetailTable', 4)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Percentage <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                            <th onclick="sortTable('classQuizDetailTable', 5)"
                                style="padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Status <span class="sort-icon" style="font-size:9px; margin-left:3px;">↕</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr class="divider"
                                onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                onmouseleave="this.style.background='transparent'">

                                {{-- Student ID --}}
                                <td style="padding:13px 22px; white-space:nowrap;">
                                    <span class="num" style="font-size:12px; color:var(--text-2);">
                                        {{ $student->studentProfile?->student_id ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- First Name --}}
                                <td style="padding:13px 16px; white-space:nowrap;">
                                    <span style="font-size:13px; font-weight:600; color:var(--text);">
                                        {{ $student->first_name }}
                                    </span>
                                </td>

                                {{-- Last Name --}}
                                <td style="padding:13px 16px; white-space:nowrap;">
                                    <span style="font-size:13px; font-weight:600; color:var(--text);">
                                        {{ $student->surname }}
                                    </span>
                                </td>

                                {{-- Score --}}
                                <td style="padding:13px 16px; white-space:nowrap;" data-value="{{ $student->quiz_score ?? -1 }}">
                                    <span class="num" style="font-size:13px; color:var(--text);">
                                        {{ !is_null($student->quiz_score) ? $student->quiz_score : '0' }}
                                    </span>
                                    <span style="font-size:12px; color:var(--text-3);">/ {{ $totalPoints }}</span>
                                </td>

                                {{-- Percentage --}}
                                <td style="padding:13px 16px; white-space:nowrap;" data-value="{{ $student->quiz_percentage ?? -1 }}">
                                    @if (!is_null($student->quiz_percentage))
                                        @if ($student->quiz_percentage >= 75)
                                            <span class="badge badge-green num">{{ number_format($student->quiz_percentage, 2) }}%</span>
                                        @elseif ($student->quiz_percentage >= 50)
                                            <span class="badge badge-amber num">{{ number_format($student->quiz_percentage, 2) }}%</span>
                                        @else
                                            <span class="badge badge-rose num">{{ number_format($student->quiz_percentage, 2) }}%</span>
                                        @endif
                                    @else
                                        <span class="badge badge-rose num">0%</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td style="padding:13px 16px; white-space:nowrap;" data-value="{{ $student->quiz_status }}">
                                    @if ($student->quiz_status === 'Taken')
                                        <span class="badge badge-green">Taken</span>
                                    @else
                                        <span class="badge badge-slate">Not Taken</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 22px; border-top:1px solid var(--border); flex-wrap:wrap;">
                <p id="pagination-info" style="font-size:12px; color:var(--text-3);"></p>
                <div style="display:flex; align-items:center; gap:6px;">
                    <button id="btn-prev"
                        onclick="currentPage--; paginateTable('classQuizDetailTable')"
                        class="btn btn-ghost btn-sm">
                        ← Prev
                    </button>
                    <div id="page-numbers" style="display:flex; align-items:center; gap:4px;"></div>
                    <button id="btn-next"
                        onclick="currentPage++; paginateTable('classQuizDetailTable')"
                        class="btn btn-ghost btn-sm">
                        Next →
                    </button>
                </div>
            </div>

            <script>
                const sortState = {};
                let currentPage = 1;
                const rowsPerPage = 10;

                function paginateTable(tableId) {
                    const table = document.getElementById(tableId);
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const totalPages = Math.ceil(rows.length / rowsPerPage);

                    if (currentPage > totalPages) currentPage = totalPages;
                    if (currentPage < 1) currentPage = 1;

                    rows.forEach((row, i) => {
                        const start = (currentPage - 1) * rowsPerPage;
                        const end = start + rowsPerPage;
                        row.style.display = i >= start && i < end ? '' : 'none';
                    });

                    const info = document.getElementById('pagination-info');
                    const total = rows.length;
                    const from = Math.min((currentPage - 1) * rowsPerPage + 1, total);
                    const to = Math.min(currentPage * rowsPerPage, total);
                    if (info) info.textContent = `Showing ${from}–${to} of ${total} students`;

                    document.getElementById('btn-prev').disabled = currentPage === 1;
                    document.getElementById('btn-next').disabled = currentPage === totalPages || totalPages === 0;

                    const pageNumbers = document.getElementById('page-numbers');
                    pageNumbers.innerHTML = '';
                    for (let p = 1; p <= totalPages; p++) {
                        const btn = document.createElement('button');
                        btn.textContent = p;
                        btn.className = p === currentPage ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm';
                        btn.style.minWidth = '32px';
                        btn.onclick = () => { currentPage = p; paginateTable(tableId); };
                        pageNumbers.appendChild(btn);
                    }
                }

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
                    currentPage = 1;
                    paginateTable(tableId);
                }

                document.addEventListener('DOMContentLoaded', () => paginateTable('classQuizDetailTable'));
            </script>
        @endif
    </div>

@endsection
