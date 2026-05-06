@extends('admin.layouts.app')

@section('content')
@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

<div class="space-y-6">

@if($isSuperAdmin)
    {{-- ===== SUPERADMIN HERO ===== --}}
    <div class="relative overflow-hidden rounded-2xl p-7 text-white"
         style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%); border: 1px solid rgba(99,102,241,0.3);">
        <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full" style="background: radial-gradient(circle, rgba(99,102,241,0.2), transparent 70%);"></div>
        <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full" style="background: radial-gradient(circle, rgba(139,92,246,0.15), transparent 70%);"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest" style="color: #a5b4fc;">SuperAdmin Panel</p>
                <h2 class="mt-1.5 text-3xl font-bold text-white sm:text-4xl">Menu Dashboard</h2>
                <p class="mt-2 max-w-2xl text-sm" style="color: #c7d2fe;">
                    Manage teacher, student, and admin accounts, review account details, and oversee the full Quizzard management system.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5 shrink-0">
                <div class="rounded-xl px-4 py-3" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color: #a5b4fc;">Teachers</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $stats['teachers_count'] }}</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color: #a5b4fc;">Students</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $stats['students_count'] }}</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color: #a5b4fc;">Admins</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $stats['admins_count'] }}</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color: #a5b4fc;">Activated</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $stats['activated_count'] }}</p>
                </div>
                <div class="rounded-xl px-4 py-3" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);">
                    <p class="text-xs" style="color: #a5b4fc;">Deactivated</p>
                    <p class="mt-1 text-xl font-bold text-white">{{ $stats['deactivated_count'] }}</p>
                </div>
            </div>
        </div>
    </div>

