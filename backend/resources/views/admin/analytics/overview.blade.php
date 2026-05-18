@extends('admin.layouts.app')

@section('title', 'Analytics Overview')

@section('content')
@php
    $authUser = auth()->user();
    $isSuperAdmin = $authUser && $authUser->role === 'superadmin';
    $periodLabel = ($filters['date_mode'] ?? 'all') === 'all'
        ? 'All Time'
        : trim(($filters['date_from'] ?? '') . ' to ' . ($filters['date_to'] ?? ''));
    $toneClasses = [
        'good' => ['border' => 'border-emerald-200', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-800', 'bar' => 'bg-emerald-500'],
        'warning' => ['border' => 'border-amber-200', 'bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'bar' => 'bg-amber-500'],
        'danger' => ['border' => 'border-red-200', 'bg' => 'bg-red-50', 'text' => 'text-red-800', 'bar' => 'bg-red-500'],
        'info' => ['border' => 'border-blue-200', 'bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'bar' => 'bg-blue-500'],
    ];
@endphp

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-blue-200">
                    {{ $isSuperAdmin ? 'SuperAdmin Decision Support' : 'Admin Decision Support' }}
                </p>
                <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Analytics Overview</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                    Prioritized signals for account oversight, teacher activity, and student performance based on the selected period.
                </p>
            </div>

            <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-blue-100">
                {{ $periodLabel ?: 'Selected period' }}
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            @if($isSuperAdmin)
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-300">Admins</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ $totalAdmins }}</p>
                    <p class="mt-1 text-xs text-slate-300">{{ $pendingAdmins }} pending review</p>
                </div>
            @endif
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-300">Teachers</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $totalTeachers }}</p>
                <p class="mt-1 text-xs text-slate-300">{{ $teacherActivityRate }}% with activity</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-300">Students</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $totalStudents }}</p>
                <p class="mt-1 text-xs text-slate-300">{{ $studentParticipationRate }}% participated</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-300">Average Score</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $systemAvgScore }}%</p>
                <p class="mt-1 text-xs text-slate-300">Instruction quality indicator</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-300">Pass Rate</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $systemPassRate }}%</p>
                <p class="mt-1 text-xs text-slate-300">
                    @if($attemptChange !== null)
                        {{ $attemptChange >= 0 ? '+' : '' }}{{ $attemptChange }}% attempts vs previous period
                    @else
                        {{ $totalAttempts }} completed attempts
                    @endif
                </p>
            </div>
        </div>
    </div>

    @include('admin.analytics.partials.nav')
    @include('admin.analytics.partials.filter-bar', ['routeName' => 'admin.analytics.overview', 'filters' => $filters, 'showSearch' => false])

    <section class="space-y-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Priority Signals</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">What needs attention first</h2>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            @foreach($criticalInsights as $item)
                @php $tone = $toneClasses[$item['tone']] ?? $toneClasses['info']; @endphp
                <a href="{{ $item['link'] }}" class="rounded-2xl border {{ $tone['border'] }} {{ $tone['bg'] }} p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold {{ $tone['text'] }}">{{ $item['value'] }}</p>
                        </div>
                        <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold {{ $tone['text'] }}">
                            {{ ucfirst($item['tone']) }}
                        </span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $item['support'] }}</p>
                </a>
            @endforeach
        </div>
    </section>

    @if(count($insights) > 0)
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($insights as $insight)
                @php
                    $tone = match($insight['type']) {
                        'warning' => $toneClasses['warning'],
                        'success' => $toneClasses['good'],
                        default => $toneClasses['info'],
                    };
                @endphp
                <div class="rounded-2xl border {{ $tone['border'] }} {{ $tone['bg'] }} p-4 shadow-sm">
                    <p class="text-sm leading-6 {{ $tone['text'] }}">{!! $insight['message'] !!}</p>
                    <a href="{{ $insight['link'] }}" class="mt-2 inline-block text-xs font-semibold {{ $tone['text'] }} underline-offset-2 hover:underline">
                        {{ $insight['label'] ?? 'View Details' }} &rarr;
                    </a>
                </div>
            @endforeach
        </section>
    @endif

    <section class="grid gap-5 {{ $isSuperAdmin ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
        @if($isSuperAdmin)
            <div onclick="window.location='{{ route('admin.users.index', ['role' => 'admin']) }}'" class="cursor-pointer rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:ring-blue-200 hover:shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Admin Governance</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-900">Account approval load</h2>
                    </div>
                    <span class="rounded-full {{ $pendingAdmins > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} px-3 py-1 text-xs font-semibold">
                        {{ $pendingAdmins > 0 ? 'Review needed' : 'Healthy' }}
                    </span>
                </div>
                <p class="mt-2 text-sm leading-6 text-slate-500">Pending admin accounts should be reviewed before they affect management coverage.</p>
                <p class="mt-3 text-xs font-semibold text-blue-600">View details &rarr;</p>
                <div class="mt-5 space-y-4">
                    @foreach([
                        ['Active admins', $activeAdmins, $totalAdmins, 'bg-emerald-500'],
                        ['Pending admins', $pendingAdmins, $totalAdmins, 'bg-amber-500'],
                        ['Deactivated admins', $deactivatedAdmins, $totalAdmins, 'bg-red-500'],
                    ] as [$label, $value, $base, $bar])
                        @php $width = $base > 0 ? min(100, round(($value / $base) * 100, 1)) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $label }}</span>
                                <span class="font-semibold text-slate-900">{{ $value }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full {{ $bar }}" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div onclick="window.location='{{ route('admin.analytics.teachers') }}'" class="cursor-pointer rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:ring-blue-200 hover:shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Teacher Overview</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">Instruction coverage</h2>
                </div>
                <span class="rounded-full {{ $teacherActivityRate >= 80 ? 'bg-emerald-100 text-emerald-700' : ($teacherActivityRate >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }} px-3 py-1 text-xs font-semibold">
                    {{ $teacherActivityRate }}%
                </span>
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-500">Classes with low teacher activity may need instructor support or reassignment.</p>
            <p class="mt-3 text-xs font-semibold text-blue-600">View teacher details &rarr;</p>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full {{ $teacherActivityRate >= 80 ? 'bg-emerald-500' : ($teacherActivityRate >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $teacherActivityRate }}%"></div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $activeTeachers }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">No activity</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $inactiveTeachers }}</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-slate-500">
                Most active teacher: <span class="font-semibold text-slate-800">{{ $mostActiveTeacher?->name ?? 'No activity yet' }}</span>
            </p>
        </div>

        <div onclick="window.location='{{ route('admin.analytics.students') }}'" class="cursor-pointer rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:ring-blue-200 hover:shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Student Overview</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">Participation health</h2>
                </div>
                <span class="rounded-full {{ $studentParticipationRate >= 80 ? 'bg-emerald-100 text-emerald-700' : ($studentParticipationRate >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }} px-3 py-1 text-xs font-semibold">
                    {{ $studentParticipationRate }}%
                </span>
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-500">Low participation helps identify classes that need reminders, schedule changes, or guidance.</p>
            <p class="mt-3 text-xs font-semibold text-blue-600">View student details &rarr;</p>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full {{ $studentParticipationRate >= 80 ? 'bg-emerald-500' : ($studentParticipationRate >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $studentParticipationRate }}%"></div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Participated</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $activeStudents }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">No attempts</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $inactiveStudents }}</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-slate-500">
                Most active class: <span class="font-semibold text-slate-800">{{ $mostActiveClass?->name ?? 'No activity yet' }}</span>
            </p>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200 lg:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Performance Trends</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900">Completed attempts over time</h2>
            <p class="mt-1 mb-5 text-sm text-slate-500">Rising or falling attempt volume helps decide when to adjust quiz schedules or reminders.</p>
            @if($attemptsOverTime->isEmpty())
                @include('admin.analytics.partials.empty-state', [
                    'title' => 'No attempt activity yet',
                    'message' => 'Try All Time or wait until students complete quizzes in this period.'
                ])
            @else
                <canvas id="activityChart" height="120"></canvas>
            @endif
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Outcome Balance</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900">Pass and fail mix</h2>
            <p class="mt-1 mb-5 text-sm text-slate-500">A low pass rate can point to difficult content or support gaps.</p>
            @if($totalAttempts === 0)
                @include('admin.analytics.partials.empty-state', [
                    'title' => 'No outcomes yet',
                    'message' => 'Pass/fail distribution will appear after completed attempts.'
                ])
            @else
                <div class="flex flex-col items-center gap-5">
                    <div class="relative" style="width:160px;height:160px;">
                        <canvas id="systemDonut"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-slate-800">{{ $systemPassRate }}%</span>
                            <span class="text-xs text-slate-500">Pass Rate</span>
                        </div>
                    </div>
                    <div class="grid w-full grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-emerald-50 p-3 text-center">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Passed</p>
                            <p class="mt-1 text-xl font-bold text-emerald-800">{{ $passCount }}</p>
                        </div>
                        <div class="rounded-2xl bg-red-50 p-3 text-center">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Failed</p>
                            <p class="mt-1 text-xl font-bold text-red-800">{{ $systemFail }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">At-Risk Students</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">Lowest average score</h2>
                </div>
                <a href="{{ route('admin.analytics.students', ['sort' => 'avg_score_asc']) }}" class="text-xs font-semibold text-red-600 hover:underline">View all &rarr;</a>
            </div>
            <p class="mb-4 text-sm text-slate-500">Use this list to prioritize remediation, parent contact, or adviser follow-up.</p>
            @if($bottom5Students->isEmpty())
                @include('admin.analytics.partials.empty-state', ['title' => 'No at-risk data', 'message' => 'Students will appear here after they complete quizzes.'])
            @else
                <div class="space-y-2">
                    @foreach($bottom5Students as $i => $student)
                        <a href="{{ route('admin.analytics.students.show', $student['id']) }}" class="block rounded-2xl border border-slate-100 px-3 py-3 transition hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $student['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $student['total'] }} attempts | {{ $student['pass_rate'] }}% pass</p>
                                </div>
                                <span class="rounded-lg {{ $student['avg_pct'] < 60 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }} px-2 py-1 text-xs font-bold">
                                    {{ $student['avg_pct'] }}%
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Strong Performers</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">Highest average score</h2>
                </div>
                <a href="{{ route('admin.analytics.students') }}" class="text-xs font-semibold text-blue-600 hover:underline">View all &rarr;</a>
            </div>
            <p class="mb-4 text-sm text-slate-500">High-performing students can guide enrichment planning and peer support decisions.</p>
            @if($top5Students->isEmpty())
                @include('admin.analytics.partials.empty-state', ['title' => 'No student performance yet', 'message' => 'Completed quiz attempts will populate this ranking.'])
            @else
                <div class="space-y-2">
                    @foreach($top5Students as $i => $student)
                        <a href="{{ route('admin.analytics.students.show', $student['id']) }}" class="block rounded-2xl border border-slate-100 px-3 py-3 transition hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $student['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $student['total'] }} attempts | {{ $student['pass_rate'] }}% pass</p>
                                </div>
                                <span class="rounded-lg bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">{{ $student['avg_pct'] }}%</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Assessment Signals</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">Quizzes with strongest outcomes</h2>
            </div>
            <p class="mb-4 text-sm text-slate-500">High pass rates can identify well-aligned assessments to reuse as models.</p>
            @if($top5Quizzes->isEmpty())
                @include('admin.analytics.partials.empty-state', ['title' => 'No quiz outcomes yet', 'message' => 'Published quizzes will appear after completed attempts.'])
            @else
                <div class="space-y-2">
                    @foreach($top5Quizzes as $quiz)
                        <a href="{{ route('admin.analytics.quizzes.show', $quiz['id']) }}" class="block rounded-2xl border border-slate-100 px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $quiz['title'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $quiz['total_attempts'] }} attempts | {{ $quiz['teacher_name'] }}</p>
                                </div>
                                <span class="rounded-lg bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">{{ $quiz['pass_rate'] }}%</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if($classBars->isNotEmpty())
        <section class="rounded-3xl bg-white p-5 shadow-lg ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Attendance And Class Outcomes</p>
            <h2 class="mt-1 text-lg font-bold text-slate-900">Pass vs fail by class</h2>
            <p class="mt-1 mb-5 text-sm text-slate-500">Classes with low outcomes may need instructor reassignment, remediation, or schedule review.</p>
            <canvas id="classBarsChart" height="100"></canvas>
        </section>
    @else
        @include('admin.analytics.partials.empty-state', [
            'title' => 'No class outcome comparison yet',
            'message' => 'Class outcome charts will appear once class quizzes receive completed attempts.'
        ])
    @endif
</div>

<script>
(function () {
    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = 'inherit';
    Chart.defaults.plugins.legend.display = false;

    const C = {
        pass: '#10b981',
        fail: '#f87171',
        blue: '#3b82f6',
        grid: 'rgba(15,23,42,0.07)',
    };

    const actEl = document.getElementById('activityChart');
    if (actEl) {
        const labels = @json($attemptsOverTime->pluck('date'));
        const counts = @json($attemptsOverTime->pluck('count'));
        new Chart(actEl, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Attempts',
                    data: counts,
                    borderColor: C.blue,
                    backgroundColor: 'rgba(59,130,246,0.10)',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                animation: { duration: 700 },
                scales: {
                    x: { grid: { color: C.grid }, ticks: { color: '#94a3b8', maxTicksLimit: 10 } },
                    y: { beginAtZero: true, grid: { color: C.grid }, ticks: { stepSize: 1, precision: 0 } }
                },
                plugins: { tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} attempt${ctx.parsed.y !== 1 ? 's' : ''}` } } }
            }
        });
    }

    const donutEl = document.getElementById('systemDonut');
    if (donutEl) {
        new Chart(donutEl, {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed'],
                datasets: [{
                    data: [@json($passCount), @json($systemFail)],
                    backgroundColor: [C.pass, C.fail],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 5,
                }]
            },
            options: {
                cutout: '72%',
                animation: { animateRotate: true, duration: 800 },
                plugins: { tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } } }
            }
        });
    }

    const cbEl = document.getElementById('classBarsChart');
    if (cbEl) {
        const classBars = @json($classBars);
        new Chart(cbEl, {
            type: 'bar',
            data: {
                labels: classBars.map(c => c.name),
                datasets: [
                    {
                        label: 'Passed',
                        data: classBars.map(c => c.pass),
                        backgroundColor: 'rgba(16,185,129,0.75)',
                        borderColor: C.pass,
                        borderWidth: 1.5,
                        borderRadius: { topLeft: 6, topRight: 6 },
                        stack: 'stack',
                    },
                    {
                        label: 'Failed',
                        data: classBars.map(c => c.fail),
                        backgroundColor: 'rgba(248,113,113,0.65)',
                        borderColor: C.fail,
                        borderWidth: 1.5,
                        borderRadius: { bottomLeft: 6, bottomRight: 6 },
                        stack: 'stack',
                    },
                ]
            },
            options: {
                responsive: true,
                animation: { duration: 700 },
                plugins: {
                    legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 12 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grid: { color: C.grid }, ticks: { stepSize: 1, precision: 0 } }
                }
            }
        });
    }
})();
</script>
@endsection
