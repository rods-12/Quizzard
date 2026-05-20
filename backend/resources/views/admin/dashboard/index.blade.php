@extends('admin.layouts.app')
// index.blade.php//
@section('content')
@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

<div class="space-y-6">

    {{-- ===== HERO ===== --}}
    <div class="rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-blue-200">
                    {{ $isSuperAdmin ? 'SuperAdmin Panel' : 'Admin Panel' }}
                </p>
                <h2 class="mt-2 text-3xl font-bold sm:text-4xl">Menu Dashboard</h2>
                <p class="mt-2 max-w-2xl text-sm text-slate-200 sm:text-base">
                    @if($isSuperAdmin)
                        Manage teacher, student, and admin accounts, review account details, and oversee the full Quizzard management system.
                    @else
                        Manage teacher and student accounts, review account details, and maintain the Quizzard management system.
                    @endif
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 {{ $isSuperAdmin ? 'sm:grid-cols-5' : 'sm:grid-cols-4' }}">
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Teachers</p>
                    <p class="mt-1 text-2xl font-bold">{{ $stats['teachers_count'] }}</p>
                </div>
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Students</p>
                    <p class="mt-1 text-2xl font-bold">{{ $stats['students_count'] }}</p>
                </div>
                @if($isSuperAdmin)
                <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-xs uppercase tracking-wide text-slate-200">Admins</p>
                    <p class="mt-1 text-2xl font-bold">{{ $stats['admins_count'] }}</p>
                </div>
                @endif
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

    {{-- ===== ALERTS ===== --}}
    @if(session('success'))
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
    <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">

        @if(!$selectedUser)
        <div class="space-y-4">

            {{-- Role tabs --}}
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.dashboard', ['type' => 'teacher', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                       class="rounded-xl px-5 py-2.5 text-sm font-semibold transition {{ $type === 'teacher' ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        Teachers
                    </a>
                    <a href="{{ route('admin.dashboard', ['type' => 'student', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                       class="rounded-xl px-5 py-2.5 text-sm font-semibold transition {{ $type === 'student' ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        Students
                    </a>
                    @if($isSuperAdmin)
                        <a href="{{ route('admin.dashboard', ['type' => 'admin', 'search' => $search, 'filter_by' => $filterBy, 'status' => $status]) }}"
                           class="rounded-xl px-5 py-2.5 text-sm font-semibold transition {{ $type === 'admin' ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                            Admin Management
                        </a>
                    @endif
                </div>

                <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
                    <select id="filterBy" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <option value="all">All Fields</option>
                        <option value="first_name">First Name</option>
                        <option value="middle_initial">Middle Initial</option>
                        <option value="surname">Surname</option>
                    </select>
                    <input type="text" id="searchInput" value="{{ $search }}" placeholder="Search by name or email"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 xl:w-80">
                </div>
            </div>

            {{-- Status buttons + Create --}}
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap gap-3">
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
                </div>

                <div class="flex flex-wrap gap-3">
                    @if($type === 'teacher')
                        <button type="button" id="btnCreateTeacher"
                                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            Create Teacher
                        </button>
                    @endif

                    @if($type === 'student')
                        <button type="button" id="btnCreateStudent"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            Create Student
                        </button>
                    @endif

                    @if($type === 'admin' && $isSuperAdmin)
                        <button type="button" id="btnCreateAdmin"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                            Create Admin Account
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Table container --}}
        <div class="{{ $selectedUser ? 'mt-0' : 'mt-6' }} overflow-hidden rounded-2xl border border-slate-200">
            <div id="usersTableContainer" class="bg-white">

                @if($selectedUser)
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
                                            <h3 class="truncate text-3xl font-bold tracking-tight sm:text-4xl" title="{{ $selectedUser->name }}">{{ \Illuminate\Support\Str::limit($selectedUser->name, 30) }}</h3>
                                            <p class="mt-3 text-sm leading-6 text-slate-200 sm:text-base">
                                                Detailed profile view for this {{ $selectedUser->role }} account.
                                            </p>
                                            <div class="mt-5 flex flex-wrap gap-3">
                                                <span class="inline-flex rounded-full bg-white/15 px-4 py-2.5 text-sm font-semibold capitalize text-white backdrop-blur">
                                                    Role: {{ $selectedUser->role }}
                                                </span>
                                                @php
                                                    $heroStatusClasses = match($selectedUser->status) {
                                                        'active'      => 'bg-emerald-400/20 text-emerald-200 ring-1 ring-emerald-300/30',
                                                        'deactivated' => 'bg-red-400/20 text-red-200 ring-1 ring-red-300/30',
                                                        default       => 'bg-amber-400/20 text-amber-200 ring-1 ring-amber-300/30',
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
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">User ID</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-lg font-bold text-slate-900">#{{ $selectedUser->id }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email Address</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-lg font-bold text-slate-900">{{ $selectedUser->email }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">First Name</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-lg font-bold text-slate-900">{{ $selectedUser->first_name ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Surname</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-lg font-bold text-slate-900">{{ $selectedUser->surname ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Middle Initial</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-lg font-bold text-slate-900">{{ $selectedUser->middle_initial ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Password</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-base font-semibold text-slate-700">Hidden for security</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created At</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-base font-semibold text-slate-900">{{ optional($selectedUser->created_at)->format('F d, Y h:i A') ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Updated At</p>
                                            <div class="mt-2 overflow-x-auto">
                                                <p class="whitespace-nowrap text-base font-semibold text-slate-900">{{ optional($selectedUser->updated_at)->format('F d, Y h:i A') ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                        @php
                                            $selectedHasProtectedActivity = ($selectedUser->role === 'teacher' && (($selectedUser->quizzes_count ?? 0) > 0 || ($selectedUser->taught_classes_count ?? 0) > 0))
                                                || ($selectedUser->role === 'student' && (($selectedUser->enrolled_classes_count ?? 0) > 0 || ($selectedUser->quiz_attempts_count ?? 0) > 0));
                                            $selectedToggleRoute = $selectedUser->status === 'active'
                                                ? route('admin.activation.deactivate', $selectedUser)
                                                : route('admin.activation.activate', $selectedUser);
                                            $selectedToggleLabel = $selectedUser->status === 'active' ? 'Deactivate Account' : 'Activate Account';
                                            $selectedToggleClass = $selectedUser->status === 'active'
                                                ? 'bg-red-600 hover:bg-red-700'
                                                : 'bg-emerald-600 hover:bg-emerald-700';
                                        @endphp
                                        <button type="button"
                                                class="btn-edit-user inline-flex h-12 w-full items-center justify-center rounded-xl bg-amber-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 sm:w-48"
                                                data-id="{{ $selectedUser->id }}"
                                                data-first-name="{{ $selectedUser->first_name }}"
                                                data-middle-initial="{{ $selectedUser->middle_initial }}"
                                                data-surname="{{ $selectedUser->surname }}"
                                                data-email="{{ $selectedUser->email }}"
                                                data-role="{{ $selectedUser->role }}"
                                                data-status="{{ $selectedUser->status }}"
                                                data-update-url="{{ route('admin.users.update', $selectedUser) }}">
                                            Update Account
                                        </button>
                                        @if($selectedHasProtectedActivity)
                                            <form method="POST" action="{{ $selectedToggleRoute }}" class="w-full sm:w-48">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="btn-toggle-status inline-flex h-12 w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-sm transition {{ $selectedToggleClass }}">
                                                    {{ $selectedToggleLabel }}
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                    class="btn-delete-user inline-flex h-12 w-full items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 sm:w-48"
                                                    data-name="{{ $selectedUser->name }}"
                                                    data-delete-url="{{ route('admin.users.destroy', $selectedUser) }}">
                                                Delete Account
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @else
                    @include('admin.dashboard.partials.users_table', ['users' => $users, 'type' => $type, 'isSuperAdmin' => $isSuperAdmin])
                @endif

            </div>
        </div>
    </div>

    {{-- ===== ROW NAVIGATION LOADER ===== --}}
    <div id="rowNavigationLoader" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-950/55 backdrop-blur-sm">
        <div class="flex flex-col items-center rounded-3xl bg-white px-8 py-7 shadow-2xl ring-1 ring-slate-200">
            <div class="h-12 w-12 animate-spin rounded-full border-4 border-blue-200 border-t-blue-700"></div>
            <p class="mt-4 text-sm font-semibold text-slate-800">Loading account details...</p>
            <p class="mt-1 text-xs text-slate-500">Please wait</p>
        </div>
    </div>

</div>
@endsection

@push('modals')
    {{-- CREATE MODAL --}}
    <div id="createModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
        <div class="relative w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl">
            <button type="button" class="close-modal absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 hover:text-slate-700">&times;</button>
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-slate-900" id="createModalTitle">Create Account</h3>
                <p class="mt-1 text-sm text-slate-500">Fill in the account details below.</p>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4" id="createForm" data-no-loading="true">
                @csrf
                <input type="hidden" name="role" id="createRole" value="{{ $type }}">
                <div id="createFormErrors" class="form-error-summary hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">First Name</label>
                        <input type="text" name="first_name" id="createFirstName" required maxlength="50" pattern="^[A-Za-z\s\-.]+$" title="First name must not contain emojis or special characters."
                               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                               oninput="checkMaxLength(this, 'createFirstNameError', 50)">
                        <p id="createFirstNameError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                        <p data-field-error="first_name" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Middle Initial</label>
                        <input type="text" name="middle_initial" id="createMiddleInitial" maxlength="1" pattern="[A-Za-z]" title="Middle initial must be a single alphabet character." placeholder="Optional"
                               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <p data-field-error="middle_initial" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Surname</label>
                        <input type="text" name="surname" id="createSurname" required maxlength="50" pattern="^[A-Za-z\s\-.]+$" title="Last name must not contain emojis or special characters."
                               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                               oninput="checkMaxLength(this, 'createSurnameError', 50)">
                        <p id="createSurnameError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                        <p data-field-error="surname" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" id="createEmail" required maxlength="30"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                           oninput="checkMaxLength(this, 'createEmailError', 30)">
                    <p id="createEmailError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 30 characters allowed.</p>
                    <p data-field-error="email" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                    <select name="status" required
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="deactivated">Deactivated</option>
                    </select>
                    <p data-field-error="status" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" id="createPassword" required maxlength="50" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).{8,50}$" title="Password must have 8+ characters, uppercase, lowercase, number, and special character (@$!%*#?&)."
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                           oninput="checkMaxLength(this, 'createPasswordError', 50)">
                    <p id="createPasswordError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                    <p data-field-error="password" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    <p class="mt-1 text-xs text-slate-500">8+ characters with uppercase, lowercase, number, and special character (@$!%*#?&).</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="createPasswordConfirmation" required maxlength="50"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                           oninput="checkMaxLength(this, 'createPasswordConfirmError', 50)">
                    <p id="createPasswordConfirmError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                    <p data-field-error="password_confirmation" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="close-modal rounded-xl bg-slate-100 px-5 py-2.5 font-semibold text-slate-700 transition hover:bg-slate-200">
                        Cancel
                    </button>
                    <button type="submit" id="createSubmitBtn"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span class="create-spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span class="create-label">Create</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 py-8 px-4 backdrop-blur-sm">
        <div class="relative w-full max-w-xl max-h-[calc(100vh-4rem)] overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
            <div id="editModalSpinner" class="hidden absolute inset-0 z-10 flex items-center justify-center bg-white/80 rounded-3xl">
                <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-400 border-t-transparent"></div>
            </div>
            <button type="button" class="close-modal absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 hover:text-slate-700">&times;</button>
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-slate-900">Update Account</h3>
                <p class="mt-1 text-sm text-slate-500">Edit user details and save changes.</p>
            </div>
            <form method="POST" id="editForm" class="space-y-4" data-no-loading="true">
                @csrf @method('PUT')
                <div id="editFormErrors" class="form-error-summary hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"></div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">First Name</label>
                        <input type="text" name="first_name" id="editFirstName" required maxlength="50" pattern="^[A-Za-z\s\-.]+$" title="First name must not contain emojis or special characters."
                               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                               oninput="checkMaxLength(this, 'editFirstNameError', 50)">
                        <p id="editFirstNameError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                        <p data-field-error="first_name" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Middle Initial</label>
                        <input type="text" name="middle_initial" id="editMiddleInitial" maxlength="1" pattern="[A-Za-z]" title="Middle initial must be a single alphabet character."
                               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <p data-field-error="middle_initial" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Surname</label>
                        <input type="text" name="surname" id="editSurname" required maxlength="50" pattern="^[A-Za-z\s\-.]+$" title="Last name must not contain emojis or special characters."
                               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                               oninput="checkMaxLength(this, 'editSurnameError', 50)">
                        <p id="editSurnameError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                        <p data-field-error="surname" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" id="editEmail" required maxlength="30"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                           oninput="checkMaxLength(this, 'editEmailError', 30)">
                    <p id="editEmailError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 30 characters allowed.</p>
                    <p data-field-error="email" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                    <select name="status" id="editStatus" required
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="deactivated">Deactivated</option>
                    </select>
                    <p data-field-error="status" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">New Password</label>
                    <input type="password" name="password" id="editPassword" maxlength="50" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).{8,50}$" title="Password must have 8+ characters, uppercase, lowercase, number, and special character (@$!%*#?&)."
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                           oninput="checkMaxLength(this, 'editPasswordError', 50)">
                    <p id="editPasswordError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                    <p data-field-error="password" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                    <p class="mt-1 text-xs text-slate-500">Leave blank to keep current password. New password must include uppercase, lowercase, number, and special character (@$!%*#?&).</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="editPasswordConfirmation" maxlength="50"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                           oninput="checkMaxLength(this, 'editPasswordConfirmError', 50)">
                    <p id="editPasswordConfirmError" class="mt-1 hidden text-xs font-medium text-red-500">Maximum 50 characters allowed.</p>
                    <p data-field-error="password_confirmation" class="mt-1 hidden text-xs font-medium text-red-500"></p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="close-modal rounded-xl bg-slate-100 px-5 py-2.5 font-semibold text-slate-700 transition hover:bg-slate-200">
                        Cancel
                    </button>
                    <button id="updateUserBtn" type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 font-semibold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60">
                        <span class="update-spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span class="update-label">Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- DELETE MODAL --}}
    <div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
        <div class="relative w-full max-w-lg rounded-3xl border border-red-200 bg-white p-6 shadow-2xl">
            <button type="button" class="close-modal absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 hover:text-slate-700">&times;</button>
            <div class="mb-5">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-xl text-red-600">!</div>
                <h3 class="text-2xl font-bold text-red-500">Delete Account</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to delete <strong id="deleteUserName" class="text-slate-900"></strong>?
                </p>
                <p class="mt-2 text-sm text-red-500">This action cannot be undone.</p>
            </div>
            <form method="POST" id="deleteForm" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" class="close-modal rounded-xl bg-slate-100 px-5 py-2.5 font-semibold text-slate-700 transition hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" id="deleteSubmitBtn"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span class="delete-spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    <span class="delete-label">Delete</span>
                </button>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
<script>
    const dashboardUrl        = "{{ route('admin.dashboard') }}";
    const currentType         = "{{ $type }}";
    const currentStatus       = "{{ $status }}";
    const searchInput         = document.getElementById('searchInput');
    const filterBySelect      = document.getElementById('filterBy');
    const usersTableContainer = document.getElementById('usersTableContainer');
    const rowNavigationLoader = document.getElementById('rowNavigationLoader');

    let isRowNavigating  = false;
    let currentFilterBy  = @json($filterBy ?? 'all');
    if (filterBySelect) { filterBySelect.value = currentFilterBy; }

    const createModal = document.getElementById('createModal');
    const editModal   = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    const allModals   = [createModal, editModal, deleteModal];

    // ── Max length live error helper ─────────────────────────────────
    function checkMaxLength(input, errorId, max) {
        const errorEl = document.getElementById(errorId);
        if (!errorEl) return;
        if (input.value.length >= max) {
            errorEl.classList.remove('hidden');
        } else {
            errorEl.classList.add('hidden');
        }
    }

    // ── Reset all inline errors inside a modal ───────────────────────
    function resetModalErrors(modal) {
        if (!modal) return;
        modal.querySelectorAll('p[id$="Error"]').forEach(el => el.classList.add('hidden'));
        clearValidationErrors(modal);
    }

    function clearValidationErrors(modal) {
        if (!modal) return;
        const summary = modal.querySelector('.form-error-summary');
        if (summary) {
            summary.classList.add('hidden');
            summary.innerHTML = '';
        }
        modal.querySelectorAll('[data-field-error]').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function showValidationErrors(modal, errors, fallbackMessage) {
        if (!modal) return;
        clearValidationErrors(modal);
        const summary = modal.querySelector('.form-error-summary');
        const messages = [];
        let hasFieldError = false;

        Object.entries(errors || {}).forEach(([field, fieldMessages]) => {
            const message = Array.isArray(fieldMessages) ? fieldMessages[0] : fieldMessages;
            if (!message) return;
            messages.push(message);
            const target = modal.querySelector(`[data-field-error="${field}"]`);
            if (target) {
                target.textContent = message;
                target.classList.remove('hidden');
                hasFieldError = true;
            }
        });

        if (summary && !hasFieldError) {
            summary.innerHTML = '';
            const summaryMessages = messages.length ? messages : [fallbackMessage || 'Please check the highlighted fields.'];
            summaryMessages.forEach(message => {
                const item = document.createElement('div');
                item.textContent = message;
                summary.appendChild(item);
            });
            summary.classList.remove('hidden');
        }
    }

    function validatePasswordConfirmation(passwordInput, confirmationInput, optionalPassword = false) {
        if (!passwordInput || !confirmationInput) return true;
        passwordInput.setCustomValidity('');
        confirmationInput.setCustomValidity('');

        if (optionalPassword && passwordInput.value === '' && confirmationInput.value === '') {
            return true;
        }

        if (optionalPassword && passwordInput.value === '' && confirmationInput.value !== '') {
            passwordInput.setCustomValidity('Enter a new password before confirming it.');
            return false;
        }

        if (passwordInput.value !== confirmationInput.value) {
            confirmationInput.setCustomValidity('Passwords do not match.');
            return false;
        }

        return true;
    }

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
        resetModalErrors(modal);
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
            clearValidationErrors(createModal);
            openModal(createModal);
        });
    }
    if (btnCreateStudent) {
        btnCreateStudent.addEventListener('click', function () {
            document.getElementById('createRole').value = 'student';
            document.getElementById('createModalTitle').textContent = 'Create Student Account';
            clearValidationErrors(createModal);
            openModal(createModal);
        });
    }
    if (btnCreateAdmin) {
        btnCreateAdmin.addEventListener('click', function () {
            document.getElementById('createRole').value = 'admin';
            document.getElementById('createModalTitle').textContent = 'Create Admin Account';
            clearValidationErrors(createModal);
            openModal(createModal);
        });
    }

    // ── Create form submit ───────────────────────────────────────────
    const createForm = document.getElementById('createForm');
    if (createForm) {
        createForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            e.stopPropagation();
            validatePasswordConfirmation(
                document.getElementById('createPassword'),
                document.getElementById('createPasswordConfirmation')
            );
            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }
            const btn = document.getElementById('createSubmitBtn');
            clearValidationErrors(createModal);
            setButtonLoading(btn, '.create-spinner', '.create-label', 'Creating...');
            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (response.ok) {
                    window.location.href = data.redirect || `${dashboardUrl}?type=${encodeURIComponent(formData.get('role') || currentType)}`;
                } else {
                    showValidationErrors(createModal, data.errors, data.message || 'Error creating user');
                    resetButtonLoading(btn, '.create-spinner', '.create-label', 'Create');
                }
            } catch (error) {
                console.error(error);
                showValidationErrors(createModal, {}, 'Error creating user');
                resetButtonLoading(btn, '.create-spinner', '.create-label', 'Create');
            }
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
            clearValidationErrors(editModal);
            document.getElementById('editFirstName').value            = editBtn.dataset.firstName    ?? '';
            document.getElementById('editMiddleInitial').value        = editBtn.dataset.middleInitial ?? '';
            document.getElementById('editSurname').value              = editBtn.dataset.surname       ?? '';
            document.getElementById('editEmail').value                = editBtn.dataset.email         ?? '';
            document.getElementById('editStatus').value               = editBtn.dataset.status        ?? '';
            document.getElementById('editPassword').value             = '';
            document.getElementById('editPasswordConfirmation').value = '';
            document.getElementById('editForm').action                = editBtn.dataset.updateUrl     ?? '';
            openModal(editModal);
            return;
        }

        // 3. Delete button — MUST be before row check
        const deleteBtn = e.target.closest('.btn-delete-user');
        if (deleteBtn) {
            e.stopPropagation();
            document.getElementById('deleteUserName').textContent = deleteBtn.dataset.name      ?? '';
            document.getElementById('deleteForm').action          = deleteBtn.dataset.deleteUrl ?? '';
            openModal(deleteModal);
            return;
        }

        const toggleBtn = e.target.closest('.btn-toggle-status');
        if (toggleBtn) {
            e.stopPropagation();
            return;
        }

        // 4. Row navigation
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
        e.stopPropagation();
        validatePasswordConfirmation(
            document.getElementById('editPassword'),
            document.getElementById('editPasswordConfirmation'),
            true
        );
        if (!this.checkValidity()) {
            this.reportValidity();
            return;
        }
        const submitBtn = document.getElementById('updateUserBtn');
        clearValidationErrors(editModal);
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
                showValidationErrors(editModal, data.errors, data.message || 'Error updating user');
                resetButtonLoading(submitBtn, '.update-spinner', '.update-label', 'Update');
            }
        } catch (error) {
            console.error(error);
            showValidationErrors(editModal, {}, 'Error updating user');
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
