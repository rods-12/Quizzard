@extends('teacher.layouts.app')
@section('title', 'Edit Question')
@section('content')

{{-- Back Link --}}
<div style="margin-bottom:20px;">
    <a href="{{ route('teacher.quizzes.manage', $quiz->id) }}" class="btn btn-ghost btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
        <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M12 15l-5-5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Back to Manage Quiz
    </a>
</div>

{{-- Page Header --}}
<div style="margin-bottom:28px;">
    <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Content</p>
    <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">Edit Question</h1>
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <p style="font-size:13px; color:var(--text-2);">{{ $quiz->title }}</p>
        <span style="color:var(--text-3); font-size:12px;">·</span>
        <span class="badge badge-sky" style="font-size:10px; text-transform:capitalize;">{{ str_replace('_', ' ', $question->type) }}</span>
    </div>
</div>

{{-- Form Card --}}
<div style="max-width:680px;">
    <div class="card" style="overflow:hidden;">

        <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Editing</p>
            <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Question Details</h2>
            <p style="font-size:12px; color:var(--text-2);">Update the question text, point value, and answer options below.</p>
        </div>

        <div style="padding:22px;">

            {{-- Validation Errors --}}
            @if($errors->any())
            <div class="attention-rose" style="margin-bottom:20px;">
                <p style="font-size:12px; font-weight:700; color:var(--danger); margin-bottom:6px;">Please fix the following errors:</p>
                <ul style="margin:0; padding-left:16px;">
                    @foreach($errors->all() as $error)
                        <li style="font-size:12px; color:var(--text-2); margin-bottom:2px;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('teacher.quizzes.questions.update', ['quizId' => $quiz->id, 'questionId' => $question->id]) }}"
                  method="POST">
                @csrf
                @method('PUT')

                {{-- Question Text --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                        Question <span style="color:var(--danger);">*</span>
                    </label>
                    <textarea name="question_text" rows="3" required
                              placeholder="Enter your question..."
                              style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; resize:vertical; box-sizing:border-box; transition:border-color 0.15s;"
                              onfocus="this.style.borderColor='var(--accent)'"
                              onblur="this.style.borderColor='var(--border-md)'">{{ old('question_text', $question->question_text) }}</textarea>
                </div>

                {{-- Points --}}
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                        Points <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="number" name="points" value="{{ old('points', $question->points) }}" min="1" required
                           style="width:110px; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--mono); outline:none; box-sizing:border-box; transition:border-color 0.15s;"
                           onfocus="this.style.borderColor='var(--accent)'"
                           onblur="this.style.borderColor='var(--border-md)'">
                </div>

                {{-- ── Multiple Choice ── --}}
                @if($question->type === 'multiple_choice')
                @php $options = $question->answerOptions->sortBy('order')->values(); @endphp
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:5px;">
                        Answer Choices <span style="color:var(--danger);">*</span>
                    </label>
                    <p style="font-size:11.5px; color:var(--text-3); margin-bottom:12px;">Select the radio button next to the correct answer.</p>

                    <div id="optionsContainer" style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($options as $i => $opt)
                        <div class="option-row" style="display:flex; align-items:center; gap:10px;">
                            <input type="radio" name="correct_option" value="{{ $i }}"
                                   {{ old('correct_option', $opt->is_correct ? $i : null) == $i && (old('correct_option') !== null ? old('correct_option') == $i : $opt->is_correct) ? 'checked' : '' }}
                                   style="accent-color:var(--accent); width:15px; height:15px; flex-shrink:0; cursor:pointer;">
                            <input type="text" name="options[]"
                                   value="{{ old('options.'.$i, $opt->option_text) }}"
                                   placeholder="Option {{ chr(65 + $i) }}"
                                   style="flex:1; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:8px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; transition:border-color 0.15s;"
                                   onfocus="this.style.borderColor='var(--accent)'"
                                   onblur="this.style.borderColor='var(--border-md)'">
                        </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addOption()"
                            style="margin-top:10px; background:none; border:none; font-size:12px; font-weight:700; color:var(--accent); cursor:pointer; padding:0; font-family:var(--font);">
                        + Add Option
                    </button>
                </div>
                @endif

                {{-- ── True / False ── --}}
                @if($question->type === 'true_false')
                @php $correctTf = strtolower($question->answerOptions->firstWhere('is_correct', true)?->option_text ?? 'true'); @endphp
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:12px;">
                        Correct Answer <span style="color:var(--danger);">*</span>
                    </label>
                    <div style="display:flex; gap:12px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600; color:var(--text); background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 16px;">
                            <input type="radio" name="correct_tf" value="true"
                                   {{ old('correct_tf', $correctTf) === 'true' ? 'checked' : '' }}
                                   style="accent-color:var(--accent); width:14px; height:14px; cursor:pointer;">
                            True
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600; color:var(--text); background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 16px;">
                            <input type="radio" name="correct_tf" value="false"
                                   {{ old('correct_tf', $correctTf) === 'false' ? 'checked' : '' }}
                                   style="accent-color:var(--accent); width:14px; height:14px; cursor:pointer;">
                            False
                        </label>
                    </div>
                </div>
                @endif

                {{-- ── Identification ── --}}
                @if($question->type === 'identification')
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                        Correct Answer <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="text" name="answer"
                           value="{{ old('answer', $question->answerOptions->first()?->option_text) }}"
                           placeholder="Enter the expected answer..."
                           required
                           style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; box-sizing:border-box; transition:border-color 0.15s;"
                           onfocus="this.style.borderColor='var(--accent)'"
                           onblur="this.style.borderColor='var(--border-md)'">
                </div>
                @endif

                {{-- ── Matching ── --}}
                @if($question->type === 'matching')
                @php $pairs = $question->answerOptions->sortBy('order')->values(); @endphp
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:5px;">
                        Matching Pairs <span style="color:var(--danger);">*</span>
                    </label>
                    <p style="font-size:11.5px; color:var(--text-3); margin-bottom:12px;">Each premise on the left matches the answer on the right.</p>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                        <span style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); padding-left:2px;">Premise</span>
                        <span style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); padding-left:2px;">Match</span>
                    </div>

                    <div id="matchingContainer" style="display:flex; flex-direction:column; gap:8px;">
                        @foreach($pairs as $i => $pair)
                        <div class="pair-row" style="display:grid; grid-template-columns:1fr 1fr; gap:8px; align-items:center;">
                            <input type="text" name="premises[]"
                                   value="{{ old('premises.'.$i, $pair->option_text) }}"
                                   placeholder="Premise {{ $i + 1 }}"
                                   style="background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:8px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; transition:border-color 0.15s;"
                                   onfocus="this.style.borderColor='var(--accent)'"
                                   onblur="this.style.borderColor='var(--border-md)'">
                            <input type="text" name="matches[]"
                                   value="{{ old('matches.'.$i, $pair->match_pair) }}"
                                   placeholder="Match {{ $i + 1 }}"
                                   style="background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:8px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; transition:border-color 0.15s;"
                                   onfocus="this.style.borderColor='var(--accent)'"
                                   onblur="this.style.borderColor='var(--border-md)'">
                        </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addPair()"
                            style="margin-top:10px; background:none; border:none; font-size:12px; font-weight:700; color:var(--accent); cursor:pointer; padding:0; font-family:var(--font);">
                        + Add Pair
                    </button>
                </div>
                @endif

                {{-- Actions --}}
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px; padding-top:4px; border-top:1px solid var(--border);">
                    <a href="{{ route('teacher.quizzes.manage', $quiz->id) }}" class="btn btn-ghost btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    let optionCount = {{ $question->type === 'multiple_choice' ? $question->answerOptions->count() : 0 }};

    function addOption() {
        const container = document.getElementById('optionsContainer');
        const i = optionCount++;
        const div = document.createElement('div');
        div.className = 'option-row';
        div.style.cssText = 'display:flex; align-items:center; gap:10px;';
        div.innerHTML = `
            <input type="radio" name="correct_option" value="${i}"
                   style="accent-color:var(--accent); width:15px; height:15px; flex-shrink:0; cursor:pointer;">
            <input type="text" name="options[]" placeholder="Option ${String.fromCharCode(65 + i)}"
                   style="flex:1; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:8px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='var(--accent)'"
                   onblur="this.style.borderColor='var(--border-md)'">
        `;
        container.appendChild(div);
    }

    let pairCount = {{ $question->type === 'matching' ? $question->answerOptions->count() : 0 }};

    function addPair() {
        const container = document.getElementById('matchingContainer');
        const i = pairCount++;
        const div = document.createElement('div');
        div.className = 'pair-row';
        div.style.cssText = 'display:grid; grid-template-columns:1fr 1fr; gap:8px; align-items:center;';
        div.innerHTML = `
            <input type="text" name="premises[]" placeholder="Premise ${i + 1}"
                   style="background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:8px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='var(--accent)'"
                   onblur="this.style.borderColor='var(--border-md)'">
            <input type="text" name="matches[]" placeholder="Match ${i + 1}"
                   style="background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:8px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; transition:border-color 0.15s;"
                   onfocus="this.style.borderColor='var(--accent)'"
                   onblur="this.style.borderColor='var(--border-md)'">
        `;
        container.appendChild(div);
    }
</script>

@endsection
