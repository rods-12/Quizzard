@extends('teacher.layouts.app')
@section('title', 'Create Quiz')
@section('content')

{{-- Back Link --}}
<div style="margin-bottom:20px;">
    <a href="{{ route('teacher.quizzes.index') }}" class="btn btn-ghost btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
        <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M12 15l-5-5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Back to My Quizzes
    </a>
</div>

{{-- Page Header --}}
<div style="margin-bottom:28px;">
    <p style="font-size:10.5px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-bottom:6px;">Content</p>
    <h1 style="font-size:24px; font-weight:700; color:var(--text); letter-spacing:-0.03em; line-height:1.2; margin-bottom:6px;">Create New Quiz</h1>
    <p style="font-size:13px; color:var(--text-2);">Fill in the details below to set up your new quiz.</p>
</div>

{{-- Form Card --}}
<div style="max-width:680px;">
    <div class="card" style="overflow:hidden;">

        <div style="padding:18px 22px; border-bottom:1px solid var(--border);">
            <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">Details</p>
            <h2 style="font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px;">Quiz Information</h2>
            <p style="font-size:12px; color:var(--text-2);">You can add questions after the quiz is created.</p>
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

            <form action="{{ route('teacher.quizzes.store') }}" method="POST">
                @csrf

                {{-- Title --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                        Title <span style="color:var(--danger);">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           placeholder="e.g. Chapter 1 Quiz"
                           required
                           style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; box-sizing:border-box; transition:border-color 0.15s;"
                           onfocus="this.style.borderColor='var(--accent)'"
                           onblur="this.style.borderColor='var(--border-md)'">
                </div>

                {{-- Description --}}
                <div style="margin-bottom:26px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                        Description
                    </label>
                    <textarea name="description" rows="4"
                              placeholder="Optional description..."
                              style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; resize:vertical; box-sizing:border-box; transition:border-color 0.15s;"
                              onfocus="this.style.borderColor='var(--accent)'"
                              onblur="this.style.borderColor='var(--border-md)'">{{ old('description') }}</textarea>
                </div>

                {{-- Actions --}}
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px; padding-top:4px; border-top:1px solid var(--border);">
                    <a href="{{ route('teacher.quizzes.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm">Create Quiz</button>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
