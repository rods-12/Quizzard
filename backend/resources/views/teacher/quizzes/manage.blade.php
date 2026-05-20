@extends('teacher.layouts.app')
@section('title', 'Manage Quiz')
@section('content')

{{-- Back Link --}}
<div style="margin-bottom:20px;">
    <a href="{{ route('teacher.quizzes.index') }}" class="btn btn-ghost btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
        <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M12 15l-5-5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Back to My Quizzes
    </a>
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

{{-- Page Header --}}
<div style="margin-bottom:28px;">
    <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Quiz Management</p>
    <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">{{ $quiz->title }}</h1>
    <p style="font-size:13px; color:var(--text-2);">{{ $quiz->description ?? 'No description provided.' }}</p>
</div>

{{-- Stat Strip --}}
<div class="quiz-stats-grid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px;">

    <div class="card" style="padding:16px 20px;">
        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:6px;">Questions</p>
        <p class="num" style="font-size:26px; font-weight:700; color:var(--text); line-height:1;">{{ $quiz->questions->count() }}</p>
    </div>

    <div class="card" style="padding:16px 20px;">
        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:6px;">Total Points</p>
        <p class="num" style="font-size:26px; font-weight:700; color:var(--text); line-height:1;">{{ $quiz->questions->sum('points') }}</p>
    </div>

    <div class="card" style="padding:16px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div>
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:6px;">Status</p>
            @if($quiz->is_published)
                <span class="badge badge-green">Published</span>
            @else
                <span class="badge badge-slate">Draft</span>
            @endif
        </div>
        <form action="{{ route('teacher.quizzes.toggle-publish', $quiz->id) }}" method="POST">
            @csrf
            @if($quiz->is_published)
                <button type="submit" class="btn btn-sm" style="background:var(--accent-bg2); color:var(--accent); border:1px solid rgba(74,222,128,0.22); font-size:11.5px;">
                    Unpublish
                </button>
            @else
                <button type="submit" class="btn btn-primary btn-sm">
                    Publish
                </button>
            @endif
        </form>
    </div>

</div>

{{-- Edit Quiz Details --}}
<div class="card" style="overflow:hidden; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border);">
        <div>
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Settings</p>
            <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Edit Quiz Details</h2>
            <p style="font-size:12px; color:var(--text-2);">Update the title and description for this quiz.</p>
        </div>
    </div>

    <div style="padding:22px;">

        @if($errors->any())
        <div class="attention-rose" style="margin-bottom:18px;">
            <p style="font-size:12px; font-weight:700; color:var(--danger); margin-bottom:6px;">Please fix the following errors:</p>
            <ul style="margin:0; padding-left:16px;">
                @foreach($errors->all() as $error)
                    <li style="font-size:12px; color:var(--text-2); margin-bottom:2px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('teacher.quizzes.update', $quiz->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                    Title <span style="color:var(--danger);">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required
                       style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; box-sizing:border-box; transition:border-color 0.15s;"
                       onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-md)'">
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                    Description
                </label>
                <textarea name="description" rows="3"
                          style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; resize:vertical; box-sizing:border-box; transition:border-color 0.15s;"
                          onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border-md)'">{{ old('description', $quiz->description) }}</textarea>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Questions --}}
