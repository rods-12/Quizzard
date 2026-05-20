@extends('teacher.layouts.app')
@section('title', 'My Quizzes')
@section('content')

{{-- Page Header --}}
<div style="margin-bottom:28px; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div>
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Content</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">My Quizzes</h1>
        <p style="font-size:13px; color:var(--text-2);">Create and manage your quizzes.</p>
    </div>
    {{-- <a href="{{ route('teacher.quizzes.create') }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">
        + New Quiz
    </a> --}}
</div>

{{-- Flash Messages --}}
@if(session('success'))
<div class="attention-sky" style="margin-bottom:18px; display:flex; align-items:center; gap:10px;">
    <svg width="15" height="15" viewBox="0 0 20 20" fill="none" style="flex-shrink:0;">
        <circle cx="10" cy="10" r="9" stroke="var(--info)" stroke-width="1.6"/>
        <path d="M7 10l2 2 4-4" stroke="var(--info)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span style="font-size:13px; color:var(--text);">{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="attention-rose" style="margin-bottom:18px; display:flex; align-items:center; gap:10px;">
    <svg width="15" height="15" viewBox="0 0 20 20" fill="none" style="flex-shrink:0;">
        <circle cx="10" cy="10" r="9" stroke="var(--danger)" stroke-width="1.6"/>
        <path d="M10 6v4m0 3v.5" stroke="var(--danger)" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
    <span style="font-size:13px; color:var(--text);">{{ session('error') }}</span>
</div>
@endif

{{-- Quizzes Card --}}
<div class="card" style="overflow:hidden;">

    {{-- Card Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
        <div>
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">All Quizzes</p>
            <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Quiz Library</h2>
            <p style="font-size:12px; color:var(--text-2);">Manage, publish, and review all your quizzes.</p>
        </div>
        <a href="{{ route('teacher.quizzes.create') }}" class="btn btn-primary btn-sm" style="flex-shrink:0;">+ New Quiz</a>
    </div>

    @if($quizzes->isEmpty())

        {{-- Empty State --}}
        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:56px 24px; gap:14px;">
            <div style="width:48px; height:48px; border-radius:var(--radius); background:var(--surface-3); border:1px solid var(--border-md); display:flex; align-items:center; justify-content:center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="5" width="18" height="14" rx="2" stroke="var(--text-3)" stroke-width="1.6"/>
                    <path d="M8 10h8M8 14h5" stroke="var(--text-3)" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </div>
            <div style="text-align:center;">
                <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No quizzes yet</p>
                <p style="font-size:12px; color:var(--text-2);">Click <strong style="color:var(--accent);">+ New Quiz</strong> to create your first one.</p>
            </div>
        </div>

    @else

        {{-- Column Headers --}}
        <div class="quiz-row" style="display:grid; grid-template-columns:1fr 90px 90px 100px 110px 90px; gap:12px; padding:9px 22px; border-bottom:1px solid var(--border);">
            <span style="font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--text-3);">Title</span>
            <span style="font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--text-3);">Questions</span>
            <span style="font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--text-3);">Attempts</span>
            <span style="font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--text-3);">Status</span>
            <span style="font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--text-3);">Created</span>
            <span style="font-size:10px; font-weight:700; letter-spacing:0.09em; text-transform:uppercase; color:var(--text-3);">Actions</span>
        </div>

        {{-- Quiz Rows --}}
        @foreach($quizzes as $quiz)
        <div class="divider quiz-row"
             style="display:grid; grid-template-columns:1fr 90px 90px 100px 110px 90px; gap:12px; padding:13px 22px; align-items:center; transition:background 0.15s;"
             onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
             onmouseleave="this.style.background='transparent'">

            {{-- Title --}}
            <div style="min-width:0;">
                <p style="font-size:13px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $quiz->title }}</p>
            </div>

            {{-- Questions --}}
            <div>
                <span class="num" style="font-size:13px; color:var(--text-2);">{{ $quiz->questions_count }}</span>
            </div>

            {{-- Attempts --}}
            <div>
                <span class="num" style="font-size:13px; color:var(--text-2);">{{ $quiz->attempts_count }}</span>
            </div>

            {{-- Status --}}
            <div>
                @if($quiz->is_published)
                    <span class="badge badge-green">Published</span>
                @else
                    <span class="badge badge-slate">Draft</span>
                @endif
            </div>

            {{-- Created --}}
            <div>
                <span class="num" style="font-size:12px; color:var(--text-3);">{{ $quiz->created_at->format('M d, Y') }}</span>
            </div>

            {{-- Actions --}}
            <div>
                <a href="{{ route('teacher.quizzes.manage', $quiz->id) }}" class="btn btn-secondary btn-sm">
                    Manage
                </a>
            </div>

        </div>
        @endforeach

    @endif
</div>

<style>
@media (max-width: 900px) {
    .quiz-row {
        grid-template-columns: 1fr 70px 70px 90px !important;
    }
    .quiz-row > *:nth-child(5),
    .quiz-row > *:nth-child(6) {
        display: none;
    }
}
@media (max-width: 600px) {
    .quiz-row {
        grid-template-columns: 1fr 80px 90px !important;
    }
    .quiz-row > *:nth-child(3),
    .quiz-row > *:nth-child(4) {
        display: none;
    }
}
</style>

@endsection
