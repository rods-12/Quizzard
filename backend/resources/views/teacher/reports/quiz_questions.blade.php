@extends('teacher.layouts.app')

@section('content')

    {{-- Page Header --}}
    <div style="margin-bottom:28px;">
        <a href="{{ route('teacher.reports.quizzes') }}"
           style="display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); text-decoration:none; margin-bottom:14px;"
           onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color='var(--text-3)'">
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.56l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 111.06 1.06L5.56 9.25h10.69A.75.75 0 0117 10z" clip-rule="evenodd" />
            </svg>
            Back to Quizzes
        </a>
        <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Quiz Report</p>
        <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">{{ $quiz->title }}</h1>
        <p style="font-size:13px; color:var(--text-2);">Test Questionnaire — questions only, no answers shown.</p>
    </div>

    {{-- Action Bar --}}
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
        <a href="{{ route('teacher.reports.quiz.questions.export.docx', $quiz->id) }}"
           class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2a.75.75 0 01.75.75v7.19l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V2.75A.75.75 0 0110 2zm-5.25 11a.75.75 0 01.75.75v.5c0 .69.56 1.25 1.25 1.25h6.5c.69 0 1.25-.56 1.25-1.25v-.5a.75.75 0 011.5 0v.5A2.75 2.75 0 0113.25 17h-6.5A2.75 2.75 0 014 14.25v-.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
            </svg>
            Export DOCX
        </a>
        <a href="{{ route('teacher.reports.quiz.questions.export.pdf', $quiz->id) }}"
           class="btn btn-sm" style="display:inline-flex; align-items:center; gap:6px; background:var(--danger); color:#fff; border-color:var(--danger);"
           onmouseenter="this.style.opacity='0.85'" onmouseleave="this.style.opacity='1'">
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2a.75.75 0 01.75.75v7.19l2.22-2.22a.75.75 0 111.06 1.06l-3.5 3.5a.75.75 0 01-1.06 0l-3.5-3.5a.75.75 0 111.06-1.06l2.22 2.22V2.75A.75.75 0 0110 2zm-5.25 11a.75.75 0 01.75.75v.5c0 .69.56 1.25 1.25 1.25h6.5c.69 0 1.25-.56 1.25-1.25v-.5a.75.75 0 011.5 0v.5A2.75 2.75 0 0113.25 17h-6.5A2.75 2.75 0 014 14.25v-.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
            </svg>
            Export PDF
        </a>
    </div>

    {{-- Questionnaire Card --}}
    <div class="card" style="overflow:hidden;">

        {{-- Card Header --}}
        <div style="padding:24px 28px; border-bottom:1px solid var(--border); text-align:center;">
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:8px;">Questionnaire View</p>
            <h2 style="font-size:20px; font-weight:700; color:var(--text); letter-spacing:-0.02em; margin-bottom:4px;">{{ $quiz->title }}</h2>
            <p style="font-size:12px; color:var(--text-3); font-style:italic; margin-bottom:0;">Test Questionnaire</p>
            @if ($quiz->description)
                <p style="font-size:13px; color:var(--text-2); max-width:560px; margin:10px auto 0; line-height:1.6;">{{ $quiz->description }}</p>
            @endif
        </div>

        {{-- Questions List --}}
        <div style="padding:24px 28px; display:flex; flex-direction:column; gap:14px;">

            @forelse ($quiz->questions->sortBy('order') as $index => $question)

                <div style="background:var(--surface-2); border:1px solid var(--border-md); border-radius:var(--radius); padding:20px 22px; transition:border-color 0.15s;"
                     onmouseenter="this.style.borderColor='rgba(74,222,128,0.18)'"
                     onmouseleave="this.style.borderColor='var(--border-md)'">

                    {{-- Question Header --}}
                    <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:16px;">
                        <span style="flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:var(--accent-bg); font-size:12px; font-weight:700; color:var(--accent);" class="num">
                            {{ $index + 1 }}
                        </span>
                        <div style="flex:1;">
                            <p style="font-size:14px; font-weight:600; color:var(--text); line-height:1.6; margin-bottom:8px;">
                                {{ $question->question_text }}
                            </p>
                            <span class="chip num">{{ $question->points }} {{ $question->points == 1 ? 'pt' : 'pts' }}</span>
                        </div>
                    </div>

                    {{-- Media --}}
                    @if ($question->image_path || $question->audio_path || $question->video_path)
                        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px; padding-left:42px;">
                            @if ($question->image_path)
                                <img src="{{ asset('storage/' . $question->image_path) }}"
                                     style="max-height:200px; max-width:340px; object-fit:contain; border-radius:var(--radius-sm); border:1px solid var(--border); background:var(--surface-3);"
                                     alt="Question Image">
                            @endif
                            @if ($question->audio_path)
                                <audio controls style="width:min(340px,100%); border-radius:var(--radius-sm);">
                                    <source src="{{ asset('storage/' . $question->audio_path) }}">
                                </audio>
                            @endif
                            @if ($question->video_path)
                                <video controls style="max-height:220px; max-width:340px; border-radius:var(--radius-sm); border:1px solid var(--border); background:var(--surface-3);">
                                    <source src="{{ asset('storage/' . $question->video_path) }}">
                                </video>
                            @endif
                        </div>
                    @endif

                    {{-- Answer Area --}}
                    <div style="padding-left:42px;">

                        @if ($question->question_type === 'multiple_choice')
                            @php $letters = ['A', 'B', 'C', 'D', 'E', 'F']; @endphp
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                @foreach ($question->answerOptions->sortBy('order') as $i => $option)
                                    <div style="display:flex; align-items:flex-start; gap:10px; background:var(--surface-3); border:1px solid var(--border); border-radius:var(--radius-sm); padding:10px 14px;">
                                        <span style="flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:var(--surface-2); border:1px solid var(--border-md); font-size:11px; font-weight:700; color:var(--accent);">
                                            {{ $letters[$i] ?? chr(65 + $i) }}
                                        </span>
                                        <div style="display:flex; flex-direction:column; gap:6px;">
                                            <span style="font-size:13px; color:var(--text-2); line-height:1.5;">{{ $option->option_text }}</span>
                                            @if ($option->image_path)
                                                <img src="{{ asset('storage/' . $option->image_path) }}"
                                                     style="max-height:100px; max-width:280px; object-fit:contain; border-radius:var(--radius-sm); border:1px solid var(--border);"
                                                     alt="Option Image">
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        @elseif ($question->question_type === 'true_false')
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <div style="background:var(--surface-3); border:1px solid var(--border); border-radius:var(--radius-sm); padding:10px 14px; font-size:13px; color:var(--text-2);">A. True</div>
                                <div style="background:var(--surface-3); border:1px solid var(--border); border-radius:var(--radius-sm); padding:10px 14px; font-size:13px; color:var(--text-2);">B. False</div>
                            </div>

                        @elseif ($question->question_type === 'identification')
                            <div style="background:var(--surface-3); border:1px dashed var(--border-md); border-radius:var(--radius-sm); padding:14px 18px; font-size:13px; color:var(--text-3);">
                                Answer: ___________________________
                            </div>

                        @elseif ($question->question_type === 'matching')
                            <div style="background:var(--surface-3); border:1px solid var(--border); border-radius:var(--radius-sm); padding:18px;">
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:18px;">
                                    <div>
                                        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:10px;">Column A</p>
                                        <ol style="list-style:decimal; padding-left:18px; display:flex; flex-direction:column; gap:8px;">
                                            @foreach ($question->answerOptions->sortBy('order') as $pair)
                                                <li style="font-size:13px; color:var(--text-2);">{{ $pair->option_text }}</li>
                                            @endforeach
                                        </ol>
                                    </div>
                                    <div>
                                        <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-3); margin-bottom:10px;">Column B</p>
                                        <ol style="list-style:upper-alpha; padding-left:18px; display:flex; flex-direction:column; gap:8px;">
                                            @foreach ($question->answerOptions->shuffle() as $pair)
                                                <li style="font-size:13px; color:var(--text-2);">{{ $pair->match_pair }}</li>
                                            @endforeach
                                        </ol>
                                    </div>
                                </div>
                                <div style="border-top:1px solid var(--border); padding-top:14px;">
                                    <p style="font-size:12px; font-weight:600; color:var(--text-2); margin-bottom:10px;">Answer:</p>
                                    <div style="display:flex; flex-wrap:wrap; gap:14px;">
                                        @foreach ($question->answerOptions->sortBy('order') as $i => $pair)
                                            <span style="font-size:13px; color:var(--text-3);" class="num">{{ $i + 1 }}. ______</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            @empty
                <div style="padding:64px 24px; text-align:center;">
                    <div style="display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:14px; background:var(--accent-bg); margin-bottom:16px;">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 5.75A2.75 2.75 0 014.75 3h10.5A2.75 2.75 0 0118 5.75v8.5A2.75 2.75 0 0115.25 17H4.75A2.75 2.75 0 012 14.25v-8.5zm2.75-1.25A1.25 1.25 0 003.5 5.75v8.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-8.5c0-.69-.56-1.25-1.25-1.25H4.75z" fill="var(--accent)"/>
                        </svg>
                    </div>
                    <p style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:6px;">No questions found</p>
                    <p style="font-size:12px; color:var(--text-2);">No questions have been added to this quiz yet.</p>
                </div>
            @endforelse

        </div>
    </div>

@endsection
