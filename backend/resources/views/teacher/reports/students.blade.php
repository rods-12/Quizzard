@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Teacher Reports</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">Students</h1>
        <p style="font-size:13px; color:var(--text-2);">Review student participation and performance across your classes and quizzes.</p>
    </div>

    {{-- Students Table Card --}}
    <div class="card" style="overflow:hidden;">

        {{-- Card Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Active Report</p>
                <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Student Roster</h2>
                <p style="font-size:12px; color:var(--text-2);">Only students currently enrolled in your classes are shown.</p>
            </div>
            <a href="{{ route('teacher.reports.students.export') }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                Export to Excel
            </a>
        </div>

        @if($students->isEmpty())
            {{-- Empty State --}}
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 24px; text-align:center;">
                <div style="width:52px; height:52px; border-radius:50%; background:var(--accent-bg); display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                    <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="10" cy="7" r="3" stroke="var(--accent)" stroke-width="1.5" fill="none"/>
                        <path d="M4 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                    </svg>
                </div>
                <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No students found</p>
                <p style="font-size:13px; color:var(--text-2); max-width:280px;">There are currently no student records available for this report.</p>
            </div>

        @else
            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table id="studentsTable" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th onclick="sortTable('studentsTable', 0)"
                                style="padding:12px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                First Name <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('studentsTable', 1)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Last Name <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('studentsTable', 2)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Student ID <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('studentsTable', 3)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Gender <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('studentsTable', 4)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Date of Birth <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('studentsTable', 5)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Contact <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('studentsTable', 6)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Grade Level <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th onclick="sortTable('studentsTable', 7)"
                                style="padding:12px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); cursor:pointer; white-space:nowrap; user-select:none;"
                                onmouseenter="this.style.color='var(--text-2)'" onmouseleave="this.style.color='var(--text-3)'">
                                Section <span class="sort-icon" style="opacity:0.5;">↕</span>
                            </th>
                            <th style="padding:12px 22px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); white-space:nowrap;">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr class="divider"
                                onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
                                onmouseleave="this.style.background='transparent'">
                                <td style="padding:14px 22px; font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">
                                    {{ $student->first_name }}
                                </td>
                                <td style="padding:14px 16px; font-size:13px; font-weight:600; color:var(--text); white-space:nowrap;">
                                    {{ $student->surname }}
                                </td>
                                <td style="padding:14px 16px; white-space:nowrap;">
                                    <span class="chip num">{{ $student->studentProfile?->student_id ?? '—' }}</span>
                                </td>
                                <td style="padding:14px 16px; font-size:13px; color:var(--text-2); white-space:nowrap;">
                                    {{ $student->studentProfile?->gender ? ucfirst($student->studentProfile->gender) : '—' }}
                                </td>
                                <td style="padding:14px 16px; font-size:13px; color:var(--text-2); white-space:nowrap;"
                                    data-value="{{ $student->studentProfile?->date_of_birth?->format('Y-m-d') ?? '' }}">
                                    {{ $student->studentProfile?->date_of_birth?->format('M d, Y') ?? '—' }}
                                </td>
                                <td style="padding:14px 16px; font-size:13px; color:var(--text-2); white-space:nowrap;">
                                    {{ $student->studentProfile?->contact_number ?? '—' }}
                                </td>
                                <td style="padding:14px 16px; font-size:13px; color:var(--text-2); white-space:nowrap;">
                                    {{ $student->studentProfile?->grade_level ?? '—' }}
                                </td>
                                <td style="padding:14px 16px; font-size:13px; color:var(--text-2); white-space:nowrap;">
                                    {{ $student->studentProfile?->section ?? '—' }}
                                </td>
                                <td style="padding:14px 22px; white-space:nowrap;">
                                    <button
                                        onclick="openClassModal({{ $student->id }}, {{ json_encode($student->enrolled_classes) }})"
                                        class="btn btn-primary btn-sm">
                                        View Quiz Info
                                    </button>
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
                        onclick="currentPage--; paginateTable('studentsTable')"
                        class="btn btn-ghost btn-sm">
                        ← Prev
                    </button>
                    <div id="page-numbers" style="display:flex; align-items:center; gap:4px;"></div>
                    <button id="btn-next"
                        onclick="currentPage++; paginateTable('studentsTable')"
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

                        const aRaw = aCell.dataset.value !== undefined && aCell.dataset.value !== '' ? aCell.dataset.value : aCell.innerText.trim();
                        const bRaw = bCell.dataset.value !== undefined && bCell.dataset.value !== '' ? bCell.dataset.value : bCell.innerText.trim();

                        const gradeMatch = (v) => v.match(/^[a-zA-Z\s]+(\d+)$/);
                        const aGrade = gradeMatch(aRaw);
                        const bGrade = gradeMatch(bRaw);
                        if (aGrade && bGrade) {
                            return asc ? parseInt(aGrade[1]) - parseInt(bGrade[1]) : parseInt(bGrade[1]) - parseInt(aGrade[1]);
                        }

                        const aDate = Date.parse(aRaw);
                        const bDate = Date.parse(bRaw);
                        if (!isNaN(aDate) && !isNaN(bDate) && /\d{4}-\d{2}-\d{2}/.test(aRaw)) {
                            return asc ? aDate - bDate : bDate - aDate;
                        }

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

                document.addEventListener('DOMContentLoaded', () => paginateTable('studentsTable'));
            </script>
        @endif

    </div>

    {{-- Class Picker Modal --}}
    <div id="classModal"
         style="display:none; position:fixed; inset:0; z-index:50; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); padding:24px;">

        <div style="position:relative; width:100%; max-width:400px; background:var(--surface); border:1px solid var(--border-md); border-radius:var(--radius); overflow:hidden; box-shadow:0 24px 48px rgba(0,0,0,0.4);">

            {{-- Modal Header --}}
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid var(--border);">
                <div>
                    <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Class Selection</p>
                    <h3 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Select a Class</h3>
                    <p style="font-size:12px; color:var(--text-2);">Choose which class to view quiz info for this student.</p>
                </div>
                <button onclick="closeClassModal()"
                        style="flex-shrink:0; width:28px; height:28px; border-radius:50%; background:var(--surface-3); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-3);"
                        onmouseenter="this.style.color='var(--text)'" onmouseleave="this.style.color='var(--text-3)'">
                    <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div style="padding:16px 20px;">
                <div id="classModalList" style="max-height:260px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;"></div>
                <button onclick="closeClassModal()" class="btn btn-ghost btn-sm" style="width:100%; margin-top:14px; justify-content:center;">
                    Cancel
                </button>
            </div>

        </div>
    </div>

    <script>
        function openClassModal(studentId, classes) {
            const list = document.getElementById('classModalList');
            list.innerHTML = '';

            if (!classes.length) {
                list.innerHTML = `
                    <div style="padding:20px; text-align:center; border:1px dashed var(--border-md); border-radius:var(--radius-sm);">
                        <p style="font-size:13px; color:var(--text-3);">No classes found for this student.</p>
                    </div>`;
            } else {
                classes.forEach(cls => {
                    const btn = document.createElement('button');
                    btn.style.cssText = 'display:flex; align-items:center; justify-content:space-between; width:100%; padding:11px 14px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; text-align:left; transition:background 0.15s;';
                    btn.innerHTML = `
                        <span style="font-size:13px; font-weight:600; color:var(--text);">${cls.name}</span>
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="var(--accent)">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>`;
                    btn.onmouseenter = () => btn.style.background = 'var(--surface-3)';
                    btn.onmouseleave = () => btn.style.background = 'var(--surface-2)';
                    btn.onclick = () => window.location.href = `/teacher/reports/students/${studentId}/classes/${cls.id}`;
                    list.appendChild(btn);
                });
            }

            const modal = document.getElementById('classModal');
            modal.style.display = 'flex';
        }

        function closeClassModal() {
            document.getElementById('classModal').style.display = 'none';
        }
    </script>

@endsection