<div class="card" style="overflow:hidden;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid var(--border); flex-wrap:wrap;">
        <div>
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Content</p>
            <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Questions</h2>
            <p style="font-size:12px; color:var(--text-2);">
                <span class="num">{{ $quiz->questions->count() }}</span> question{{ $quiz->questions->count() !== 1 ? 's' : '' }} in this quiz.
            </p>
        </div>

        @if(!$quiz->has_attempts)
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <select id="questionTypeSelect"
                    style="background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:7px 11px; font-size:12px; color:var(--text); font-family:var(--font); outline:none; cursor:pointer;">
                <option value="multiple_choice">Multiple Choice</option>
                <option value="true_false">True / False</option>
                <option value="identification">Identification</option>
                <option value="matching">Matching</option>
            </select>
            <a id="addQuestionBtn"
               href="{{ route('teacher.quizzes.questions.create', ['quizId' => $quiz->id, 'type' => 'multiple_choice']) }}"
               class="btn btn-primary btn-sm">
                + Add Question
            </a>
            <button onclick="openAiModal()" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:5px;">
                <span>✨</span> Generate with AI
            </button>
        </div>
        @else
        <span style="font-size:11.5px; color:var(--text-3); font-style:italic;">Questions are locked — this quiz has attempts.</span>
        @endif
    </div>

    {{-- Question List --}}
    @if($quiz->questions->isEmpty())
    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:56px 24px; gap:14px;">
        <div style="width:48px; height:48px; border-radius:var(--radius); background:var(--surface-3); border:1px solid var(--border-md); display:flex; align-items:center; justify-content:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="var(--text-3)" stroke-width="1.6"/>
                <path d="M12 8v4m0 3v.5" stroke="var(--text-3)" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
        </div>
        <div style="text-align:center;">
            <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:4px;">No questions yet</p>
            <p style="font-size:12px; color:var(--text-2);">Use <strong style="color:var(--accent);">+ Add Question</strong> or <strong style="color:var(--accent);">✨ Generate with AI</strong> to get started.</p>
        </div>
    </div>
    @else
        @foreach($quiz->questions as $index => $question)
        <div class="divider"
             style="padding:16px 22px; transition:background 0.15s;"
             onmouseenter="this.style.background='rgba(255,255,255,0.018)'"
             onmouseleave="this.style.background='transparent'">

            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px;">

                {{-- Question Body --}}
                <div style="flex:1; min-width:0;">

                    {{-- Meta chips --}}
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:7px;">
                        <span class="chip num" style="color:var(--text-3); font-size:10.5px;">Q{{ $index + 1 }}</span>
                        <span class="badge badge-sky" style="font-size:10px; text-transform:capitalize;">
                            {{ str_replace('_', ' ', $question->type) }}
                        </span>
                        <span class="badge badge-slate">
                            <span class="num">{{ $question->points }}</span>&nbsp;{{ $question->points == 1 ? 'pt' : 'pts' }}
                        </span>
                    </div>

                    {{-- Question text --}}
                    <p style="font-size:13px; font-weight:600; color:var(--text); margin-bottom:10px; line-height:1.5;">{{ $question->question_text }}</p>

                    {{-- Answer preview --}}
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        @if($question->type === 'multiple_choice')
                            @foreach($question->answerOptions->sortBy('order') as $i => $opt)
                            <div style="display:flex; align-items:center; gap:7px;">
                                <span class="num" style="font-size:11px; color:var(--text-3); min-width:14px;">{{ chr(65 + $i) }}.</span>
                                <span style="font-size:12px; color:{{ $opt->is_correct ? 'var(--accent)' : 'var(--text-2)' }}; font-weight:{{ $opt->is_correct ? '600' : '400' }};">
                                    {{ $opt->option_text }}
                                    @if($opt->is_correct)
                                        <span style="margin-left:4px; font-size:10px; background:var(--accent-bg); color:var(--accent); padding:1px 6px; border-radius:20px; font-weight:700;">✓ Correct</span>
                                    @endif
                                </span>
                            </div>
                            @endforeach

                        @elseif($question->type === 'true_false')
                            @php $correct = $question->answerOptions->firstWhere('is_correct', true); @endphp
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="font-size:11px; color:var(--text-3);">Answer:</span>
                                <span style="font-size:12px; font-weight:600; color:var(--accent);">{{ $correct?->option_text ?? '—' }}</span>
                            </div>

                        @elseif($question->type === 'identification')
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="font-size:11px; color:var(--text-3);">Answer:</span>
                                <span style="font-size:12px; font-weight:600; color:var(--accent);">{{ $question->answerOptions->first()?->option_text ?? '—' }}</span>
                            </div>

                        @elseif($question->type === 'matching')
                            @foreach($question->answerOptions->sortBy('order') as $opt)
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span style="font-size:12px; color:var(--text-2);">{{ $opt->option_text }}</span>
                                <span style="font-size:11px; color:var(--text-3);">→</span>
                                <span style="font-size:12px; color:var(--accent); font-weight:600;">{{ $opt->match_pair }}</span>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Row Actions --}}
                @if(!$quiz->has_attempts)
                <div style="display:flex; align-items:center; gap:6px; flex-shrink:0; padding-top:2px;">
                    <a href="{{ route('teacher.quizzes.questions.edit', ['quizId' => $quiz->id, 'questionId' => $question->id]) }}"
                       class="btn btn-secondary btn-sm">Edit</a>
                    <form action="{{ route('teacher.quizzes.questions.destroy', ['quizId' => $quiz->id, 'questionId' => $question->id]) }}"
                          method="POST"
                          onsubmit="return confirm('Delete this question?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm" style="background:rgba(248,113,113,0.08); color:var(--danger); border:1px solid rgba(248,113,113,0.18);">
                            Delete
                        </button>
                    </form>
                </div>
                @endif

            </div>
        </div>
        @endforeach
    @endif
</div>

<script>
    const select = document.getElementById('questionTypeSelect');
    const btn    = document.getElementById('addQuestionBtn');
    const base   = "{{ route('teacher.quizzes.questions.create', ['quizId' => $quiz->id, 'type' => '__TYPE__']) }}";
    if (select && btn) {
        select.addEventListener('change', function () {
            btn.href = base.replace('__TYPE__', this.value);
        });
    }
</script>

@include('teacher.quizzes.partials.ai_generate_modal')

<style>
@media (max-width: 900px) {
    .quiz-stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 600px) {
    .quiz-stats-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

@endsection
