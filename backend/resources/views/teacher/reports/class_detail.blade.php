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
        <p style="font-size:13px; color:var(--text-2);">Student performance report for this class. Overall grade is computed based on {{ $totalQuizzes }} assigned {{ Str::plural('quiz', $totalQuizzes) }}.</p>
    </div>

    {{-- Summary Stat Cards --}}
    @php
        $passing = $students->filter(fn($s) => !is_null($s->overall_grade) && $s->overall_grade >= 75)->count();
        $avg = $students->whereNotNull('overall_grade')->avg('overall_grade');
    @endphp
    <div class="stat-grid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px;">

        <div class="card" style="padding:20px 22px;">
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:8px;">Students</p>
            <p class="num" style="font-size:26px; font-weight:700; color:var(--text); line-height:1; margin-bottom:4px;">{{ $students->count() }}</p>
            <p style="font-size:12px; color:var(--text-3);">enrolled</p>
        </div>

        <div class="card" style="padding:20px 22px;">
            @php
                $avgColor = $avg
                    ? ($avg >= 75 ? 'var(--accent)' : ($avg >= 50 ? 'var(--warn)' : 'var(--danger)'))
                    : 'var(--text)';
            @endphp
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:8px;">Class Avg</p>
            <p class="num" style="font-size:26px; font-weight:700; color:{{ $avgColor }}; line-height:1; margin-bottom:4px;">
                {{ $avg ? number_format($avg, 1) . '%' : 'N/A' }}
            </p>
            <p style="font-size:12px; color:var(--text-3);">overall grade</p>
        </div>

        <div class="card" style="padding:20px 22px;">
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:8px;">Passing</p>
            <p class="num" style="font-size:26px; font-weight:700; color:var(--accent); line-height:1; margin-bottom:4px;">{{ $passing }}</p>
            <p style="font-size:12px; color:var(--text-3);">above 75%</p>
        </div>

    </div>

    {{-- Table Card --}}
    <div class="card" style="overflow:hidden;">

        {{-- Card Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Student Performance</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Student Performance</h2>
                <p style="font-size:12px; color:var(--text-2);">Overall grade = sum of scores / total quizzes assigned</p>
            </div>
            <a href="{{ route('teacher.reports.class.export', $class->id) }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                Export to Excel
            </a>
        </div>

        @if($students->isEmpty())
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 24px; text-align:center;">
                <div style="width:52px; height:52px; border-radius:50%; background:var(--accent-bg); display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="10" cy="7" r="3" stroke="var(--accent)" stroke-width="1.5" fill="none"/>
                        <path d="M4 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No students enrolled</p>
                <p style="font-size:13px; color:var(--text-2); max-width:280px;">No students are currently enrolled in this class.</p>
            </div>

        @else
            <div style="overflow-x:auto;">
                <table id="classDetailTable" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th onclick="sortTable('classDetailTable', 0)"
                                style="padding:12px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Student ID <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('classDetailTable', 1)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                First Name <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('classDetailTable', 2)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Last Name <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('classDetailTable', 3)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Quizzes Taken <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('classDetailTable', 4)"
                                style="padding:12px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Overall Grade <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr class="divider"
                                onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                onmouseleave="this.style.background='transparent'">
                                <td style="padding:14px 22px; white-space:nowrap;">
                                    <span class="chip num">{{ $student->studentProfile?->student_id ?? '—' }}</span>
                                </td>
                                <td style="padding:14px 16px; font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">
                                    {{ $student->first_name }}
                                </td>
                                <td style="padding:14px 16px; font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">
                                    {{ $student->surname }}
                                </td>
                                <td style="padding:14px 16px; white-space:nowrap;">
                                    <span class="num" style="font-size:13px; color:var(--text-2);">{{ $student->quizzes_taken }}</span>
                                    <span style="font-size:12px; color:var(--text-3);"> / {{ $totalQuizzes }}</span>
                                </td>
                                <td style="padding:14px 22px; white-space:nowrap;" data-value="{{ $student->overall_grade ?? -1 }}">
                                    @if(!is_null($student->overall_grade))
                                        <span class="num {{ $student->overall_grade >= 75 ? 'score-high' : ($student->overall_grade >= 50 ? 'score-mid' : 'score-low') }}">
                                            {{ number_format($student->overall_grade, 2) }}%
                                        </span>
                                    @else
                                        <span class="score-none num">N/A</span>
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
                    <button id="btn-prev" onclick="currentPage--; paginateTable('classDetailTable')" class="btn btn-ghost btn-sm">← Prev</button>
                    <div id="page-numbers" style="display:flex; align-items:center; gap:4px;"></div>
                    <button id="btn-next" onclick="currentPage++; paginateTable('classDetailTable')" class="btn btn-ghost btn-sm">Next →</button>
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

                    icons.forEach(icon => { icon.textContent = '↕'; });
                    icons[colIndex].textContent = asc ? '↑' : '↓';

                    rows.sort((a, b) => {
                        const aCell = a.querySelectorAll('td')[colIndex];
                        const bCell = b.querySelectorAll('td')[colIndex];
                        const aVal = aCell.dataset.value !== undefined ? aCell.dataset.value : aCell.innerText.trim();
                        const bVal = bCell.dataset.value !== undefined ? bCell.dataset.value : bCell.innerText.trim();

                        const aNum = parseFloat(aVal);
                        const bNum = parseFloat(bVal);
                        if (!isNaN(aNum) && !isNaN(bNum)) return asc ? aNum - bNum : bNum - aNum;
                        return asc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                    });

                    rows.forEach(row => tbody.appendChild(row));
                    currentPage = 1;
                    paginateTable(tableId);
                }

                document.addEventListener('DOMContentLoaded', () => paginateTable('classDetailTable'));
            </script>
        @endif

    </div>

    <style>
        @media (max-width:900px) { .stat-grid { grid-template-columns:repeat(2,1fr) !important; } }
        @media (max-width:560px) { .stat-grid { grid-template-columns:1fr !important; } }
    </style>

@endsection
