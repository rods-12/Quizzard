@extends('admin.layouts.app')

@section('title', 'Class Details')

@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

@section('content')
<div class="space-y-6">

@if($isSuperAdmin)
{{-- ===== SUPERADMIN: Back button ===== --}}
<div>
    <a href="{{ route('admin.classes') }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition"
       style="background:rgba(255,255,255,0.05);color:#e2e8f0;border:1px solid rgba(255,255,255,0.08);"
       onmouseover="this.style.background='rgba(255,255,255,0.10)';"
       onmouseout="this.style.background='rgba(255,255,255,0.05)';">
        <span>&larr;</span>
        <span>Back to Classes</span>
    </a>
</div>

{{-- ===== SUPERADMIN: Hero ===== --}}
<div class="relative overflow-hidden rounded-2xl p-7 text-white"
     style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%); border: 1px solid rgba(99,102,241,0.3);">
    <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full" style="background: radial-gradient(circle, rgba(99,102,241,0.2), transparent 70%);"></div>
    <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full" style="background: radial-gradient(circle, rgba(139,92,246,0.15), transparent 70%);"></div>

    <div class="relative z-10 flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
        <div class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-widest" style="color:#a5b4fc;">Class Details</p>
            <h1 class="mt-2 text-3xl font-bold sm:text-4xl text-white">{{ $class->name }}</h1>
            <p class="mt-3 max-w-2xl text-sm sm:text-base" style="color:#c7d2fe;">
                {{ $class->description ?: 'No class description provided.' }}
            </p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl px-4 py-3" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color:#a5b4fc;">Teacher</p>
                    <p class="mt-1 text-base font-semibold text-white">{{ $class->teacher->name ?? 'Unknown Teacher' }}</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color:#a5b4fc;">Class Code</p>
                    <p class="mt-1 text-base font-semibold text-white">{{ $class->class_code }}</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color:#a5b4fc;">Created</p>
                    <p class="mt-1 text-base font-semibold text-white">{{ $class->created_at ? $class->created_at->format('M d, Y') : '—' }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 xl:max-w-xs xl:justify-end">
            <div class="min-w-[140px] rounded-xl px-4 py-3" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs" style="color:#a5b4fc;">Students Enrolled</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $studentsEnrolledCount }}</p>
            </div>
            <div class="min-w-[140px] rounded-xl px-4 py-3" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs" style="color:#a5b4fc;">Total Quizzes</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $quizzesCount }}</p>
            </div>
        </div>
    </div>
</div>

{{-- ===== SUPERADMIN: Quizzes Card ===== --}}
<div class="rounded-2xl p-6 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Class Quizzes</h2>
            <p class="mt-1 text-sm" style="color:#475569;">
                View all quizzes currently assigned to this class.
            </p>
        </div>
    </div>

    <style>
        .sa-quiz-row { transition: background 0.15s ease; }
        .sa-quiz-row:hover { background: rgba(99,102,241,0.06) !important; }
        .sa-quiz-row:hover .sa-quiz-title { color: #a5b4fc !important; }
        .sa-quiz-row:hover .sa-quiz-hint { opacity: 1 !important; }
    </style>

    <div class="mt-6 overflow-hidden rounded-xl" style="border:1px solid rgba(255,255,255,0.06);">
        <div class="overflow-x-auto p-2" style="background:#0f1117;">
            <table class="min-w-full border-separate border-spacing-y-2 text-sm">
                <thead class="text-left text-xs font-bold uppercase tracking-wide" style="color:#475569;">
                    <tr>
                        <th class="px-4 py-3">Quiz</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Questions</th>
                        <th class="px-4 py-3">Students Taken</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($class->quizzes as $quiz)
                        <tr class="sa-quiz-row quiz-row cursor-pointer"
                            style="background:rgba(255,255,255,0.02);"
                            data-url="{{ route('admin.classes.quizzes.details', [$class->id, $quiz->id]) }}">

                            <td class="rounded-l-xl border-y border-l px-4 py-4 transition-all duration-200"
                                style="border-color:rgba(255,255,255,0.06);">
                                <div class="flex flex-col">
                                    <span class="sa-quiz-title font-semibold transition" style="color:#e2e8f0;">
                                        {{ $quiz->title }}
                                    </span>
                                    <span class="sa-quiz-hint mt-1 text-xs font-medium transition-opacity duration-200"
                                          style="color:#6366f1;opacity:0;">
                                        Click to view results &amp; analytics
                                    </span>
                                </div>
                            </td>

                            <td class="border-y px-4 py-4 text-sm transition-all duration-200"
                                style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                                <div class="max-w-md truncate">
                                    {{ $quiz->description ?: 'No description' }}
                                </div>
                            </td>

                            <td class="border-y px-4 py-4 text-sm transition-all duration-200"
                                style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                                {{ $quiz->questions_count ?? 0 }}
                            </td>

                            <td class="border-y px-4 py-4 text-sm transition-all duration-200"
                                style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                                {{ $quiz->attempts_count ?? 0 }}
                            </td>

                            <td class="rounded-r-xl border-y border-r px-4 py-4 transition-all duration-200"
                                style="border-color:rgba(255,255,255,0.06);">
                                @if($quiz->is_published)
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                          style="background:rgba(16,185,129,0.12);color:#34d399;">
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                          style="background:rgba(245,158,11,0.12);color:#fbbf24;">
                                        Unpublished
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="rounded-xl px-4 py-10 text-center text-sm"
                                style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);color:#475569;">
                                No quizzes are currently assigned to this class.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@else
{{-- ===== ADMIN: 100% original, zero changes ===== --}}

    <!-- Back button -->
    <div>
        <a href="{{ route('admin.classes') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
            <span>&larr;</span>
            <span>Back to Classes</span>
        </a>
    </div>

    <!-- Hero / Class Info -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-900 p-6 text-white shadow-xl sm:p-8">
        <!-- subtle colored glows only, no white circles -->
        <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="absolute -bottom-16 -left-16 h-44 w-44 rounded-full bg-violet-500/10 blur-3xl"></div>

        <div class="relative z-10 flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-indigo-200">Class Details</p>
                <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $class->name }}</h1>

                <p class="mt-3 max-w-2xl text-sm text-slate-200 sm:text-base">
                    {{ $class->description ?: 'No class description provided.' }}
                </p>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-2xl bg-slate-800/60 border border-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs uppercase tracking-wide text-slate-200">Teacher</p>
                        <p class="mt-1 text-base font-semibold text-white">
                            {{ $class->teacher->name ?? 'Unknown Teacher' }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-800/60 border border-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs uppercase tracking-wide text-slate-200">Class Code</p>
                        <p class="mt-1 text-base font-semibold text-white">
                            {{ $class->class_code }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-800/60 border border-white/10 px-4 py-3 backdrop-blur">
                        <p class="text-xs uppercase tracking-wide text-slate-200">Created</p>
                        <p class="mt-1 text-base font-semibold text-white">
                            {{ $class->created_at ? $class->created_at->format('M d, Y') : '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- smaller widgets -->
            <div class="flex flex-wrap gap-3 xl:max-w-xs xl:justify-end">
                <div class="min-w-[140px] rounded-2xl bg-white/10 px-4 py-3 backdrop-blur shadow-lg">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Students Enrolled</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ $studentsEnrolledCount }}</p>
                </div>

                <div class="min-w-[140px] rounded-2xl bg-white/10 px-4 py-3 backdrop-blur shadow-lg">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Total Quizzes</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ $quizzesCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quizzes Card -->
    <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Class Quizzes</h2>
                <p class="mt-1 text-sm text-slate-500">
                    View all quizzes currently assigned to this class.
                </p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Quiz
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Description
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Questions
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Students Taken
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($class->quizzes as $quiz)
                            <tr
                                class="quiz-row cursor-pointer transition duration-150 hover:bg-indigo-100 hover:shadow-sm"
                                data-url="{{ route('admin.classes.quizzes.details', [$class->id, $quiz->id]) }}"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800">{{ $quiz->title }}</div>
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <div class="max-w-md truncate">
                                        {{ $quiz->description ?: 'No description' }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ $quiz->questions_count ?? 0 }}
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ $quiz->attempts_count ?? 0 }}
                                </td>

                                <td class="px-6 py-4">
                                    @if($quiz->is_published)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                            Unpublished
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                    No quizzes are currently assigned to this class.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.quiz-row').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('button, a')) return;
            if (this.dataset.loading === 'true') return;

            this.dataset.loading = 'true';
            this.style.opacity = '0.65';

            document.querySelectorAll('.quiz-row').forEach(otherRow => {
                if (otherRow !== this) {
                    otherRow.style.pointerEvents = 'none';
                    otherRow.style.opacity = '0.55';
                }
            });

            const overlay = document.getElementById('pageLoadingOverlay');
            if (overlay) {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            }

            window.location.href = this.dataset.url;
        });
    });
});
</script>
@endpush
