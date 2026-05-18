@extends('admin.layouts.app')

@section('title', 'Quiz Analytics Detail')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 p-6 text-white shadow-xl">
        <a href="{{ route('admin.analytics.quizzes', request()->query()) }}"
           class="inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-blue-50"
           data-loading-text="Back to quiz analytics...">
            Back to Quizzes
        </a>
        <div class="mt-5">
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-blue-200">Quiz Drill-Down</p>
            <h1 class="mt-2 text-3xl font-bold">{{ $quiz->title }}</h1>
            <p class="mt-2 text-sm text-slate-300">
                Teacher: {{ $quiz->teacher?->name ?? 'Unassigned' }} |
                Classes: {{ $quiz->classes->pluck('name')->join(', ') ?: 'No class assigned' }}
            </p>
        </div>
    </div>

    @include('admin.analytics.partials.nav')
    @include('admin.analytics.partials.filter-bar', [
        'routeName' => 'admin.analytics.quizzes.show',
        'filters' => $filters,
        'showSearch' => false,
        'actionUrl' => route('admin.analytics.quizzes.show', $quiz),
        'resetUrl' => route('admin.analytics.quizzes.show', $quiz),
    ])

    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
        @foreach([
            ['Total Attempts', number_format($kpis['total_attempts'] ?? 0), 'Completed quiz submissions'],
            ['Average Score', number_format($kpis['avg_score'] ?? 0, 1) . '%', 'Overall learner performance'],
            ['Pass Rate', number_format($kpis['pass_rate'] ?? 0, 1) . '%', 'Uses 60% passing threshold'],
            ['Passed', number_format($kpis['pass_count'] ?? 0), 'Attempts at or above 60%'],
            ['Failed', number_format($kpis['fail_count'] ?? 0), 'Attempts below 60%'],
        ] as [$label, $value, $hint])
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-bold text-slate-900">Student Results For This Quiz</h2>
            <p class="mt-1 text-xs text-slate-500">Click a student to open their individual analytics profile.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Student</th>
                        <th class="px-3 py-2 text-right">Attempts</th>
                        <th class="px-3 py-2 text-right">Avg Score</th>
                        <th class="px-3 py-2 text-right">Pass Rate</th>
                        <th class="px-3 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $student)
                        <tr class="cursor-pointer hover:bg-slate-50"
                            onclick="window.showPageLoadingOverlay && window.showPageLoadingOverlay('Loading student analytics...'); window.location='{{ route('admin.analytics.students.show', $student->id) }}'">
                            <td class="px-3 py-2">
                                <p class="font-semibold text-slate-800">{{ $student->full_name ?: $student->email }}</p>
                                <p class="text-[11px] text-slate-500">{{ $student->email }}</p>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($student->attempt_count ?? 0) }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ number_format($student->avg_score ?? 0, 1) }}%</td>
                            <td class="px-3 py-2 text-right font-semibold text-emerald-600">{{ number_format($student->pass_rate ?? 0, 1) }}%</td>
                            <td class="px-3 py-2 text-center" onclick="event.stopPropagation()">
                                <a href="{{ route('admin.analytics.students.show', $student->id) }}"
                                   class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-slate-400">No completed attempts match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $students->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
