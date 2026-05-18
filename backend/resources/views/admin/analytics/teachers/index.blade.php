@extends('admin.layouts.app')

@section('title', 'Teacher Analytics')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-8 py-8 text-white shadow-xl">
        <p class="text-sm font-medium uppercase tracking-[0.2em] text-blue-200">System Analytics</p>
        <h1 class="mt-2 text-3xl font-bold">Teacher Analytics</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">
            Review teacher activity, quiz engagement, and learner performance across all teacher-owned quizzes.
        </p>
    </div>

    @include('admin.analytics.partials.nav')
    @include('admin.analytics.partials.filter-bar', ['routeName' => 'admin.analytics.teachers', 'filters' => $filters])

    @include('admin.analytics.partials.stat-grid', [
        'items' => [
            ['label' => 'Active Teachers', 'value' => number_format($kpis['total_teachers'] ?? 0)],
            ['label' => 'Teacher Quizzes', 'value' => number_format($kpis['total_quizzes'] ?? 0)],
            ['label' => 'Completed Attempts', 'value' => number_format($kpis['total_attempts'] ?? 0)],
            ['label' => 'Avg Pass Rate', 'value' => number_format($kpis['avg_pass_rate'] ?? 0, 1) . '%'],
        ],
    ])

    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-lg font-bold text-slate-900">Top Teachers</h2>
        <p class="mt-1 text-sm text-slate-500">Ranked by pass rate for teachers with completed attempts.</p>

        @if($topTeachers->isEmpty())
            <div class="mt-4">
                @include('admin.analytics.partials.empty-state', ['message' => 'No teacher attempts match the current filters.'])
            </div>
        @else
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                @foreach($topTeachers as $teacher)
                    <a href="{{ route('admin.analytics.teachers.show', $teacher->id) }}"
                       class="rounded-2xl border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $teacher->name ?: $teacher->email }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ number_format($teacher->total_attempts ?? 0) }} attempts</p>
                        <p class="mt-3 text-xl font-bold text-emerald-600">{{ number_format($teacher->pass_rate ?? 0, 1) }}%</p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-lg font-bold text-slate-900">All Teachers</h2>
            <p class="mt-1 text-sm text-slate-500">Includes active teachers only.</p>
        </div>

        @if($teachers->isEmpty())
            <div class="p-6">
                @include('admin.analytics.partials.empty-state', ['message' => 'No teachers match the current filters.'])
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Teacher</th>
                            <th class="px-4 py-3 text-right">Classes</th>
                            <th class="px-4 py-3 text-right">Quizzes</th>
                            <th class="px-4 py-3 text-right">Attempts</th>
                            <th class="px-4 py-3 text-right">Avg Score</th>
                            <th class="px-4 py-3 text-right">Pass Rate</th>
                            <th class="px-4 py-3 text-left">Latest Attempt</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($teachers as $teacher)
                            <tr class="cursor-pointer hover:bg-slate-50"
                                onclick="window.showPageLoadingOverlay && window.showPageLoadingOverlay('Loading teacher analytics...'); window.location='{{ route('admin.analytics.teachers.show', $teacher->id) }}'">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $teacher->name ?: $teacher->email }}</p>
                                    <p class="text-xs text-slate-500">{{ $teacher->email }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">{{ number_format($teacher->classes_count ?? 0) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($teacher->quizzes_count ?? 0) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($teacher->total_attempts ?? 0) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($teacher->avg_score ?? 0, 1) }}%</td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{ number_format($teacher->pass_rate ?? 0, 1) }}%</td>
                                <td class="px-4 py-3 text-slate-600">{{ $teacher->latest_attempt_at ? \Carbon\Carbon::parse($teacher->latest_attempt_at)->format('M d, Y') : 'No attempts' }}</td>
                                <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                    <a href="{{ route('admin.analytics.teachers.show', $teacher->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($teachers->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $teachers->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
