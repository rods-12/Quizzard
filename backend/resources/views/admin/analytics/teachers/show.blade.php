@extends('admin.layouts.app')

@section('title', 'Teacher Analytics Detail')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-8 py-8 text-white shadow-xl">
        <a href="{{ route('admin.analytics.teachers') }}" class="inline-flex items-center rounded-xl border border-white/25 bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-lg transition hover:bg-blue-50">Back to Teachers</a>
        <h1 class="mt-3 text-3xl font-bold">{{ $teacher->name ?: $teacher->email }}</h1>
        <p class="mt-1 text-sm text-slate-300">{{ $teacher->email }}</p>
    </div>

    @include('admin.analytics.partials.nav')
    @include('admin.analytics.partials.stat-grid', [
        'items' => [
            ['label' => 'Classes', 'value' => number_format($teacher->classes_count ?? 0)],
            ['label' => 'Quizzes', 'value' => number_format($teacher->quizzes_count ?? 0)],
            ['label' => 'Completed Attempts', 'value' => number_format($teacher->total_attempts ?? 0)],
            ['label' => 'Pass Rate', 'value' => number_format($teacher->pass_rate ?? 0, 1) . '%'],
        ],
    ])

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-lg font-bold text-slate-900">Teacher Quiz Performance</h2>
            <p class="mt-1 text-sm text-slate-500">Filtered by the selected date range.</p>
        </div>

        @if($quizzes->isEmpty())
            <div class="p-6">
                @include('admin.analytics.partials.empty-state', ['message' => 'This teacher has no quiz performance data for the selected filters.'])
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Quiz</th>
                            <th class="px-4 py-3 text-right">Questions</th>
                            <th class="px-4 py-3 text-right">Attempts</th>
                            <th class="px-4 py-3 text-right">Avg Score</th>
                            <th class="px-4 py-3 text-right">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($quizzes as $quiz)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $quiz->title }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($quiz->questions_count ?? 0) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($quiz->total_attempts ?? 0) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($quiz->avg_score ?? 0, 1) }}%</td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{ number_format($quiz->pass_rate ?? 0, 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($quizzes->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $quizzes->links() }}
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