@else
    {{-- ===== ADMIN HERO — original unchanged ===== --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-blue-200">Admin Panel</p>
                <h2 class="mt-2 text-3xl font-bold sm:text-4xl">Menu Dashboard</h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-200 sm:text-base">
                    Manage teacher and student accounts, review account details, and maintain the Quizzard management system.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Teachers</p>
                    <p class="mt-1 text-2xl font-bold">{{ $stats['teachers_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Students</p>
                    <p class="mt-1 text-2xl font-bold">{{ $stats['students_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Activated</p>
                    <p class="mt-1 text-2xl font-bold">{{ $stats['activated_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Deactivated</p>
                    <p class="mt-1 text-2xl font-bold">{{ $stats['deactivated_count'] }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

    {{-- ===== ALERTS ===== --}}
    @if(session('success'))
        @if($isSuperAdmin)
            <div class="rounded-2xl px-5 py-4" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full" style="background: rgba(16,185,129,0.15); color: #34d399;">✓</div>
                    <div>
                        <p class="font-semibold" style="color: #34d399;">Success</p>
                        <p class="text-sm" style="color: #6ee7b7;">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</div>
                    <div>
                        <p class="font-semibold text-emerald-800">Success</p>
                        <p class="text-sm text-emerald-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 shadow-sm">
            <p class="mb-2 font-semibold text-red-800">Please fix the following:</p>
            <ul class="ml-5 list-disc text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== MAIN CARD ===== --}}
@if($isSuperAdmin)
    <div class="rounded-2xl p-6 shadow-lg" style="background: #161b27; border: 1px solid rgba(255,255,255,0.06);">
@else
    <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
@endif

        @if(!$selectedUser)
        <div class="space-y-4">

            {{-- Role tabs --}}
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-3">

                    @if($isSuperAdmin)
                        <a href="{{ route('admin.dashboard', ['type' => 'teacher', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                           class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                           style="{{ $type === 'teacher' ? 'background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.4);' : 'background: rgba(255,255,255,0.04); color: #64748b; border: 1px solid rgba(255,255,255,0.06);' }}">
                            Teachers
                        </a>
                        <a href="{{ route('admin.dashboard', ['type' => 'student', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                           class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                           style="{{ $type === 'student' ? 'background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.4);' : 'background: rgba(255,255,255,0.04); color: #64748b; border: 1px solid rgba(255,255,255,0.06);' }}">
                            Students
                        </a>
                        <a href="{{ route('admin.dashboard', ['type' => 'admin', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                           class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                           style="{{ $type === 'admin' ? 'background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.4);' : 'background: rgba(255,255,255,0.04); color: #64748b; border: 1px solid rgba(255,255,255,0.06);' }}">
                            Admin Management
                        </a>
                    @else
                        <a href="{{ route('admin.dashboard', ['type' => 'teacher', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                           class="rounded-xl px-5 py-2.5 text-sm font-semibold transition {{ $type === 'teacher' ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Teachers
                        </a>
                        <a href="{{ route('admin.dashboard', ['type' => 'student', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                           class="rounded-xl px-5 py-2.5 text-sm font-semibold transition {{ $type === 'student' ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Students
                        </a>
                    @endif
                </div>

                <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
                    @if($isSuperAdmin)
                        <select id="filterBy" class="sa-select rounded-lg border px-4 py-2.5 text-sm">
                    @else
                        <select id="filterBy" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    @endif
                            <option value="all">All Fields</option>
                            <option value="first_name">First Name</option>
                            <option value="middle_initial">Middle Initial</option>
                            <option value="surname">Surname</option>
                        </select>

                    @if($isSuperAdmin)
                        <input type="text" id="searchInput" value="{{ $search }}" placeholder="Search by name or email"
                               class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none transition xl:w-72"
                               style="background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); color: #e2e8f0;">
                    @else
                        <input type="text" id="searchInput" value="{{ $search }}" placeholder="Search by name or email"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 xl:w-80">
                    @endif
                </div>
            </div>

            {{-- Status buttons + Create --}}
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap gap-3">
                    @if($isSuperAdmin)
                        @foreach([['all','All Status'],['pending','Pending'],['active','Active'],['deactivated','Deactivated']] as [$val,$label])
                        <a href="{{ route('admin.dashboard', ['type' => $type, 'search' => $search, 'filter_by' => $filterBy, 'status' => $val]) }}"
                           class="rounded-lg border px-4 py-2 text-sm font-semibold transition"
                           style="{{ $status === $val
                               ? 'background: rgba(99,102,241,0.2); color: #a5b4fc; border-color: rgba(99,102,241,0.4);'
                               : 'background: rgba(255,255,255,0.03); color: #64748b; border-color: rgba(255,255,255,0.06);' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    @else
                        @php $statusButtonBase = 'rounded-xl border px-5 py-2.5 text-sm font-semibold transition'; @endphp
                        <a href="{{ route('admin.dashboard', ['type' => $type, 'search' => $search, 'filter_by' => $filterBy, 'status' => 'all']) }}"
                           class="{{ $statusButtonBase }} {{ $status === 'all' ? 'border-slate-900 bg-slate-900 text-white shadow-md' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100' }}">
                            All Status
                        </a>
                        <a href="{{ route('admin.dashboard', ['type' => $type, 'search' => $search, 'filter_by' => $filterBy, 'status' => 'pending']) }}"
                           class="{{ $statusButtonBase }} {{ $status === 'pending' ? 'border-amber-500 bg-amber-500 text-white shadow-md' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100' }}">
                            Pending
                        </a>
                        <a href="{{ route('admin.dashboard', ['type' => $type, 'search' => $search, 'filter_by' => $filterBy, 'status' => 'active']) }}"
                           class="{{ $statusButtonBase }} {{ $status === 'active' ? 'border-emerald-600 bg-emerald-600 text-white shadow-md' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100' }}">
                            Active
                        </a>
                        <a href="{{ route('admin.dashboard', ['type' => $type, 'search' => $search, 'filter_by' => $filterBy, 'status' => 'deactivated']) }}"
                           class="{{ $statusButtonBase }} {{ $status === 'deactivated' ? 'border-red-600 bg-red-600 text-white shadow-md' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100' }}">
                            Deactivated
                        </a>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3">
                    @if($type === 'teacher')
                        @if($isSuperAdmin)
                            <button type="button" id="btnCreateTeacher"
                                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition"
                                    style="background: #6366f1;"
                                    onmouseover="this.style.background='#4f46e5';" onmouseout="this.style.background='#6366f1';">
                                Create Teacher
                            </button>
                        @else
                            <button type="button" id="btnCreateTeacher"
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                Create Teacher
                            </button>
                        @endif
                    @endif

                    @if($type === 'student')
                        @if($isSuperAdmin)
                            <button type="button" id="btnCreateStudent"
                                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition"
                                    style="background: #6366f1;"
                                    onmouseover="this.style.background='#4f46e5';" onmouseout="this.style.background='#6366f1';">
                                Create Student
                            </button>
                        @else
                            <button type="button" id="btnCreateStudent"
                                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                Create Student
                            </button>
                        @endif
                    @endif

                    @if($type === 'admin' && $isSuperAdmin)
                        <button type="button" id="btnCreateAdmin"
                                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition"
                                style="background: #6366f1;"
                                onmouseover="this.style.background='#4f46e5';" onmouseout="this.style.background='#6366f1';">
                            Create Admin Account
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Table container --}}
        @if($isSuperAdmin)
            <div class="{{ $selectedUser ? 'mt-0' : 'mt-5' }} overflow-hidden rounded-xl" style="border: 1px solid rgba(255,255,255,0.06);">
                <div id="usersTableContainer" style="background: #0f1117;">
        @else
            <div class="{{ $selectedUser ? 'mt-0' : 'mt-6' }} overflow-hidden rounded-2xl border border-slate-200">
                <div id="usersTableContainer" class="bg-white">
        @endif

                @if($selectedUser)

                @if($isSuperAdmin)
                    <div class="flex justify-center px-4 py-8 sm:px-6" style="background: #0f1117;">
                        <div class="w-full max-w-4xl">
                            <div class="overflow-hidden rounded-2xl shadow-xl" style="border: 1px solid rgba(255,255,255,0.08); background: #161b27;">
                                <div class="px-6 py-8 sm:px-8 sm:py-9" style="background: linear-gradient(135deg, #1e1b4b, #312e81, #1e1b4b);">
                                    <div class="flex flex-col gap-6">
                                        <div class="flex items-center justify-between gap-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.22em]" style="color: #a5b4fc;">Account Information</p>
                                            <a href="{{ route('admin.dashboard', ['type' => $type, 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                                               class="js-dashboard-back-link inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold shadow-sm transition"
                                               style="background: rgba(255,255,255,0.08); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.12);"
                                               onmouseover="this.style.background='rgba(255,255,255,0.14)';"
                                               onmouseout="this.style.background='rgba(255,255,255,0.08)';">
                                                ← Back to List
                                            </a>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $selectedUser->name }}</h3>
                                            <p class="mt-3 text-sm leading-6 sm:text-base" style="color: #a5b4fc;">
                                                Detailed profile view for this {{ $selectedUser->role }} account.
                                            </p>
                                            <div class="mt-5 flex flex-wrap gap-3">
                                                <span class="inline-flex rounded-full px-4 py-2.5 text-sm font-semibold capitalize"
                                                      style="background: rgba(99,102,241,0.2); color: #c7d2fe; border: 1px solid rgba(99,102,241,0.3);">
                                                    Role: {{ $selectedUser->role }}
                                                </span>
                                                @php
                                                    $heroStatusClasses = match($selectedUser->status) {
                                                        'active' => 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/30',
                                                        'deactivated' => 'bg-red-400/20 text-red-200 ring-1 ring-red-300/30',
                                                        default => 'bg-amber-400/20 text-amber-200 ring-1 ring-amber-300/30',
                                                    };
                                                @endphp
                                                <span class="inline-flex rounded-full px-4 py-2.5 text-sm font-semibold capitalize backdrop-blur {{ $heroStatusClasses }}">
                                                    Status: {{ $selectedUser->status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-7 sm:p-8" style="background: #161b27;">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        @foreach([['User ID','#'.$selectedUser->id],['Email Address',$selectedUser->email],['First Name',$selectedUser->first_name??'-'],['Surname',$selectedUser->surname??'-'],['Middle Initial',$selectedUser->middle_initial??'-'],['Password','Hidden for security'],['Created At',optional($selectedUser->created_at)->format('F d, Y h:i A')??'-'],['Updated At',optional($selectedUser->updated_at)->format('F d, Y h:i A')??'-']] as [$label,$value])
                                        <div class="rounded-xl p-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                                            <p class="text-xs font-semibold uppercase tracking-wide" style="color: #475569;">{{ $label }}</p>
                                            <p class="mt-2 text-base font-bold break-all" style="color: #e2e8f0;">{{ $value }}</p>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                        <button type="button"
                                                class="btn-edit-user inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-sm transition"
                                                style="background: #6366f1;"
                                                onmouseover="this.style.background='#4f46e5';" onmouseout="this.style.background='#6366f1';"
                                                data-id="{{ $selectedUser->id }}" data-first-name="{{ $selectedUser->first_name }}" data-middle-initial="{{ $selectedUser->middle_initial }}" data-surname="{{ $selectedUser->surname }}" data-email="{{ $selectedUser->email }}" data-role="{{ $selectedUser->role }}" data-status="{{ $selectedUser->status }}" data-update-url="{{ route('admin.users.update', $selectedUser) }}">
                                            Update Account
                                        </button>
                                        <button type="button"
                                                class="btn-delete-user inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                                                data-name="{{ $selectedUser->name }}" data-delete-url="{{ route('admin.users.destroy', $selectedUser) }}">
                                            Delete Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @else
                    <div class="flex justify-center bg-slate-50 px-4 py-8 sm:px-6">
                        <div class="w-full max-w-4xl">
                            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">
                                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 px-6 py-8 text-white sm:px-8 sm:py-9">
                                    <div class="flex flex-col gap-6">
                                        <div class="flex items-center justify-between gap-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-200">Account Information</p>
                                            <a href="{{ route('admin.dashboard', ['type' => $type, 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                                               class="js-dashboard-back-link inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-100">
                                                ← Back to List
                                            </a>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $selectedUser->name }}</h3>
                                            <p class="mt-3 text-sm leading-6 text-slate-200 sm:text-base">Detailed profile view for this {{ $selectedUser->role }} account.</p>
                                            <div class="mt-5 flex flex-wrap gap-3">
                                                <span class="inline-flex rounded-full bg-white/15 px-4 py-2.5 text-sm font-semibold capitalize text-white backdrop-blur">
                                                    Role: {{ $selectedUser->role }}
                                                </span>
                                                @php
                                                    $heroStatusClasses = match($selectedUser->status) {
                                                        'active' => 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/30',
                                                        'deactivated' => 'bg-red-400/20 text-red-200 ring-1 ring-red-300/30',
                                                        default => 'bg-amber-400/20 text-amber-200 ring-1 ring-amber-300/30',
                                                    };
                                                @endphp
                                                <span class="inline-flex rounded-full px-4 py-2.5 text-sm font-semibold capitalize backdrop-blur {{ $heroStatusClasses }}">
                                                    Status: {{ $selectedUser->status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-7 sm:p-8">
                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">User ID</p><p class="mt-2 text-lg font-bold text-slate-900">#{{ $selectedUser->id }}</p></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email Address</p><p class="mt-2 break-all text-lg font-bold text-slate-900">{{ $selectedUser->email }}</p></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">First Name</p><p class="mt-2 text-lg font-bold text-slate-900">{{ $selectedUser->first_name ?? '-' }}</p></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Surname</p><p class="mt-2 text-lg font-bold text-slate-900">{{ $selectedUser->surname ?? '-' }}</p></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Middle Initial</p><p class="mt-2 text-lg font-bold text-slate-900">{{ $selectedUser->middle_initial ?? '-' }}</p></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Password</p><p class="mt-2 text-base font-semibold text-slate-700">Hidden for security</p></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created At</p><p class="mt-2 text-base font-semibold text-slate-900">{{ optional($selectedUser->created_at)->format('F d, Y h:i A') ?? '-' }}</p></div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Updated At</p><p class="mt-2 text-base font-semibold text-slate-900">{{ optional($selectedUser->updated_at)->format('F d, Y h:i A') ?? '-' }}</p></div>
                                    </div>
                                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                        <button type="button" class="btn-edit-user inline-flex items-center justify-center rounded-xl bg-amber-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600"
                                                data-id="{{ $selectedUser->id }}" data-first-name="{{ $selectedUser->first_name }}" data-middle-initial="{{ $selectedUser->middle_initial }}" data-surname="{{ $selectedUser->surname }}" data-email="{{ $selectedUser->email }}" data-role="{{ $selectedUser->role }}" data-status="{{ $selectedUser->status }}" data-update-url="{{ route('admin.users.update', $selectedUser) }}">
                                            Update Account
                                        </button>
                                        <button type="button" class="btn-delete-user inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                                                data-name="{{ $selectedUser->name }}" data-delete-url="{{ route('admin.users.destroy', $selectedUser) }}">
                                            Delete Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @else
                    @include('admin.dashboard.partials.users_table', ['users' => $users, 'type' => $type, 'isSuperAdmin' => $isSuperAdmin])
                @endif
                </div>
            </div>
        </div>

    {{-- Row loader --}}
    @if($isSuperAdmin)
        <div id="rowNavigationLoader" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-950/55 backdrop-blur-sm">
            <div class="flex flex-col items-center rounded-2xl px-8 py-7 shadow-2xl" style="background: #161b27; border: 1px solid rgba(255,255,255,0.08);">
                <div class="h-12 w-12 animate-spin rounded-full" style="border: 3px solid rgba(99,102,241,0.2); border-top-color: #6366f1;"></div>
                <p class="mt-4 text-sm font-semibold" style="color: #e2e8f0;">Loading account details...</p>
                <p class="mt-1 text-xs" style="color: #475569;">Please wait</p>
            </div>
        </div>
    @else
        <div id="rowNavigationLoader" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-950/55 backdrop-blur-sm">
            <div class="flex flex-col items-center rounded-3xl bg-white px-8 py-7 shadow-2xl ring-1 ring-slate-200">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-blue-200 border-t-blue-700"></div>
                <p class="mt-4 text-sm font-semibold text-slate-800">Loading account details...</p>
                <p class="mt-1 text-xs text-slate-500">Please wait</p>
            </div>
        </div>
    @endif

</div>
@endsection

@push('modals')
    {{-- CREATE MODAL --}}
    <div id="createModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
        @if($isSuperAdmin)
        <div class="relative w-full max-w-xl rounded-2xl p-6 shadow-2xl" style="background: #161b27; border: 1px solid rgba(255,255,255,0.08);">
            <button type="button" class="close-modal absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full text-lg transition" style="background: rgba(255,255,255,0.06); color: #94a3b8;">&times;</button>
            <div class="mb-5">
                <h3 class="text-xl font-bold" id="createModalTitle" style="color: #e2e8f0;">Create Account</h3>
                <p class="mt-1 text-sm" style="color: #475569;">Fill in the account details below.</p>
            </div>
        @else
        <div class="relative w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl">
            <button type="button" class="close-modal absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 hover:text-slate-700">&times;</button>
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-slate-900" id="createModalTitle">Create Account</h3>
                <p class="mt-1 text-sm text-slate-500">Fill in the account details below.</p>
            </div>
        @endif
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>First Name</label>
                        <input type="text" name="first_name" id="createFirstName" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Middle Initial</label>
                        <input type="text" name="middle_initial" id="createMiddleInitial" maxlength="1" placeholder="Optional" class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Surname</label>
                        <input type="text" name="surname" id="createSurname" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Email</label>
                    <input type="email" name="email" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Role</label>
                        <select name="role" id="createRole" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? 'sa-select' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}">
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                            @if($isSuperAdmin)<option value="admin">Admin</option>@endif
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Status</label>
                        <select name="status" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? 'sa-select' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}">
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="deactivated">Deactivated</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Password</label>
                    <input type="password" name="password" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Confirm Password</label>
                    <input type="password" name="password_confirmation" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="close-modal rounded-xl px-5 py-2.5 font-semibold transition {{ $isSuperAdmin ? '' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.05);color:#94a3b8;border:1px solid rgba(255,255,255,0.08);" @endif>Cancel</button>
                    <button type="submit" id="createSubmitBtn" class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 font-semibold text-white transition disabled:opacity-60 disabled:cursor-not-allowed {{ $isSuperAdmin ? '' : 'bg-emerald-600 hover:bg-emerald-700' }}" @if($isSuperAdmin) style="background:#6366f1;" onmouseover="this.style.background='#4f46e5';" onmouseout="this.style.background='#6366f1';" @endif>
                        <span class="create-spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span class="create-label">Create</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 py-8 px-4 backdrop-blur-sm">
        @if($isSuperAdmin)
        <div class="relative w-full max-w-xl max-h-[calc(100vh-4rem)] overflow-y-auto rounded-2xl p-6 shadow-2xl" style="background: #161b27; border: 1px solid rgba(255,255,255,0.08);">
            <div id="editModalSpinner" class="hidden absolute inset-0 z-10 flex items-center justify-center rounded-2xl" style="background: rgba(22,27,39,0.85);">
                <div class="h-10 w-10 animate-spin rounded-full" style="border: 3px solid rgba(99,102,241,0.2); border-top-color: #6366f1;"></div>
            </div>
            <button type="button" class="close-modal absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full text-lg transition" style="background: rgba(255,255,255,0.06); color: #94a3b8;">&times;</button>
            <div class="mb-5">
                <h3 class="text-xl font-bold" style="color: #e2e8f0;">Update Account</h3>
                <p class="mt-1 text-sm" style="color: #475569;">Edit user details and save changes.</p>
            </div>
        @else
        <div class="relative w-full max-w-xl max-h-[calc(100vh-4rem)] overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
            <div id="editModalSpinner" class="hidden absolute inset-0 z-10 flex items-center justify-center bg-white/80 rounded-3xl">
                <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-400 border-t-transparent"></div>
            </div>
            <button type="button" class="close-modal absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 hover:text-slate-700">&times;</button>
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-slate-900">Update Account</h3>
                <p class="mt-1 text-sm text-slate-500">Edit user details and save changes.</p>
            </div>
        @endif
            <form method="POST" id="editForm" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>First Name</label>
                        <input type="text" name="first_name" id="editFirstName" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Middle Initial</label>
                        <input type="text" name="middle_initial" id="editMiddleInitial" maxlength="1" class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Surname</label>
                        <input type="text" name="surname" id="editSurname" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Email</label>
                    <input type="email" name="email" id="editEmail" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Role</label>
                        <select name="role" id="editRole" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? 'sa-select' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}">
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                            @if($isSuperAdmin)<option value="admin">Admin</option>@endif
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Status</label>
                        <select name="status" id="editStatus" required class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? 'sa-select' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}">
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="deactivated">Deactivated</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>New Password</label>
                    <input type="password" name="password" id="editPassword" class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium {{ $isSuperAdmin ? '' : 'text-slate-700' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="editPasswordConfirmation" class="w-full rounded-xl border px-4 py-2.5 outline-none {{ $isSuperAdmin ? '' : 'border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;" @endif>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="close-modal rounded-xl px-5 py-2.5 font-semibold transition {{ $isSuperAdmin ? '' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.05);color:#94a3b8;border:1px solid rgba(255,255,255,0.08);" @endif>Cancel</button>
                    <button id="updateUserBtn" type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 font-semibold text-white transition disabled:opacity-60 disabled:cursor-not-allowed {{ $isSuperAdmin ? '' : 'bg-amber-500 hover:bg-amber-600' }}" @if($isSuperAdmin) style="background:#6366f1;" onmouseover="if(!this.disabled){this.style.background='#4f46e5';}" onmouseout="if(!this.disabled){this.style.background='#6366f1';}" @endif>
                        <span class="update-spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span class="update-label">Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- DELETE MODAL --}}
    <div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
        @if($isSuperAdmin)
        <div class="relative w-full max-w-lg rounded-2xl p-6 shadow-2xl" style="background: #161b27; border: 1px solid rgba(239,68,68,0.2);">
            <button type="button" class="close-modal absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full text-lg" style="background: rgba(255,255,255,0.06); color: #94a3b8;">&times;</button>
        @else
        <div class="relative w-full max-w-lg rounded-3xl border border-red-200 bg-white p-6 shadow-2xl">
            <button type="button" class="close-modal absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 hover:text-slate-700">&times;</button>
        @endif
            <div class="mb-5">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-xl text-red-600">!</div>
                <h3 class="text-2xl font-bold text-red-500">Delete Account</h3>
                <p class="mt-2 text-sm {{ $isSuperAdmin ? '' : 'text-slate-600' }}" @if($isSuperAdmin) style="color:#94a3b8;" @endif>
                    Are you sure you want to delete <strong id="deleteUserName" class="{{ $isSuperAdmin ? '' : 'text-slate-900' }}" @if($isSuperAdmin) style="color:#e2e8f0;" @endif></strong>?
                </p>
                <p class="mt-2 text-sm text-red-500">This action cannot be undone.</p>
            </div>
            <form method="POST" id="deleteForm" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" class="close-modal rounded-xl px-5 py-2.5 font-semibold transition {{ $isSuperAdmin ? '' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}" @if($isSuperAdmin) style="background:rgba(255,255,255,0.05);color:#94a3b8;border:1px solid rgba(255,255,255,0.08);" @endif>Cancel</button>
                <button type="submit" id="deleteSubmitBtn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white transition hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span class="delete-spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    <span class="delete-label">Delete</span>
                </button>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
<script>
    const dashboardUrl    = "{{ route('admin.dashboard') }}";
    const currentType     = "{{ $type }}";
    const currentStatus   = "{{ $status }}";
    const searchInput     = document.getElementById('searchInput');
    const filterBySelect  = document.getElementById('filterBy');
    const usersTableContainer = document.getElementById('usersTableContainer');
    const rowNavigationLoader = document.getElementById('rowNavigationLoader');

    let isRowNavigating  = false;
    let currentFilterBy  = @json($filterBy ?? 'all');
    if (filterBySelect) { filterBySelect.value = currentFilterBy; }

    const createModal = document.getElementById('createModal');
    const editModal   = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    const allModals   = [createModal, editModal, deleteModal];

    // ── Modal helpers ────────────────────────────────────────────────
    function openModal(modal) {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        const hasVisible = allModals.some(m => m && !m.classList.contains('hidden'));
        if (!hasVisible) { document.body.classList.remove('overflow-hidden'); }
    }
    function closeAllModals() { allModals.forEach(m => closeModal(m)); }

    // ── Button loading helpers ───────────────────────────────────────
    function setButtonLoading(btn, spinnerClass, labelClass, labelText) {
        if (!btn) return;
        btn.disabled = true;
        const sp = btn.querySelector(spinnerClass);
        const lb = btn.querySelector(labelClass);
        if (sp) sp.classList.remove('hidden');
        if (lb && labelText) lb.textContent = labelText;
    }
    function resetButtonLoading(btn, spinnerClass, labelClass, labelText) {
        if (!btn) return;
        btn.disabled = false;
        const sp = btn.querySelector(spinnerClass);
        const lb = btn.querySelector(labelClass);
        if (sp) sp.classList.add('hidden');
        if (lb && labelText) lb.textContent = labelText;
    }

    function showRowNavigationLoader() {
        if (!rowNavigationLoader || isRowNavigating) return;
        isRowNavigating = true;
        rowNavigationLoader.classList.remove('hidden');
        rowNavigationLoader.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    // ── Close on backdrop / Escape / close-modal buttons ────────────
    allModals.forEach(modal => {
        if (!modal) return;
        modal.addEventListener('click', function (e) {
            if (e.target === modal) { closeModal(modal); }
        });
    });
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = btn.closest('#createModal, #editModal, #deleteModal');
            closeModal(modal);
            // reset update btn if edit modal closed mid-flight
            const updateBtn = document.getElementById('updateUserBtn');
            resetButtonLoading(updateBtn, '.update-spinner', '.update-label', 'Update');
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeAllModals(); }
    });

    // ── Create buttons ───────────────────────────────────────────────
    const btnCreateTeacher = document.getElementById('btnCreateTeacher');
    const btnCreateStudent = document.getElementById('btnCreateStudent');
    const btnCreateAdmin   = document.getElementById('btnCreateAdmin');
    if (btnCreateTeacher) {
        btnCreateTeacher.addEventListener('click', function () {
            document.getElementById('createRole').value = 'teacher';
            document.getElementById('createModalTitle').textContent = 'Create Teacher Account';
            openModal(createModal);
        });
    }
    if (btnCreateStudent) {
        btnCreateStudent.addEventListener('click', function () {
            document.getElementById('createRole').value = 'student';
            document.getElementById('createModalTitle').textContent = 'Create Student Account';
            openModal(createModal);
        });
    }
    if (btnCreateAdmin) {
        btnCreateAdmin.addEventListener('click', function () {
            document.getElementById('createRole').value = 'admin';
            document.getElementById('createModalTitle').textContent = 'Create Admin Account';
            openModal(createModal);
        });
    }

    // ── Create form submit ───────────────────────────────────────────
    const createForm = document.querySelector('#createModal form');
    if (createForm) {
        createForm.addEventListener('submit', function () {
            const btn = document.getElementById('createSubmitBtn');
            setButtonLoading(btn, '.create-spinner', '.create-label', 'Creating...');
        });
    }

    // ── Search / filter ──────────────────────────────────────────────
    if (filterBySelect) {
        filterBySelect.addEventListener('change', function () {
            currentFilterBy = this.value;
            performSearch();
        });
    }
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 250);
        });
    }
    function performSearch() {
        const search = searchInput ? searchInput.value : '';
        const url = `${dashboardUrl}?type=${encodeURIComponent(currentType)}&status=${encodeURIComponent(currentStatus)}&search=${encodeURIComponent(search)}&filter_by=${encodeURIComponent(currentFilterBy)}`;
        loadUsers(url);
    }

    // ── MAIN DELEGATED CLICK HANDLER ─────────────────────────────────
    // ORDER MATTERS: check edit/delete FIRST, then row navigation, then pagination.
    document.addEventListener('click', async function (e) {

        // 1. Back link
        const backLink = e.target.closest('.js-dashboard-back-link');
        if (backLink) {
            e.preventDefault();
            const backUrl = backLink.getAttribute('href');
            if (backUrl && !isRowNavigating) {
                showRowNavigationLoader();
                window.location.href = backUrl;
            }
            return;
        }

        // 2. Edit button — MUST be before row check
        const editBtn = e.target.closest('.btn-edit-user');
        if (editBtn) {
            e.stopPropagation();
            document.getElementById('editFirstName').value         = editBtn.dataset.firstName    ?? '';
            document.getElementById('editMiddleInitial').value     = editBtn.dataset.middleInitial ?? '';
            document.getElementById('editSurname').value           = editBtn.dataset.surname       ?? '';
            document.getElementById('editEmail').value             = editBtn.dataset.email         ?? '';
            document.getElementById('editRole').value              = editBtn.dataset.role          ?? '';
            document.getElementById('editStatus').value            = editBtn.dataset.status        ?? '';
            document.getElementById('editPassword').value          = '';
            document.getElementById('editPasswordConfirmation').value = '';
            document.getElementById('editForm').action             = editBtn.dataset.updateUrl     ?? '';
            openModal(editModal);
            return;
        }

        // 3. Delete button — MUST be before row check
        const deleteBtn = e.target.closest('.btn-delete-user');
        if (deleteBtn) {
            e.stopPropagation();
            document.getElementById('deleteUserName').textContent = deleteBtn.dataset.name        ?? '';
            document.getElementById('deleteForm').action          = deleteBtn.dataset.deleteUrl   ?? '';
            openModal(deleteModal);
            return;
        }

        // 4. Row navigation (only if not clicking a button inside)
        const row = e.target.closest('tr[data-view-url]');
        if (row) {
            const viewUrl = row.dataset.viewUrl;
            if (viewUrl && !isRowNavigating) {
                showRowNavigationLoader();
                window.location.href = viewUrl;
            }
            return;
        }

        // 5. Pagination
        const paginationLink = e.target.closest('#usersPagination a');
        if (paginationLink) {
            e.preventDefault();
            const url = paginationLink.getAttribute('href');
            if (url) { loadUsers(url); }
        }
    });

    // ── Edit form submit ─────────────────────────────────────────────
    document.getElementById('editForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const submitBtn = document.getElementById('updateUserBtn');
        setButtonLoading(submitBtn, '.update-spinner', '.update-label', 'Updating...');
        try {
            const formData = new FormData(this);
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (response.ok) {
                closeModal(editModal);
                await loadUsers(dashboardUrl + window.location.search);
            } else {
                alert(data.message || 'Error updating user');
                resetButtonLoading(submitBtn, '.update-spinner', '.update-label', 'Update');
            }
        } catch (error) {
            console.error(error);
            alert('Error updating user');
            resetButtonLoading(submitBtn, '.update-spinner', '.update-label', 'Update');
        }
    });

    // ── Delete form submit ───────────────────────────────────────────
    document.getElementById('deleteForm').addEventListener('submit', function () {
        const btn = document.getElementById('deleteSubmitBtn');
        setButtonLoading(btn, '.delete-spinner', '.delete-label', 'Deleting...');
    });

    // ── Load users via AJAX ──────────────────────────────────────────
    function userSkeleton() {
        return `<div class="py-12 text-center"><div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-slate-300 border-t-slate-600"></div><p class="mt-3 text-sm text-slate-600">Loading users...</p></div>`;
    }
    async function loadUsers(url) {
        try {
            usersTableContainer.innerHTML = userSkeleton();
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await response.json();
            usersTableContainer.innerHTML = data.html;
        } catch (error) {
            usersTableContainer.innerHTML = `<div class="py-8 text-center text-red-600">Failed to load users.</div>`;
            console.error(error);
        }
    }
</script>
@endpush
